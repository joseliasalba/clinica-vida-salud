<?php

namespace Database\Factories;

use App\Models\Medico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medico>
 */
class MedicoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => 'MED-TEST-'.fake()->unique()->uuid(),
            'registro_profesional' => 'REG-'.fake()->unique()->uuid(),
            'nombres' => fake()->firstName(), 'apellidos' => fake()->lastName(),
            'telefono' => '+591 70000000', 'correo' => fake()->safeEmail(), 'activo' => true,
        ];
    }
}
