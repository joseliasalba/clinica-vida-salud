<?php

namespace App\Models;

use Database\Factories\MedicoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Medico extends Model
{
    /** @use HasFactory<MedicoFactory> */
    use HasFactory;

    protected $table = 'medicos';

    protected $fillable = ['registro_profesional', 'nombres', 'apellidos', 'telefono', 'correo'];

    protected $attributes = ['activo' => true, 'usuario_id' => null];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function especialidades(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'medico_especialidad', 'medico_id', 'especialidad_id')->withPivot('activo')->orderBy('especialidades.id');
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function editVersion(): string
    {
        $data = $this->getRawOriginal();
        ksort($data);
        $data['especialidades'] = $this->especialidades()->get()->map(fn (Especialidad $specialty): array => [$specialty->id, (bool) $specialty->pivot->activo])->all();

        return hash_hmac('sha256', json_encode($data, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
