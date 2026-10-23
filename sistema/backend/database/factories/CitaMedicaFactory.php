<?php

namespace Database\Factories;

use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<CitaMedica> */
class CitaMedicaFactory extends Factory
{
    public function definition(): array
    {
        $start = now()->addWeek()->startOfWeek()->setTime(9, 0);

        return [
            'codigo' => 'TEST-'.Str::uuid(), 'paciente_id' => Paciente::factory(),
            'horario_id' => HorarioAtencion::factory(),
            'medico_id' => fn (array $attributes) => HorarioAtencion::query()->findOrFail($attributes['horario_id'])->medico_id,
            'especialidad_id' => Especialidad::factory(),
            'inicio' => $start, 'fin' => $start->copy()->addMinutes(30),
            'estado' => 'programada', 'creada_por' => User::factory(),
        ];
    }
}
