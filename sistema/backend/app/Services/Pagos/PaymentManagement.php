<?php

namespace App\Services\Pagos;

use App\Models\CitaMedica;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PagoConsulta;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Stripe\Exception\InvalidRequestException;

class PaymentManagement
{
    public function __construct(private StripeGateway $gateway) {}

    public function authorize(User $actor, PagoConsulta $payment): void
    {
        $actor = $actor->fresh();
        abort_unless($actor?->activo && $actor->tienePermiso('citas.propias') && $actor->cumpleVerificacionParaAcceso(), 403);
        abort_unless($payment->cita()->whereHas('paciente', fn ($q) => $q->where('usuario_id', $actor->id)->where('activo', true))->exists(), 403);
    }

    /** Se llama dentro de la transacción de reserva, con médico y paciente bloqueados. */
    public function reserve(CitaMedica $appointment, int $amount): PagoConsulta
    {
        return PagoConsulta::query()->create([
            'codigo' => (string) Str::uuid(), 'cita_id' => $appointment->id,
            'importe_centavos' => $amount, 'moneda' => 'bob', 'estado' => 'nuevo',
            'vence_en' => now()->addMinutes(config('pagos.minutos_retencion')),
        ]);
    }

    public function start(User $actor, PagoConsulta $payment): PagoConsulta
    {
        $this->authorize($actor, $payment);
        $payment = DB::transaction(function () use ($payment): PagoConsulta {
            $payment = PagoConsulta::query()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->metodo === 'qr') {
                throw ValidationException::withMessages(['pago' => 'Este pago ya utiliza QR.']);
            }
            if ($payment->estado !== 'nuevo') {
                return $payment;
            }
            if ($payment->vence_en->isPast()) {
                throw ValidationException::withMessages(['pago' => 'El plazo para iniciar el pago terminó. Actualiza su estado.']);
            }
            // Se fija una sola vez para que todos los reintentos tengan el mismo cuerpo.
            $payment->vence_en = now()->addMinutes(config('pagos.minutos_retencion') + 1);
            $payment->checkout_payload = [
                'mode' => 'payment', 'customer_creation' => 'always',
                'invoice_creation' => ['enabled' => true],
                'adaptive_pricing' => ['enabled' => false],
                'locale' => 'es', 'client_reference_id' => $payment->codigo,
                'metadata' => ['pago_codigo' => $payment->codigo],
                'payment_intent_data' => ['metadata' => ['pago_codigo' => $payment->codigo]],
                'integration_identifier' => 'clinica_cu18_'.substr(hash('sha256', $payment->codigo), 0, 8),
                'expires_at' => $payment->vence_en->timestamp,
                'line_items' => [['price_data' => [
                    'currency' => 'bob', 'unit_amount' => $payment->importe_centavos,
                    'product_data' => ['name' => 'Consulta médica — prueba académica'],
                ], 'quantity' => 1]],
                'success_url' => route('pagos.show', $payment),
                'cancel_url' => route('pagos.show', $payment),
            ];
            $payment->metodo = 'stripe';
            $payment->estado = 'incierto';
            $payment->save();

            return $payment;
        }, 3);
        if ($payment->stripe_session_id || in_array($payment->estado, ['pagado', 'revision', 'vencido'], true)) {
            return $payment;
        }
        // Stripe puede podar claves de idempotencia después de 24 h: jamás recrear después.
        if ($payment->created_at->lt(now()->subHours(23))) {
            throw new RuntimeException('El intento requiere conciliación manual en Stripe.');
        }

