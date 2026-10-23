<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CitaMedica extends Model
{
    protected $table = 'citas_medicas';

    protected $guarded = ['id'];

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

    /** Detecta formularios antiguos aun cuando dos cambios ocurran en el mismo segundo. */
    public function editVersion(): string
    {
        $attributes = $this->getRawOriginal();
        ksort($attributes);

        return hash_hmac('sha256', json_encode($attributes, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $data = $this->only(['id', 'codigo', 'paciente_id', 'medico_id', 'especialidad_id', 'horario_id', 'estado', 'motivo', 'motivo_cancelacion']);
        $data['inicio'] = $this->inicio->format('Y-m-d H:i:s');
        $data['fin'] = $this->fin->format('Y-m-d H:i:s');

        return $data;
    }

    public function estadoLabel(): string
    {
        return match ($this->estado) {
            'programada' => 'Programada', 'confirmada' => 'Confirmada',
            'en_curso' => 'En atención', 'completada', 'atendida' => 'Atendida',
            'cancelada' => 'Cancelada', 'inasistencia' => 'Inasistencia',
            default => 'Estado no reconocido',
        };
    }
}
