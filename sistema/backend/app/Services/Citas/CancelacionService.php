<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CancelacionService
{
    public function __construct(private readonly CitaWorkflow $workflow) {}

    /** @param array{motivo: string, version: string, confirmar: mixed} $data */
    public function cancelar(User $actor, int $id, array $data): CitaMedica
    {
        if (is_string($data['motivo'] ?? null)) {
            $data['motivo'] = Str::squish($data['motivo']);
        }
        $data = Validator::make($data, [
            'version' => ['required', 'string', 'size:64'],
            'motivo' => ['required', 'string', 'max:255'], 'confirmar' => ['required', 'accepted'],
        ])->validate();

        return DB::transaction(function () use ($actor, $id, $data): CitaMedica {
            $cita = $this->workflow->lock($id, $data['version']);
            $this->workflow->authorize($actor, $cita);
            $this->workflow->assertTransition($cita, 'cancelada');
            $before = $cita->snapshot();
            $cita->estado = 'cancelada';
            $cita->motivo_cancelacion = $data['motivo'];
            $cita->save();
            $this->workflow->audit($cita, $actor, 'CANCELAR', $before, $data['motivo']);

            return $cita;
        });
    }
}
