<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsistenciaService
{
    public function __construct(private CitaWorkflow $workflow) {}

    /** @param array<string, mixed> $data */
    public function changeState(User $actor, int $id, array $data): CitaMedica
    {
        return DB::transaction(function () use ($actor, $id, $data): CitaMedica {
            $cita = $this->workflow->lock($id, (string) ($data['version'] ?? ''));
            $this->workflow->authorize($actor, $cita, true);

            if (! in_array($data['estado'] ?? null, ['confirmada', 'completada', 'inasistencia'], true)) {
                throw ValidationException::withMessages(['estado' => 'Selecciona confirmada, atendida o inasistencia.']);
            }
            if (! in_array($data['confirmacion'] ?? null, ['yes', 'on', '1', 1, true, 'true'], true)) {
                throw ValidationException::withMessages(['confirmacion' => 'Confirma que revisaste el cambio de estado.']);
            }

            $this->workflow->assertTransition($cita, $data['estado']);
            $before = $cita->snapshot();
            $cita->estado = $data['estado'];
            $cita->save();
            $this->workflow->audit($cita, $actor, 'CAMBIAR_ESTADO', $before, $data['motivo'] ?? null);

            return $cita;
        });
    }
}
