<?php

namespace App\Models;

use Database\Factories\HorarioAtencionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HorarioAtencion extends Model
{
    /** @use HasFactory<HorarioAtencionFactory> */
    use HasFactory;

    public const DAYS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    protected $table = 'horarios_atencion';

    protected $fillable = ['dia_semana', 'hora_inicio', 'hora_fin', 'duracion_turno_min', 'vigente_desde', 'vigente_hasta'];

    protected $attributes = ['activo' => true, 'vigente_hasta' => null];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'dia_semana' => 'integer', 'duracion_turno_min' => 'integer', 'vigente_desde' => 'date', 'vigente_hasta' => 'date'];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function editVersion(): string
    {
        $data = $this->getRawOriginal();
        foreach (['hora_inicio', 'hora_fin'] as $field) {
            $data[$field] = strlen($data[$field]) === 5 ? $data[$field].':00' : $data[$field];
        }
        $data['vigente_desde'] = $this->vigente_desde->toDateString();
        $data['vigente_hasta'] = $this->vigente_hasta?->toDateString();
        ksort($data);

        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
