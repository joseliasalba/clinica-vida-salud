<?php

namespace App\Services\Pagos;

use App\Models\CitaMedica;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PagoConsulta;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualQrPayment
{
    public function activeQr(): ?object
    {
        return DB::table('codigos_qr_pago')->where('activo', true)->first(['id', 'mime']);
    }

    public function saveQr(User $actor, UploadedFile $file): void
    {
        abort_unless($actor->fresh()?->activo && $actor->fresh()->tienePermiso('usuarios.gestionar'), 403);
        $content = file_get_contents($file->getRealPath());
        $image = $content === false ? false : @getimagesizefromstring($content);
        if (! $image || ! in_array($image['mime'], ['image/png', 'image/jpeg'], true) || strlen($content) > 2097152) {
            throw ValidationException::withMessages(['qr' => 'Selecciona una imagen PNG o JPEG válida de hasta 2 MB.']);
        }
        DB::transaction(function () use ($actor, $image, $content): void {
            DB::select('SELECT pg_advisory_xact_lock(1820261106)');
            DB::table('codigos_qr_pago')->where('activo', true)->update(['activo' => false, 'updated_at' => now()]);
            DB::table('codigos_qr_pago')->insert(['imagen_base64' => base64_encode($content), 'mime' => $image['mime'],
                'activo' => true, 'creado_por' => $actor->id, 'created_at' => now(), 'updated_at' => now()]);
        }, 3);
    }

    public function select(User $actor, PagoConsulta $original): void
    {
        app(PaymentManagement::class)->authorize($actor, $original);
        $this->locked($original, function (PagoConsulta $payment, CitaMedica $appointment) use ($actor): void {
            if ($payment->estado !== 'nuevo' || $payment->metodo !== null || ! $payment->vence_en->isFuture() || $appointment->estado !== 'pendiente_pago') {
                throw ValidationException::withMessages(['pago' => 'No se puede cambiar el método de este pago.']);
            }
            $qr = $this->activeQr();
            if (! $qr) {
                throw ValidationException::withMessages(['pago' => 'El QR todavía no está disponible.']);
            }
            $payment->forceFill(['metodo' => 'qr', 'qr_id' => $qr->id, 'estado' => 'qr_pendiente'])->save();
            $this->audit($payment, $actor, 'seleccionado');
        });
    }

    public function report(User $actor, PagoConsulta $original, string $reference): void
    {
        app(PaymentManagement::class)->authorize($actor, $original);
        $reference = trim($reference);
        try {
            $this->locked($original, function (PagoConsulta $payment, CitaMedica $appointment) use ($actor, $reference): void {
                if ($payment->metodo !== 'qr' || ! in_array($payment->estado, ['qr_pendiente', 'vencido'], true) || $payment->referencia_qr !== null) {
                    throw ValidationException::withMessages(['pago' => 'Este pago no admite otro reporte.']);
                }
                if (($payment->vence_en->isPast() || ! $appointment->inicio->isFuture()) && $appointment->estado === 'pendiente_pago') {
                    $this->changeAppointment($appointment, $actor, 'cancelada', 'plazo_pago_qr_vencido');
                }
                $payment->forceFill(['referencia_qr' => $reference, 'reportado_en' => now(), 'estado' => 'qr_revision'])->save();
                $this->audit($payment, $actor, 'reportado');
            });
        } catch (QueryException $error) {
            if ($error->getCode() === '23505') {
                throw ValidationException::withMessages(['referencia' => 'Este número de operación ya fue registrado. Contacta a recepción.']);
            }
            throw $error;
        }
    }

    public function decide(User $actor, PagoConsulta $original, bool $approve, ?string $reason): void
    {
        abort_unless($actor->fresh()?->activo && $actor->fresh()->tienePermiso('citas.gestionar'), 403);
        $this->locked($original, function (PagoConsulta $payment, CitaMedica $appointment) use ($actor, $approve, $reason): void {
            if ($payment->metodo !== 'qr' || $payment->estado !== 'qr_revision' || ! $payment->referencia_qr) {
                throw ValidationException::withMessages(['pago' => 'Este reporte ya fue revisado o no está disponible.']);
            }
            if ($approve) {
                $payment->pagado_en = now();
                if ($appointment->estado === 'pendiente_pago' && $appointment->inicio->isFuture()) {
                    $this->changeAppointment($appointment, $actor, 'confirmada', 'pago_qr_confirmado');
                    $payment->estado = 'pagado';
                } else {
                    if ($appointment->estado === 'pendiente_pago') {
                        $this->changeAppointment($appointment, $actor, 'cancelada', 'pago_qr_revision_tardia');
                    }
                    $payment->estado = 'revision';
                }
            } else {
                $payment->estado = 'qr_rechazado';
                if ($appointment->estado === 'pendiente_pago') {
                    $this->changeAppointment($appointment, $actor, 'cancelada', 'pago_qr_rechazado');
                }
            }
            $payment->save();
            $this->audit($payment, $actor, $approve ? 'aprobado' : 'rechazado', $reason);
        });
    }

    public function expire(PagoConsulta $original): PagoConsulta
    {
        return $this->locked($original, function (PagoConsulta $payment, CitaMedica $appointment): PagoConsulta {
            if ($payment->estado === 'qr_pendiente' && $payment->vence_en->isPast()) {
                $payment->estado = 'vencido';
                $payment->save();
                if ($appointment->estado === 'pendiente_pago') {
                    $actor = User::findOrFail($appointment->creada_por);
                    $this->changeAppointment($appointment, $actor, 'cancelada', 'plazo_pago_qr_vencido');
                }
            }

            return $payment;
        });
    }

    private function locked(PagoConsulta $original, callable $callback): mixed
    {
        return DB::transaction(function () use ($original, $callback): mixed {
            $appointment = $original->cita()->firstOrFail();
            Medico::query()->lockForUpdate()->findOrFail($appointment->medico_id);
            Paciente::query()->lockForUpdate()->findOrFail($appointment->paciente_id);
            $appointment = CitaMedica::query()->lockForUpdate()->findOrFail($appointment->id);
            $payment = PagoConsulta::query()->lockForUpdate()->findOrFail($original->id);

            return $callback($payment, $appointment);
        }, 3);
    }

    private function audit(PagoConsulta $payment, User $actor, string $action, ?string $reason = null): void
    {
        DB::table('eventos_pago_manual')->insert(['pago_id' => $payment->id, 'usuario_id' => $actor->id, 'accion' => $action, 'motivo' => $reason]);
    }

    private function changeAppointment(CitaMedica $appointment, User $actor, string $state, string $action): void
    {
        $before = $appointment->snapshot();
        $appointment->estado = $state;
        $appointment->save();
        DB::table('cambios_cita')->insert(['cita_id' => $appointment->id, 'usuario_id' => $actor->id, 'accion' => $action,
            'datos_anteriores' => json_encode($before, JSON_THROW_ON_ERROR), 'datos_nuevos' => json_encode($appointment->snapshot(), JSON_THROW_ON_ERROR),
            'motivo' => 'Validación manual de pago QR en recepción.']);
    }
}
