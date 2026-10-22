<?php

namespace Database\Factories;

use App\Models\IndisponibilidadMedico;
use App\Models\Medico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IndisponibilidadMedico>
 */
class IndisponibilidadMedicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'medico_id' => Medico::factory(),
            'inicio' => now()->addWeek()->startOfDay()->format('Y-m-d H:i:s'),
            'fin' => now()->addWeek()->startOfDay()->addDay()->format('Y-m-d H:i:s'),
            'motivo' => 'Ausencia de prueba', 'activo' => true,
        ];
    }
}
