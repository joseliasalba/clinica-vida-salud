<?php

namespace App\Services\Horarios;

use App\Models\HorarioAtencion;
use App\Models\IndisponibilidadMedico;
use App\Models\Medico;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ScheduleManagement
{
    /** @param array<string, mixed> $data */
    public function saveSchedule(User $actor, array $data, ?int $id = null): HorarioAtencion
    {
        return $this->mutate($actor, HorarioAtencion::class, $data, $id);
    }

    /** @param array<string, mixed> $data */
    public function saveBlock(User $actor, array $data, ?int $id = null): IndisponibilidadMedico
    {
        return $this->mutate($actor, IndisponibilidadMedico::class, $data, $id);
    }

    /** @param array<string, mixed> $data */
    public function changeScheduleState(User $actor, int $id, array $data): HorarioAtencion
    {
        return $this->mutate($actor, HorarioAtencion::class, $data, $id, true);
    }

    /** @param array<string, mixed> $data */
    public function changeBlockState(User $actor, int $id, array $data): IndisponibilidadMedico
    {
        return $this->mutate($actor, IndisponibilidadMedico::class, $data, $id, true);
    }

    /**
     * @param  class-string<HorarioAtencion>|class-string<IndisponibilidadMedico>  $class
     * @param  array<string, mixed>  $data
     */
    private function mutate(User $actor, string $class, array $data, ?int $id, bool $state = false): HorarioAtencion|IndisponibilidadMedico
    {
        try {
            return DB::transaction(function () use ($actor, $class, $data, $id, $state): HorarioAtencion|IndisponibilidadMedico {
                abort_unless($actor->fresh()?->tienePermiso('horarios.gestionar'), 403);
                $doctorId = $id ? $class::query()->findOrFail($id)->medico_id : (int) $data['medico_id'];
                $doctor = Medico::query()->lockForUpdate()->findOrFail($doctorId);
                $record = $id ? $class::query()->lockForUpdate()->findOrFail($id) : new $class;
                if ($id && (! is_string($data['version'] ?? null) || ! hash_equals($record->editVersion(), $data['version']))) {
                    throw ValidationException::withMessages(['version' => 'El registro cambió. Recarga la página y revisa antes de guardar.']);
                }
                if (isset($data['medico_id']) && (int) $data['medico_id'] !== (int) $doctorId) {
                    throw ValidationException::withMessages(['medico_id' => 'El médico de un registro existente no se puede cambiar. Crea otro registro.']);
                }
                if ((! $id || ($state && $data['activo'])) && ! $doctor->activo) {
                    throw ValidationException::withMessages(['medico_id' => 'El médico debe estar activo para registrar o reactivar su agenda.']);
                }
                $record->medico_id = $doctor->id;
                if ($state) {
                    $record->activo = (bool) $data['activo'];
                } elseif ($record instanceof HorarioAtencion) {
                    $values = Arr::only($data, ['dia_semana', 'hora_inicio', 'hora_fin', 'duracion_turno_min', 'vigente_desde', 'vigente_hasta']);
                    $values['vigente_hasta'] = $values['vigente_hasta'] ?? null;
                    foreach (['hora_inicio', 'hora_fin'] as $field) {
                        $values[$field] = strlen($values[$field]) === 5 ? $values[$field].':00' : $values[$field];
                    }
                    $record->fill($values);
                } else {
                    $record->fill(Arr::only($data, ['inicio', 'fin', 'motivo']));
                }
                if ($record instanceof HorarioAtencion) {
                    $this->protectAppointments($record);
                } elseif ($record->activo && $this->pendingAppointments($doctor->id)->where('inicio', '<', $record->fin)->where('fin', '>', $record->inicio)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['intervalo' => 'El bloqueo coincide con citas pendientes. Reprograma o cancela esas citas antes de bloquear.']);
                }
                $record->save();
                $event = $record instanceof HorarioAtencion ? 'horario.guardado' : 'indisponibilidad.guardada';
                DB::afterCommit(fn () => Log::info($event, ['actor_id' => $actor->id, 'medico_id' => $doctor->id, 'registro_id' => $record->id, 'activo' => $record->activo]));

                return $record;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23P01') {
                throw ValidationException::withMessages(['intervalo' => 'Se superpone con otro registro activo del mismo médico. Revisa las horas y la vigencia.']);
            }
            if ($exception->getCode() === '23514') {
                throw ValidationException::withMessages(['intervalo' => 'Revisa el día, las horas, la duración y la vigencia: el intervalo no es válido.']);
            }
            throw $exception;
        }
    }

    private function pendingAppointments(int $doctorId): Builder
    {
        return DB::table('citas_medicas')->where('medico_id', $doctorId)->where('fin', '>', now())->whereNotIn('estado', ['cancelada', 'inasistencia', 'completada']);
    }

    private function protectAppointments(HorarioAtencion $schedule): void
    {
        if (! $schedule->exists) {
            return;
        }
        if ($schedule->isDirty(['dia_semana', 'hora_inicio', 'hora_fin', 'duracion_turno_min', 'vigente_desde']) && DB::table('citas_medicas')->where('horario_id', $schedule->id)->exists()) {
            throw ValidationException::withMessages(['intervalo' => 'Este horario ya tiene citas. Conserva su día, horas, duración y fecha inicial; crea una nueva franja para cambiarlos.']);
        }
        foreach ($this->pendingAppointments($schedule->medico_id)->where('horario_id', $schedule->id)->lockForUpdate()->get() as $appointment) {
            $date = CarbonImmutable::parse($appointment->inicio)->toDateString();
            if (! $schedule->activo || $date < $schedule->vigente_desde->toDateString() || ($schedule->vigente_hasta && $date > $schedule->vigente_hasta->toDateString())) {
                throw ValidationException::withMessages(['intervalo' => 'El cambio dejaría citas pendientes fuera del horario activo. Reprograma o cancela primero las citas afectadas.']);
            }
        }
    }
}
