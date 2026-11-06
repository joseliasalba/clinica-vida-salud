<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultadoEstudio extends Model
{
    use HasFactory;

    protected $table = 'resultados_estudio';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['fecha_publicacion' => 'immutable_datetime'];
    }

    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(SolicitudEstudio::class, 'solicitud_id');
    }
}
