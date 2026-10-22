<?php

namespace App\Models;

use Database\Factories\PacienteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    /** @use HasFactory<PacienteFactory> */
    use HasFactory;

    protected $fillable = ['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'correo', 'direccion', 'activo'];

    protected function casts(): array
    {
        return ['fecha_nacimiento' => 'date', 'activo' => 'boolean'];
    }

    public function editVersion(): string
    {
        $attributes = $this->getRawOriginal();
        ksort($attributes);

        return hash_hmac('sha256', json_encode($attributes, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
