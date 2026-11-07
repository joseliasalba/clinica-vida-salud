<?php

namespace App\Services\Pagos;

use App\Models\CitaMedica;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PagoConsulta;
use App\Models\ReembolsoConsulta;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class RefundManagement
{
    public function __construct(private StripeGateway $gateway) {}

    public function isAdministrator(User $actor): bool
    {
        $actor = $actor->fresh();

        return (bool) ($actor?->activo && $actor->tienePermiso('usuarios.gestionar') && DB::table('usuario_rol')
            ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->where('usuario_rol.usuario_id', $actor->id)->where('roles.nombre', 'administrador')
            ->where('roles.activo', true)->where('usuario_rol.activo', true)
            ->where('usuario_rol.fecha_asignacion', '<=', now())
            ->where(fn ($q) => $q->whereNull('usuario_rol.fecha_expiracion')->orWhere('usuario_rol.fecha_expiracion', '>', now()))
            ->exists());
    }

    public function authorizeView(User $actor, PagoConsulta $payment): void
    {
        $actor = $actor->fresh();
        abort_unless($actor?->activo, 403);
        if ($actor->tienePermiso('citas.gestionar')) {
            return;
        }
        app(PaymentManagement::class)->authorize($actor, $payment);
    }

    public function canRequest(PagoConsulta $payment): bool
    {
        return $payment->pagado_en !== null && in_array($payment->estado, ['pagado', 'revision'], true)
            && ($payment->cita->estado === 'cancelada' || $payment->estado === 'revision')
            && (($payment->metodo === 'qr' && $payment->referencia_qr) || ($payment->metodo !== 'qr' && $payment->stripe_payment_intent));
    }

    public function request(User $actor, PagoConsulta $original, string $reason): ReembolsoConsulta
    {
        $this->authorizeView($actor, $original);
        if ($original->metodo === 'qr') {
            abort_unless($actor->fresh()->tienePermiso('citas.gestionar'), 403);
        }
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw ValidationException::withMessages(['motivo' => 'Indica un motivo de hasta 500 caracteres.']);
        }
        try {
            return $this->lockedPayment($original, function (PagoConsulta $payment) use ($actor, $reason): ReembolsoConsulta {
                if ($payment->metodo === 'qr') {
                    abort_unless($actor->fresh()->tienePermiso('citas.gestionar'), 403);
                }
                if (! $this->canRequest($payment)) {
                    throw ValidationException::withMessages(['reembolso' => 'Este pago todavía no admite una solicitud de devolución.']);
                }
                $refund = ReembolsoConsulta::query()->create(['codigo' => (string) Str::uuid(), 'pago_id' => $payment->id,
                    'solicitado_por' => $actor->id, 'importe_centavos' => $payment->importe_centavos,
                    'motivo' => $reason, 'estado' => 'solicitado']);
                $this->audit($refund, $actor, 'solicitado');

                return $refund;
            });
        } catch (QueryException $error) {
            if ($error->getCode() === '23505') {
                throw ValidationException::withMessages(['reembolso' => 'Ya existe una solicitud activa o una devolución para este pago.']);
            }
            throw $error;
        }
    }

    public function decide(User $actor, ReembolsoConsulta $original, bool $approve, ?string $reason): void
    {
        abort_unless($this->isAdministrator($actor), 403);
        if (! $approve && (! is_string($reason) || trim($reason) === '')) {
            throw ValidationException::withMessages(['motivo' => 'Explica el motivo del rechazo.']);
        }
        $this->lockedRefund($original, function (ReembolsoConsulta $refund, PagoConsulta $payment) use ($actor, $approve, $reason): void {
            if ($refund->estado !== 'solicitado' || ! $this->canRequest($payment)) {
                throw ValidationException::withMessages(['reembolso' => 'La solicitud cambió o el pago ya no es elegible.']);
            }
            $refund->forceFill(['estado' => $approve ? 'aprobado' : 'rechazado',
                'revisado_por' => $actor->id, 'decision_motivo' => $reason])->save();
            $this->audit($refund, $actor, $approve ? 'aprobado' : 'rechazado', $reason);
        });
    }

    public function recordManual(User $actor, ReembolsoConsulta $original, string $reference): void
    {
        abort_unless($this->isAdministrator($actor), 403);
        $reference = trim($reference);
        if ($reference === '' || mb_strlen($reference) > 100) {
            throw ValidationException::withMessages(['referencia' => 'Indica el número de operación de devolución.']);
        }
        try {
            $this->lockedRefund($original, function (ReembolsoConsulta $refund, PagoConsulta $payment) use ($actor, $reference): void {
                if ($payment->metodo !== 'qr' || $refund->estado !== 'aprobado') {
                    throw ValidationException::withMessages(['reembolso' => 'La devolución manual no está disponible.']);
                }
                $refund->forceFill(['estado' => 'completado', 'referencia_devolucion' => $reference, 'completado_en' => now()])->save();
                $this->audit($refund, $actor, 'registrado_manual');
            });
        } catch (QueryException $error) {
            if ($error->getCode() === '23505') {
                throw ValidationException::withMessages(['referencia' => 'Esta operación de devolución ya fue registrada.']);
            }
            throw $error;
        }
    }

    public function processStripe(User $actor, ReembolsoConsulta $original): void
    {
        abort_unless($this->isAdministrator($actor), 403);
        $refund = $this->lockedRefund($original, function (ReembolsoConsulta $refund, PagoConsulta $payment) use ($actor): ReembolsoConsulta {
            if ($payment->metodo === 'qr' || ! $payment->stripe_payment_intent || ! in_array($refund->estado, ['aprobado', 'procesando'], true)) {
                throw ValidationException::withMessages(['reembolso' => 'La devolución de Stripe no está disponible.']);
            }
            if ($refund->estado === 'aprobado') {
                $refund->forceFill(['estado' => 'procesando', 'iniciado_en' => now()])->save();
                $this->audit($refund, $actor, 'iniciado_stripe');
            }

            return $refund;
        });
        if ($refund->stripe_refund_id) {
            $result = $this->gateway->retrieveRefund($refund->stripe_refund_id);
        } else {
            if ($refund->iniciado_en->lt(now()->subHours(23))) {
                throw new RuntimeException('El intento requiere conciliación manual por antigüedad.');
            }
            $result = $this->gateway->createRefund($refund->pago->stripe_payment_intent, $refund->importe_centavos, $refund->codigo);
        }
        $this->applyStripe($refund, $result, $actor);
    }

    public function applyStripe(ReembolsoConsulta $original, array $result, ?User $actor = null, ?string $eventId = null): void
    {
        $payment = $original->pago;
        if ((array_key_exists('livemode', $result) && $result['livemode'] !== false) || ! str_starts_with($result['id'] ?? '', 're_')
            || ($result['payment_intent'] ?? null) !== $payment->stripe_payment_intent
            || ($result['amount'] ?? null) !== $original->importe_centavos || ($result['currency'] ?? null) !== $payment->moneda
            || ($result['metadata']['reembolso_codigo'] ?? null) !== $original->codigo
            || ! in_array($result['status'] ?? '', ['pending', 'succeeded', 'failed', 'canceled', 'requires_action'], true)) {
            throw new RuntimeException('La devolución no coincide con la solicitud.');
        }
        $this->lockedRefund($original, function (ReembolsoConsulta $refund) use ($result, $actor, $eventId): void {
            if ($refund->stripe_refund_id && $refund->stripe_refund_id !== $result['id']) {
                throw new RuntimeException('Identificador de devolución distinto.');
            }
            if ($eventId && DB::table('eventos_reembolso')->where('stripe_event_id', $eventId)->exists()) {
                return;
            }
            if (! in_array($refund->estado, ['procesando', 'completado', 'fallido', 'revision'], true)) {
                throw new RuntimeException('La devolución no fue iniciada.');
            }
            $before = $refund->estado;
            $refund->stripe_refund_id = $result['id'];
            if ($before === 'completado' && in_array($result['status'], ['failed', 'canceled'], true)) {
                $refund->estado = 'revision';
            }
            if (! in_array($before, ['completado', 'fallido', 'revision'], true)) {
                $refund->estado = match ($result['status']) {
                    'succeeded' => 'completado', 'failed', 'canceled' => 'fallido',
                    'requires_action' => 'revision', default => 'procesando',
                };
                if ($refund->estado === 'completado') {
                    $refund->completado_en = now();
                }
            }
            $refund->save();
            if ($before !== $refund->estado || $eventId) {
                $this->audit($refund, $actor, 'stripe_'.$refund->estado, null, $eventId);
            }
        });
    }

    public function stripeEvent(array $event): void
    {
        $object = $event['data']['object'] ?? [];
        $code = $object['metadata']['reembolso_codigo'] ?? '';
        if (! Str::isUuid($code) || ! is_string($object['id'] ?? null)) {
            return;
        }
        $refund = ReembolsoConsulta::query()->where('codigo', $code)->first();
        if ($refund) {
            $this->applyStripe($refund, $this->gateway->retrieveRefund($object['id']), null, $event['id']);
        }
    }

    private function lockedPayment(PagoConsulta $original, callable $callback): mixed
    {
        return DB::transaction(function () use ($original, $callback): mixed {
            $appointment = $original->cita()->firstOrFail();
            Medico::query()->lockForUpdate()->findOrFail($appointment->medico_id);
            Paciente::query()->lockForUpdate()->findOrFail($appointment->paciente_id);
            CitaMedica::query()->lockForUpdate()->findOrFail($appointment->id);
            $payment = PagoConsulta::query()->lockForUpdate()->findOrFail($original->id);

            return $callback($payment);
        }, 3);
    }

    private function lockedRefund(ReembolsoConsulta $original, callable $callback): mixed
    {
        return $this->lockedPayment($original->pago, function (PagoConsulta $payment) use ($original, $callback): mixed {
            $refund = ReembolsoConsulta::query()->lockForUpdate()->findOrFail($original->id);

            return $callback($refund, $payment);
        });
    }

    private function audit(ReembolsoConsulta $refund, ?User $actor, string $action, ?string $detail = null, ?string $eventId = null): void
    {
        DB::table('eventos_reembolso')->insert(['reembolso_id' => $refund->id, 'usuario_id' => $actor?->id,
            'stripe_event_id' => $eventId, 'accion' => $action, 'detalle' => $detail]);
    }
}
