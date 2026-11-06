<?php

namespace Database\Factories;

use App\Models\CitaMedica;
use App\Models\PagoConsulta;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<PagoConsulta> */
class PagoConsultaFactory extends Factory
{
    public function definition(): array
    {
        return ['codigo' => (string) Str::uuid(), 'cita_id' => CitaMedica::factory(), 'importe_centavos' => 15000, 'moneda' => 'bob', 'estado' => 'nuevo', 'vence_en' => now()->addMinutes(30)];
    }
}
