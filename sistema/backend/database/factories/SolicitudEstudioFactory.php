<?php

namespace Database\Factories;

use App\Models\CitaMedica;
use App\Models\SolicitudEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** @extends Factory<SolicitudEstudio> */
class SolicitudEstudioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'consulta_id' => function (): int {
                $start = now()->subWeek()->startOfWeek()->setTime(9, 0);
                $appointment = CitaMedica::factory()->create(['estado' => 'completada', 'inicio' => $start, 'fin' => $start->copy()->addMinutes(30)]);
                $history = DB::table('historias_clinicas')->insertGetId(['paciente_id' => $appointment->paciente_id, 'nro_expediente' => 'TEST-'.Str::uuid()]);

                return DB::table('consultas_medicas')->insertGetId([
                    'historia_id' => $history, 'cita_id' => $appointment->id, 'medico_id' => $appointment->medico_id, 'fecha_atencion' => $start,
                ]);
            },
            'nombre_estudio' => 'Estudio de prueba', 'indicaciones' => null,
            'estado' => 'solicitado', 'fecha_solicitud' => now(),
        ];
    }
}
