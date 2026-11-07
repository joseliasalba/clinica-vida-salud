<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\PagoConsulta;
use App\Services\Pagos\ManualQrPayment;
use App\Services\Pagos\PaymentManagement;
use App\Services\Pagos\RefundManagement;
use App\Services\Pagos\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Stripe\Exception\SignatureVerificationException;
use Throwable;

class PagoController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->tienePermiso('citas.propias') && $request->user()->cumpleVerificacionParaAcceso(), 403);
        $payments = PagoConsulta::query()->with('cita.especialidad')
            ->whereHas('cita.paciente', fn ($q) => $q->where('usuario_id', $request->user()->id)->where('activo', true))
            ->latest('id')->paginate(15);

        return view('pagos.index', ['pagos' => $payments]);
    }

    public function show(Request $request, PagoConsulta $pago, PaymentManagement $payments, ManualQrPayment $manual, RefundManagement $refunds): View
    {
        $payments->authorize($request->user(), $pago);

        $pago->load(['cita.especialidad', 'reembolsos']);

        return view('pagos.show', ['pago' => $pago, 'qrDisponible' => (bool) $manual->activeQr(),
            'puedeSolicitarReembolso' => $pago->metodo !== 'qr' && $refunds->canRequest($pago) && ! $pago->reembolsos->contains(fn ($r) => $r->estado !== 'rechazado'),
            'solicitarQrEnRecepcion' => $pago->metodo === 'qr' && $refunds->canRequest($pago) && ! $pago->reembolsos->contains(fn ($r) => $r->estado !== 'rechazado')]);
    }

    public function start(Request $request, PagoConsulta $pago, PaymentManagement $payments): RedirectResponse
    {
        $payments->authorize($request->user(), $pago);
        try {
            $pago = $payments->start($request->user(), $pago);
            if ($pago->estado === 'pendiente' && $pago->vence_en->isFuture() && $pago->checkout_url) {
                return redirect()->away($pago->checkout_url, 303);
            }
        } catch (ValidationException $error) {
            return back()->withErrors($error->errors());
        } catch (Throwable $error) {
            Log::warning('pago.checkout_no_confirmado', ['pago_id' => $pago->id, 'tipo' => $error::class]);

            return back()->withErrors(['pago' => 'No pudimos confirmar la conexión con Stripe. No generes otra reserva: actualiza este pago o vuelve a intentarlo.']);
        }

        return redirect()->route('pagos.show', $pago);
    }

    public function refresh(Request $request, PagoConsulta $pago, PaymentManagement $payments): RedirectResponse
    {
        $payments->authorize($request->user(), $pago);
        try {
            $payments->refresh($pago);
        } catch (Throwable $error) {
            Log::warning('pago.consulta_no_confirmada', ['pago_id' => $pago->id, 'tipo' => $error::class]);

            return back()->withErrors(['pago' => 'Stripe no pudo confirmar el estado. Conservamos tu intento; vuelve a consultar en un momento.']);
        }

        return redirect()->route('pagos.show', $pago)->with('status', 'Estado consultado.');
    }

    public function invoice(Request $request, PagoConsulta $pago, PaymentManagement $payments, StripeGateway $gateway): RedirectResponse
    {
        $payments->authorize($request->user(), $pago);
        abort_unless(in_array($pago->estado, ['pagado', 'revision'], true) && $pago->stripe_invoice_id, 404);
        try {
            $invoice = $gateway->invoice($pago->stripe_invoice_id);
            $url = $invoice['hosted_invoice_url'] ?? '';
            abort_unless(($invoice['livemode'] ?? null) === false && ($invoice['status'] ?? '') === 'paid'
                && ($invoice['currency'] ?? '') === $pago->moneda && ($invoice['total'] ?? null) === $pago->importe_centavos
                && is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === 'invoice.stripe.com', 409);

            return redirect()->away($url);
        } catch (Throwable $error) {
            Log::warning('pago.comprobante_no_disponible', ['pago_id' => $pago->id, 'tipo' => $error::class]);

            return back()->withErrors(['pago' => 'El comprobante de prueba todavía no está disponible. Actualiza el pago e inténtalo de nuevo.']);
        }
    }

    public function webhook(Request $request, StripeGateway $gateway, PaymentManagement $payments): JsonResponse
    {
        try {
            $event = $gateway->event($request->getContent(), (string) $request->header('Stripe-Signature'));
        } catch (SignatureVerificationException|\UnexpectedValueException) {
            return response()->json(['error' => 'Firma o contenido inválido.'], 400);
        } catch (Throwable) {
            return response()->json(['error' => 'Webhook no disponible.'], 503);
        }
        try {
            $payments->webhook($event);
        } catch (Throwable $error) {
            Log::warning('pago.webhook_pendiente', ['tipo' => $error::class]);

            return response()->json(['error' => 'Pendiente de conciliación.'], 503);
        }

        return response()->json(['received' => true]);
    }
}
