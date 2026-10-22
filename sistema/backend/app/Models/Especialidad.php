<?php

namespace App\Models;

use Database\Factories\EspecialidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Especialidad extends Model
{
    /** @use HasFactory<EspecialidadFactory> */
    use HasFactory;

    protected $table = 'especialidades';

    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    protected $attributes = ['activo' => true];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Especialidad $especialidad): void {
            $especialidad->nombre = Str::squish($especialidad->nombre);
            $especialidad->nombre_normalizado = self::normalizeName($especialidad->nombre);
        });
    }

    /** Clave de comparación: conserva el nombre visible y unifica tildes, mayúsculas y espacios. */
    public static function normalizeName(string $nombre): string
    {
        return Str::lower(Str::ascii(Str::squish($nombre)));
    }

    /** Firma los datos leídos para detectar formularios antiguos sin agregar una columna de versión. */
    public function editVersion(): string
    {
        $attributes = $this->getRawOriginal();
        ksort($attributes);

        return hash_hmac('sha256', json_encode($attributes, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }
}
