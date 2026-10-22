<?php

namespace Tests\Support;

use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Support\Facades\DB;

trait CreatesSchedulingData
{
    protected function scheduleData(Medico $doctor, array $changes = []): array
    {
        return array_replace(['medico_id' => $doctor->id, 'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '12:00', 'duracion_turno_min' => 30, 'vigente_desde' => now()->startOfWeek()->toDateString(), 'vigente_hasta' => null], $changes);
    }

    protected function blockData(Medico $doctor, array $changes = []): array
    {
        return array_replace(['medico_id' => $doctor->id, 'inicio' => now()->addWeek()->startOfWeek()->setTime(9, 0)->format('Y-m-d\TH:i'), 'fin' => now()->addWeek()->startOfWeek()->setTime(12, 0)->format('Y-m-d\TH:i'), 'motivo' => 'Permiso de prueba'], $changes);
    }

    protected function appointmentFor(HorarioAtencion $schedule, array $changes = []): int
    {
        $start = now()->addWeek()->startOfWeek()->setTime(9, 0);
        $specialty = Especialidad::factory()->create();

        return DB::table('citas_medicas')->insertGetId(array_replace([
            'codigo' => 'TEST-'.bin2hex(random_bytes(8)), 'medico_id' => $schedule->medico_id,
            'horario_id' => $schedule->id, 'paciente_id' => Paciente::factory()->create()->id,
            'especialidad_id' => $specialty->id, 'inicio' => $start->format('Y-m-d H:i:s'),
            'fin' => $start->copy()->addMinutes(30)->format('Y-m-d H:i:s'), 'estado' => 'programada',
            'creada_por' => auth()->id() ?? User::factory()->create()->id,
        ], $changes));
    }
}
