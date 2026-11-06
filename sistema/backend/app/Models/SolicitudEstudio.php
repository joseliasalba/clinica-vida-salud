<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SolicitudEstudio extends Model
{
    use HasFactory;

    public const ESTADOS = ['solicitado' => 'Solicitado', 'en_proceso' => 'En proceso', 'completado' => 'Completado', 'cancelado' => 'Cancelado'];

    protected $table = 'solicitudes_estudio';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_solicitud' => 'immutable_datetime'];
    }

    public function resultado(): HasOne
    {
        return $this->hasOne(ResultadoEstudio::class, 'solicitud_id');
    }

    public function editVersion(): string
    {
        return hash_hmac('sha256', json_encode([
            $this->id, $this->consulta_id, $this->nombre_estudio, $this->indicaciones,
            $this->estado, $this->fecha_solicitud?->format('Y-m-d H:i:s'),
            $this->resultado?->id, $this->resultado?->descripcion,
            $this->resultado?->archivo_privado, $this->resultado?->estado_publicacion,
            $this->resultado?->publicado_por, $this->resultado?->fecha_publicacion?->format('Y-m-d H:i:s'),
        ], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
