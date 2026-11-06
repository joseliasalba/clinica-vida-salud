<?php

namespace Database\Factories;

use App\Models\ResultadoEstudio;
use App\Models\SolicitudEstudio;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ResultadoEstudio> */
class ResultadoEstudioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'solicitud_id' => SolicitudEstudio::factory(),
            'descripcion' => 'Contenido ficticio del resultado.',
            'archivo_privado' => null, 'estado_publicacion' => 'borrador',
            'publicado_por' => null, 'fecha_publicacion' => null,
        ];
    }
}
