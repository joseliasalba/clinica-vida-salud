<?php

namespace Tests\Support;

use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreatesAppointments
{
    /** Datos ficticios relacionados; los tests consumidores deben revertir su transacción. */
    protected function createAppointment(array $changes = []): CitaMedica
    {
        $start = CarbonImmutable::parse($changes['inicio'] ?? now('America/La_Paz')->addDay()->setTime(9, 0), 'America/La_Paz');
        $medicoId = $changes['medico_id'] ?? Medico::factory()->create()->id;
        $especialidadId = $changes['especialidad_id'] ?? Especialidad::factory()->create()->id;
        DB::table('medico_especialidad')->insertOrIgnore(['medico_id' => $medicoId, 'especialidad_id' => $especialidadId, 'activo' => true]);
        $horarioId = $changes['horario_id'] ?? DB::table('horarios_atencion')->insertGetId([
            'medico_id' => $medicoId, 'dia_semana' => $start->dayOfWeekIso,
            'hora_inicio' => '09:00:00', 'hora_fin' => '17:00:00', 'duracion_turno_min' => 30,
            'vigente_desde' => $start->subDays(30)->toDateString(), 'activo' => true,
        ]);

        return CitaMedica::query()->create(array_replace([
            'codigo' => 'CM-'.Str::ulid(), 'paciente_id' => Paciente::factory()->create()->id,
            'medico_id' => $medicoId, 'especialidad_id' => $especialidadId, 'horario_id' => $horarioId,
            'inicio' => $start, 'fin' => $start->addMinutes(30), 'estado' => 'programada',
            'creada_por' => User::factory()->create()->id,
        ], $changes))->fresh();
    }
}
