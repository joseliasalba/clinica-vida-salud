<?php

namespace App\Http\Controllers\Pagos;

use App\Http\Controllers\Controller;
use App\Models\PagoConsulta;
use App\Models\ReembolsoConsulta;
use App\Services\Pagos\RefundManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class ReembolsoController extends Controller
{
    public function index(Request $request, RefundManagement $service): View
    {
        abort_unless($request->user()->tienePermiso('citas.gestionar'), 403);
        $solicitudes = ReembolsoConsulta::query()->with(['pago.cita.paciente', 'pago.cita.especialidad'])->latest('id')->paginate(15);
        $pagos = PagoConsulta::query()->with(['cita.paciente', 'cita.especialidad'])
            ->whereNotNull('pagado_en')->whereIn('estado', ['pagado', 'revision'])
            ->where(fn ($q) => $q->where('estado', 'revision')->orWhereHas('cita', fn ($c) => $c->where('estado', 'cancelada')))
            ->where(fn ($q) => $q->where(fn ($qr) => $qr->where('metodo', 'qr')->whereNotNull('referencia_qr'))
                ->orWhere(fn ($stripe) => $stripe->whereNotNull('stripe_payment_intent')->where(fn ($method) => $method->whereNull('metodo')->orWhere('metodo', 'stripe'))))
            ->whereDoesntHave('reembolsos', fn ($q) => $q->where('estado', '<>', 'rechazado'))
            ->latest('id')->paginate(15, ['*'], 'pagos');

        return view('pagos.reembolsos', ['solicitudes' => $solicitudes, 'pagos' => $pagos, 'esAdmin' => $service->isAdministrator($request->user())]);
    }

    public function store(Request $request, PagoConsulta $pago, RefundManagement $service): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:500']]);
        $service->request($request->user(), $pago, $data['motivo']);

        return $request->user()->tienePermiso('citas.gestionar')
            ? redirect()->route('reembolsos.index')->with('status', 'Solicitud registrada para revisión.')
            : redirect()->route('pagos.show', $pago)->with('status', 'Solicitud enviada para revisión.');
    }

    public function decide(Request $request, ReembolsoConsulta $reembolso, RefundManagement $service): RedirectResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:aprobar,rechazar'], 'motivo' => ['nullable', 'string', 'max:500', 'required_if:decision,rechazar']]);
        $service->decide($request->user(), $reembolso, $data['decision'] === 'aprobar', $data['motivo'] ?? null);

        return redirect()->route('reembolsos.index')->with('status', 'Decisión registrada. La devolución todavía no se ha efectuado.');
    }

    public function manual(Request $request, ReembolsoConsulta $reembolso, RefundManagement $service): RedirectResponse
    {
        $data = $request->validate(['referencia' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9][a-zA-Z0-9 ._\\/-]*$/'], 'confirmar' => ['required', 'accepted']]);
        $service->recordManual($request->user(), $reembolso, $data['referencia']);

        return redirect()->route('reembolsos.index')->with('status', 'Devolución manual registrada.');
    }

    public function stripe(Request $request, ReembolsoConsulta $reembolso, RefundManagement $service): RedirectResponse
    {
        abort_unless($service->isAdministrator($request->user()), 403);
        try {
            $service->processStripe($request->user(), $reembolso);
        } catch (ValidationException $error) {
            throw $error;
        } catch (Throwable $error) {
            Log::warning('reembolso.stripe_pendiente', ['reembolso_id' => $reembolso->id, 'tipo' => $error::class]);

            return redirect()->route('reembolsos.index')->withErrors(['reembolso' => 'No se confirmó el estado de Stripe. No generes otra solicitud; vuelve a consultar este intento.']);
        }

        return redirect()->route('reembolsos.index')->with('status', 'Estado de la devolución de Stripe consultado.');
    }
}
