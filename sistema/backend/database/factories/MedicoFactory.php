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
            'codigo' => 'MED-'.fake()->unique()->numerify('########'),
            'registro_profesional' => fake()->unique()->bothify('REG-########'),
            'nombres' => fake()->firstName(), 'apellidos' => fake()->lastName(),
            'telefono' => '70000000', 'correo' => fake()->unique()->safeEmail(), 'activo' => true,
        ];
    }
}
