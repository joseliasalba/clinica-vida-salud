<?php

namespace App\Services\Citas;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TurnoAtencionService
{
    public function __construct(private CitaWorkflow $workflow) {}

    public function registrarLlegada(User $actor, int $id, string $version): void
    {
        DB::transaction(function () use ($actor, $id, $version): void {
            $cita = $this->workflow->lock($id, $version);
            $this->workflow->authorize($actor, $cita);
            if (! in_array($cita->estado, ['programada', 'confirmada'], true) || ! $cita->inicio->isSameDay(now('America/La_Paz'))) {
                throw ValidationException::withMessages(['llegada' => 'Solo se registra la llegada de citas vigentes para hoy.']);
            }
            if (DB::table('pagos_consulta')->where('cita_id', $cita->id)->where('estado', '!=', 'pagado')->exists()) {
                throw ValidationException::withMessages(['llegada' => 'La cita tiene un pago sin confirmar.']);
            }
            $arrival = DB::table('llegadas_citas')->where('cita_id', $cita->id)->lockForUpdate()->first();
            $scheduled = $cita->inicio->format('Y-m-d H:i:s');
            if ($arrival && $arrival->inicio_programado === $scheduled && (int) $arrival->medico_id === $cita->medico_id) {
                throw ValidationException::withMessages(['llegada' => 'La llegada ya fue registrada para esta cita.']);
            }
            DB::table('llegadas_citas')->updateOrInsert(['cita_id' => $cita->id], [
                'registrada_por' => $actor->id, 'medico_id' => $cita->medico_id, 'inicio_programado' => $scheduled, 'llegada_en' => now(),
            ]);
            $this->workflow->audit($cita, $actor, 'REGISTRAR_LLEGADA', $cita->snapshot());
        });
    }
}
