<?php

namespace Database\Factories;

use App\Models\HorarioAtencion;
use App\Models\Medico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HorarioAtencion>
 */
class HorarioAtencionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medico_id' => Medico::factory(), 'dia_semana' => 1,
            'hora_inicio' => '09:00:00', 'hora_fin' => '12:00:00', 'duracion_turno_min' => 30,
            'vigente_desde' => now()->startOfWeek()->toDateString(), 'vigente_hasta' => null, 'activo' => true,
        ];
    }
}
