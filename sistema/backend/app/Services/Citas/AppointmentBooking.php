<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentBooking
{
    public function __construct(private AppointmentAvailability $availability) {}

    public function patient(User $actor, ?int $patientId = null, bool $lock = false): Paciente
    {
        $actor = $actor->fresh();
        abort_unless($actor && ($actor->tienePermiso('citas.gestionar') || $actor->tienePermiso('citas.propias')), 403);
        $query = Paciente::query()->where('activo', true);
        if ($actor->tienePermiso('citas.gestionar')) {
            $query->whereKey($patientId ?? 0);
        } else {
            abort_unless($actor->hasVerifiedEmail(), 403, 'Verifica tu correo antes de reservar.');
            $query->where('usuario_id', $actor->id);
            if ($patientId) {
                $query->whereKey($patientId);
            }
        }
        $patient = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $patient) {
            throw ValidationException::withMessages(['paciente_id' => 'Selecciona una ficha activa. Si tu cuenta no tiene una ficha vinculada, solicita ayuda en recepción.']);
        }

        return $patient;
    }

    /** @param array<string, mixed> $data
     * @return array{paciente:Paciente, turno:array, token:string, especialidad:Especialidad}
     */
    public function review(User $actor, array $data): array
    {
        $patient = $this->patient($actor, isset($data['paciente_id']) ? (int) $data['paciente_id'] : null);
        $slot = $this->availability->slots((int) $data['especialidad_id'], $data['fecha'], (int) $data['medico_id'], $patient->id)
            ->first(fn (array $slot) => $slot['horario_id'] === (int) $data['horario_id'] && $slot['inicio'] === $data['inicio']);
        if (! $slot) {
            $this->unavailable();
        }
        $payload = ['actor_id' => $actor->id, 'paciente_id' => $patient->id, 'especialidad_id' => (int) $data['especialidad_id'], 'turno' => $slot, 'uuid' => (string) Str::uuid(), 'emitido' => now()->timestamp];

        return ['paciente' => $patient, 'turno' => $slot, 'token' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)), 'especialidad' => Especialidad::query()->findOrFail($data['especialidad_id'])];
    }

    public function book(User $actor, string $token): CitaMedica
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true, flags: JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw ValidationException::withMessages(['reserva' => 'La revisión no es válida. Consulta nuevamente los turnos.']);
        }
        abort_unless($payload['actor_id'] === $actor->id, 403);
        try {
            return DB::transaction(function () use ($actor, $payload): CitaMedica {
                // CU-06/CU-08 must acquire this same doctor lock before changing availability.
                Medico::query()->lockForUpdate()->findOrFail($payload['turno']['medico_id']);
                $patient = $this->patient($actor, $payload['paciente_id'], true);
                $existing = CitaMedica::query()->where('reserva_token', $payload['uuid'])->first();
                if ($existing) {
                    abort_unless($existing->creada_por === $actor->id && $existing->paciente_id === $patient->id, 403);

                    return $existing;
                }
                if (now()->timestamp - $payload['emitido'] > config('citas.minutos_revision') * 60) {
                    throw ValidationException::withMessages(['reserva' => 'La revisión venció. Consulta nuevamente los turnos.']);
                }
                Especialidad::query()->whereKey($payload['especialidad_id'])->sharedLock()->firstOrFail();
                $slot = $this->availability->slots($payload['especialidad_id'], substr($payload['turno']['inicio'], 0, 10), $payload['turno']['medico_id'], $patient->id)
                    ->first(fn (array $slot) => $slot['horario_id'] === $payload['turno']['horario_id'] && $slot['inicio'] === $payload['turno']['inicio'] && $slot['fin'] === $payload['turno']['fin']);
                if (! $slot) {
                    $this->unavailable();
                }
                $appointment = new CitaMedica;
                $appointment->codigo = 'RES-'.Str::uuid();
                $appointment->paciente_id = $patient->id;
                $appointment->medico_id = $slot['medico_id'];
                $appointment->especialidad_id = $payload['especialidad_id'];
                $appointment->horario_id = $slot['horario_id'];
                $appointment->inicio = $slot['inicio'];
                $appointment->fin = $slot['fin'];
                $appointment->estado = 'programada';
                $appointment->creada_por = $actor->id;
                $appointment->reserva_token = $payload['uuid'];
                $appointment->save();
                $appointment->codigo = 'CIT-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT);
                $appointment->save();
                DB::table('cambios_cita')->insert(['cita_id' => $appointment->id, 'usuario_id' => $actor->id, 'accion' => 'creacion', 'datos_nuevos' => json_encode($appointment->only(['codigo', 'paciente_id', 'medico_id', 'especialidad_id', 'horario_id', 'inicio', 'fin', 'estado']), JSON_THROW_ON_ERROR)]);
                DB::afterCommit(fn () => Log::info('cita.reservada', ['actor_id' => $actor->id, 'cita_id' => $appointment->id, 'medico_id' => $appointment->medico_id]));

                return $appointment;
            }, 3);
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23P01', '23505'], true)) {
                $this->unavailable();
            }
            throw $exception;
        }
    }

    private function unavailable(): never
    {
        throw ValidationException::withMessages(['reserva' => 'El turno ya no está disponible o coincide con otra cita del paciente. Consulta nuevamente la disponibilidad.']);
    }
}
