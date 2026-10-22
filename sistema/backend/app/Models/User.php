<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['nombre_usuario', 'correo', 'contrasena_hash'])]
#[Hidden(['contrasena_hash', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    protected $table = 'usuarios';

    public function getAuthPasswordName(): string
    {
        return 'contrasena_hash';
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->correo;
    }

    public function getEmailForVerification(): string
    {
        return $this->correo;
    }

    public function routeNotificationForMail(): string
    {
        return $this->correo;
    }

    public function tienePermiso(string $codigo): bool
    {
        if (! $this->activo) {
            return false;
        }

        return DB::table('usuario_rol')
            ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
            ->join('rol_permiso', 'rol_permiso.rol_id', '=', 'roles.id')
            ->join('permisos', 'permisos.id', '=', 'rol_permiso.permiso_id')
            ->where('usuario_rol.usuario_id', $this->getKey())
            ->where('usuario_rol.activo', true)
            ->where('usuario_rol.fecha_asignacion', '<=', now())
            ->where(fn ($query) => $query->whereNull('usuario_rol.fecha_expiracion')
                ->orWhere('usuario_rol.fecha_expiracion', '>', now()))
            ->where('roles.activo', true)
            ->where('rol_permiso.activo', true)
            ->where('permisos.activo', true)
            ->where('permisos.codigo', $codigo)
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'email_verified_at' => 'datetime',
            'contrasena_hash' => 'hashed',
        ];
    }
}
