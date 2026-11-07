<?php

namespace Database\Factories;

use App\Models\PagoConsulta;
use App\Models\ReembolsoConsulta;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ReembolsoConsulta> */
class ReembolsoConsultaFactory extends Factory
{
    public function definition(): array
    {
        return ['codigo' => (string) Str::uuid(), 'pago_id' => PagoConsulta::factory(), 'solicitado_por' => User::factory(),
            'importe_centavos' => 15000, 'motivo' => 'Solicitud de prueba', 'estado' => 'solicitado'];
    }
}
