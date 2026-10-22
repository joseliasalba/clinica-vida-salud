<?php

namespace App\Models;

use Database\Factories\IndisponibilidadMedicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndisponibilidadMedico extends Model
{
    /** @use HasFactory<IndisponibilidadMedicoFactory> */
    use HasFactory;

    protected $table = 'indisponibilidades_medico';

    protected $fillable = ['inicio', 'fin', 'motivo'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'inicio' => 'datetime', 'fin' => 'datetime'];
    }

    public function medico(): BelongsTo
    {
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function editVersion(): string
    {
        $data = $this->getRawOriginal();
        ksort($data);

        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
