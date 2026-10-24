<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReprogramacionService
{
    public function __construct(private CitaWorkflow $workflow, private ReprogramacionDisponibilidadService $disponibilidad) {}

    /** @param array<string, mixed> $data */
    public function reprogramar(User $actor, int $citaId, array $data): CitaMedica
    {
        try {
            return DB::transaction(function () use ($actor, $citaId, $data): CitaMedica {
                $original = CitaMedica::query()->findOrFail($citaId);
                $this->workflow->authorize($actor, $original);
                $medicoId = (int) $data['medico_id'];
                DB::table('medicos')->whereIn('id', [$original->medico_id, $medicoId])->orderBy('id')->lockForUpdate()->get();
                DB::table('pacientes')->where('id', $original->paciente_id)->lockForUpdate()->first();
                $cita = $this->workflow->lock($citaId, $data['version'], false);
                $this->workflow->authorize($actor, $cita);
                if (! in_array($cita->estado, ['programada', 'confirmada'], true)) {
                    throw ValidationException::withMessages(['estado' => 'Solo puedes reprogramar una cita programada o confirmada.']);
                }
                if (! DB::table('pacientes')->where('id', $cita->paciente_id)->where('activo', true)->exists()) {
                    throw ValidationException::withMessages(['paciente' => 'El paciente está inactivo. Actívalo antes de reprogramar la cita.']);
                }

                $turnos = $this->disponibilidad->turnos($medicoId, $cita->especialidad_id, $data['fecha'], $cita->id, $cita->paciente_id);
                $inicio = $data['fecha'].' '.$data['inicio'].':00';
                $turno = collect($turnos)->first(fn (array $turno): bool => $turno['horario_id'] === (int) $data['horario_id'] && $turno['inicio'] === $inicio);
                if (! $turno) {
                    throw ValidationException::withMessages(['inicio' => 'El turno ya no está disponible o no pertenece a un horario válido. Consulta nuevamente la disponibilidad.']);
                }
                if ($cita->medico_id === $medicoId && $cita->inicio->format('Y-m-d H:i:s') === $turno['inicio'] && $cita->fin->format('Y-m-d H:i:s') === $turno['fin']) {
                    throw ValidationException::withMessages(['inicio' => 'Selecciona un turno diferente al de la cita actual.']);
                }

                $anteriores = $cita->snapshot();
                $cita->medico_id = $medicoId;
                $cita->horario_id = $turno['horario_id'];
                $cita->inicio = $turno['inicio'];
                $cita->fin = $turno['fin'];
                $cita->save();
                $this->workflow->audit($cita, $actor, 'REPROGRAMAR', $anteriores, $data['motivo'] ?? null);

                return $cita;
            });
        } catch (QueryException $exception) {
            if (($exception->errorInfo[0] ?? $exception->getCode()) === '23P01') {
                throw ValidationException::withMessages(['inicio' => 'Otro usuario ocupó ese turno. La cita conserva su reserva anterior; consulta otro turno.']);
            }
            throw $exception;
        }
    }
}
