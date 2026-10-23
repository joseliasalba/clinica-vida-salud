<?php

namespace App\Models;

use Database\Factories\CitaMedicaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitaMedica extends Model
{
    /** @use HasFactory<CitaMedicaFactory> */
    use HasFactory;

    protected $table = 'citas_medicas';

    public const RELEASED_STATES = ['cancelada', 'inasistencia'];

    protected function casts(): array
    {
        return ['inicio' => 'immutable_datetime', 'fin' => 'immutable_datetime'];
    }

    public function paciente(): BelongsTo
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class, 'especialidad_id');
    }

    public function horario(): BelongsTo
    {
        return $this->belongsTo(HorarioAtencion::class, 'horario_id');
    }
}