        return $this->apply($payment, $this->gateway->create($payment));
    }

    public function refresh(PagoConsulta $payment): PagoConsulta
    {
        $payment->refresh();
        if ($payment->metodo === 'qr') {
            return app(ManualQrPayment::class)->expire($payment);
        }
        if ($payment->stripe_session_id) {
            $session = $this->gateway->retrieve($payment->stripe_session_id);
            if (($session['status'] ?? '') === 'open' && $payment->vence_en->isPast()) {
                try {
                    $session = $this->gateway->expire($payment->stripe_session_id);
                } catch (InvalidRequestException) {
                    $session = $this->gateway->retrieve($payment->stripe_session_id);
                }
            }

            return $this->apply($payment, $session);
        }
        if ($payment->estado === 'incierto') {
            if ($payment->created_at->lt(now()->subHours(23))) {
                throw new RuntimeException('El intento requiere conciliación manual en Stripe.');
            }

            return $this->apply($payment, $this->gateway->create($payment));
        }
        if ($payment->estado === 'nuevo' && $payment->vence_en->isPast()) {
            return $this->releaseUnstarted($payment);
        }

        return $payment;
    }

    /** La respuesta se consulta con la clave del servidor; nunca se confía en el retorno del navegador. */
    public function webhook(array $event): void
    {
        if (($event['livemode'] ?? null) !== false || isset($event['account'])) {
            throw new RuntimeException('Evento fuera del sandbox directo.');
        }
        if (in_array($event['type'] ?? '', ['refund.created', 'refund.updated', 'refund.failed'], true)) {
            app(RefundManagement::class)->stripeEvent($event);

            return;
        }
        if (! in_array($event['type'] ?? '', ['checkout.session.completed', 'checkout.session.expired', 'checkout.session.async_payment_succeeded', 'checkout.session.async_payment_failed'], true)) {
            return;
        }
        $object = $event['data']['object'] ?? [];
        if (! Str::isUuid($object['metadata']['pago_codigo'] ?? '')) {
            return;
        }
        $payment = PagoConsulta::query()->where('codigo', $object['metadata']['pago_codigo'] ?? '')->first();
        if (! $payment) {
            return;
        }
        $this->apply($payment, $this->gateway->retrieve($object['id']), $event['id']);
    }

    public function apply(PagoConsulta $original, array $session, ?string $eventId = null): PagoConsulta
    {
        if ($original->metodo === 'qr' || ($session['livemode'] ?? null) !== false || ($session['mode'] ?? '') !== 'payment'
            || ! in_array($session['status'] ?? '', ['open', 'complete', 'expired'], true)
            || ($session['metadata']['pago_codigo'] ?? '') !== $original->codigo
            || ($session['client_reference_id'] ?? '') !== $original->codigo
            || ($session['currency'] ?? '') !== $original->moneda
            || ($session['amount_total'] ?? null) !== $original->importe_centavos
            || ! str_starts_with($session['id'] ?? '', 'cs_test_')) {
            throw new RuntimeException('La sesión no coincide con el pago esperado.');
        }

        return DB::transaction(function () use ($original, $session, $eventId): PagoConsulta {
            $appointment = $original->cita()->firstOrFail();
            Medico::query()->lockForUpdate()->findOrFail($appointment->medico_id);
            Paciente::query()->lockForUpdate()->findOrFail($appointment->paciente_id);
            $appointment = CitaMedica::query()->lockForUpdate()->findOrFail($appointment->id);
            $payment = PagoConsulta::query()->lockForUpdate()->findOrFail($original->id);
            if ($payment->metodo === 'qr') {
                throw new RuntimeException('El pago utiliza otro método.');
            }
            if ($payment->stripe_session_id && $payment->stripe_session_id !== $session['id']) {
                throw new RuntimeException('Sesión de pago distinta de la registrada.');
            }
            if ($eventId && DB::table('eventos_pago')->where('stripe_event_id', $eventId)->exists()) {
                return $payment;
            }
            $before = $payment->estado;
            $payment->stripe_session_id = $session['id'];
            $url = $session['url'] ?? null;
            $payment->checkout_url = is_string($url) && parse_url($url, PHP_URL_SCHEME) === 'https' && parse_url($url, PHP_URL_HOST) === 'checkout.stripe.com' ? $url : null;
            if (is_int($session['expires_at'] ?? null)) {
                $payment->vence_en = CarbonImmutable::createFromTimestamp($session['expires_at'], config('app.timezone'));
            }
            if (is_string($session['invoice'] ?? null)) {
                $payment->stripe_invoice_id = $session['invoice'];
            }
            if (! in_array($payment->estado, ['pagado', 'revision'], true)) {
                if (($session['payment_status'] ?? '') === 'paid' && ($session['status'] ?? '') === 'complete') {
                    if (! is_string($session['payment_intent'] ?? null)) {
                        throw new RuntimeException('Falta la transacción del pago.');
                    }
                    $payment->stripe_payment_intent = $session['payment_intent'];
                    $payment->pagado_en = now();
                    $payment->estado = 'revision';
                    if ($appointment->estado === 'pendiente_pago' && $appointment->inicio->isFuture()) {
                        $payment->estado = 'pagado';
                        $this->changeAppointment($appointment, 'confirmada', 'pago_confirmado');
                    }
                } elseif (($session['status'] ?? '') === 'expired'
                    || (($session['status'] ?? '') === 'complete' && $payment->vence_en->isPast())) {
                    $payment->estado = 'vencido';
                    if ($appointment->estado === 'pendiente_pago') {
                        $this->changeAppointment($appointment, 'cancelada', 'plazo_pago_vencido');
                    }
                } elseif ($payment->estado !== 'vencido') {
                    $payment->estado = ($session['status'] ?? '') === 'complete' ? 'procesando' : 'pendiente';
                }
            }
            $payment->save();
            if ($before !== $payment->estado || $eventId) {
                DB::table('eventos_pago')->insert(['pago_id' => $payment->id, 'stripe_event_id' => $eventId, 'anterior' => $before, 'nuevo' => $payment->estado]);
            }

            return $payment;
        }, 3);
    }

    private function releaseUnstarted(PagoConsulta $original): PagoConsulta
    {
        return DB::transaction(function () use ($original): PagoConsulta {
            $appointment = $original->cita()->firstOrFail();
            Medico::query()->lockForUpdate()->findOrFail($appointment->medico_id);
            Paciente::query()->lockForUpdate()->findOrFail($appointment->paciente_id);
            $appointment = CitaMedica::query()->lockForUpdate()->findOrFail($appointment->id);
            $payment = PagoConsulta::query()->lockForUpdate()->findOrFail($original->id);
            if ($payment->estado === 'nuevo' && $payment->vence_en->isPast()) {
                $payment->estado = 'vencido';
                $payment->save();
                if ($appointment->estado === 'pendiente_pago') {
                    $this->changeAppointment($appointment, 'cancelada', 'pago_no_iniciado');
                }
                DB::table('eventos_pago')->insert(['pago_id' => $payment->id, 'anterior' => 'nuevo', 'nuevo' => 'vencido']);
            }

            return $payment;
        }, 3);
    }

    private function changeAppointment(CitaMedica $appointment, string $state, string $action): void
    {
        $before = $appointment->snapshot();
        $appointment->estado = $state;
        $appointment->save();
        DB::table('cambios_cita')->insert([
            'cita_id' => $appointment->id, 'usuario_id' => $appointment->creada_por,
            'accion' => $action, 'datos_anteriores' => json_encode($before, JSON_THROW_ON_ERROR),
            'datos_nuevos' => json_encode($appointment->snapshot(), JSON_THROW_ON_ERROR),
            'motivo' => 'CU-18: conciliación automática con Stripe sandbox.',
        ]);
    }
}
