<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Rules\UniqueUserIdentity;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserAdministration
{
    public const LOCK_KEY = 340201;

    public function saveUser(User $actor, array $data, ?int $id = null): User
    {
        return $this->mutate($actor, function () use ($actor, $data, $id): User {
            $user = $id ? User::query()->lockForUpdate()->findOrFail($id) : new User;
            Validator::make($data, [
                'nombre_usuario' => [new UniqueUserIdentity('nombre_usuario', $id)],
                'correo' => [new UniqueUserIdentity('correo', $id)],
            ])->validate();
            $roles = array_map('intval', $data['roles']);
            if (DB::table('roles')->whereIn('id', $roles)->where('activo', true)->count() !== count($roles)) {
                throw ValidationException::withMessages(['roles' => 'Revisa los roles: alguno fue desactivado.']);
            }
            if ($actor->id === $id && ! $data['activo']) {
                throw ValidationException::withMessages(['activo' => 'No puedes desactivar tu propia cuenta.']);
            }

            $oldEmail = $user->correo;
            $passwordChanged = ! empty($data['password']);
            $deactivated = $user->exists && $user->activo && ! $data['activo'];
            $user->forceFill(['nombre_usuario' => $data['nombre_usuario'], 'correo' => $data['correo'], 'activo' => (bool) $data['activo']]);
            if ($passwordChanged) {
                $user->contrasena_hash = $data['password'];
            }
            if ($passwordChanged || $deactivated) {
                $user->remember_token = Str::random(60);
            }
            $user->save();
            if ($passwordChanged || $deactivated || $oldEmail !== $user->correo) {
                DB::table('password_reset_tokens')->whereIn('email', array_filter([$oldEmail, $user->correo]))->delete();
            }

            $assignments = DB::table('usuario_rol')->where('usuario_id', $user->id)->get()->keyBy('rol_id');
            DB::table('usuario_rol')->where('usuario_id', $user->id)->update(['activo' => false]);
            foreach ($roles as $roleId) {
                $previous = $assignments->get($roleId);
                $valid = $previous && $previous->activo && $previous->fecha_asignacion <= now()->toDateTimeString()
                    && (! $previous->fecha_expiracion || $previous->fecha_expiracion > now()->toDateTimeString());
                DB::table('usuario_rol')->updateOrInsert(['usuario_id' => $user->id, 'rol_id' => $roleId], [
                    'activo' => true, 'fecha_asignacion' => $valid ? $previous->fecha_asignacion : now(),
                    'fecha_expiracion' => $valid ? $previous->fecha_expiracion : null,
                ]);
            }
            DB::afterCommit(fn () => Log::info($id ? 'usuarios.updated' : 'usuarios.created', ['actor_id' => $actor->id, 'usuario_id' => $user->id]));

            return $user;
        });
    }

    public function saveRole(User $actor, array $data, ?int $id = null): int
    {
        return $this->mutate($actor, function () use ($actor, $data, $id): int {
            $role = $id ? DB::table('roles')->where('id', $id)->lockForUpdate()->first() : null;
            abort_if($id && ! $role, 404);
            $reserved = array_keys(config('permisos.roles'));
            if ($role && in_array($role->nombre, $reserved, true) && $role->nombre !== $data['nombre']) {
                throw ValidationException::withMessages(['nombre' => 'El nombre de un rol base no se puede cambiar.']);
            }
            if ((! $role || $role->nombre !== $data['nombre']) && in_array($data['nombre'], $reserved, true)) {
                throw ValidationException::withMessages(['nombre' => 'Ese nombre está reservado para un rol base.']);
            }
            $permissions = array_map('intval', $data['permisos']);
            $activeIds = DB::table('permisos')->where('activo', true)->whereIn('codigo', array_keys(config('permisos.catalogo')))->pluck('id');
            if (count(array_diff($permissions, $activeIds->all())) > 0) {
                throw ValidationException::withMessages(['permisos' => 'Revisa los permisos: alguno fue desactivado o no pertenece al catálogo.']);
            }
            $values = ['nombre' => $data['nombre'], 'descripcion' => $data['descripcion'] ?? null, 'activo' => (bool) $data['activo'], 'updated_at' => now()];
            if ($role) {
                DB::table('roles')->where('id', $id)->update($values);
            } else {
                $id = DB::table('roles')->insertGetId($values + ['created_at' => now()]);
            }
            DB::table('rol_permiso')->where('rol_id', $id)->whereIn('permiso_id', $activeIds)->update(['activo' => false]);
            foreach ($permissions as $permissionId) {
                DB::table('rol_permiso')->updateOrInsert(['rol_id' => $id, 'permiso_id' => $permissionId], ['activo' => true]);
            }
            DB::afterCommit(fn () => Log::info('roles.saved', ['actor_id' => $actor->id, 'rol_id' => $id]));

            return $id;
        });
    }

    public function setPermissionState(User $actor, int $id, bool $active): void
    {
        $this->mutate($actor, function () use ($actor, $id, $active): void {
            $permission = DB::table('permisos')->where('id', $id)->lockForUpdate()->first();
            abort_unless($permission && array_key_exists($permission->codigo, config('permisos.catalogo')), 404);
            DB::table('permisos')->where('id', $id)->update(['activo' => $active]);
            DB::afterCommit(fn () => Log::info('permisos.updated', ['actor_id' => $actor->id, 'permiso_id' => $id, 'activo' => $active]));
        });
    }

    private function mutate(User $actor, Closure $operation): mixed
    {
        try {
            return DB::transaction(function () use ($actor, $operation): mixed {
                DB::select('SELECT pg_advisory_xact_lock(?)', [self::LOCK_KEY]);
                abort_unless($actor->fresh()?->tienePermiso('usuarios.gestionar'), 403);
                $result = $operation();
                $hasManager = DB::table('usuarios')
                    ->join('usuario_rol', 'usuario_rol.usuario_id', '=', 'usuarios.id')
                    ->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
                    ->join('rol_permiso', 'rol_permiso.rol_id', '=', 'roles.id')
                    ->join('permisos', 'permisos.id', '=', 'rol_permiso.permiso_id')
                    ->where('usuarios.activo', true)->where('usuario_rol.activo', true)->where('roles.activo', true)
                    ->where('rol_permiso.activo', true)->where('permisos.activo', true)->where('permisos.codigo', 'usuarios.gestionar')
                    ->where('usuario_rol.fecha_asignacion', '<=', now())
                    ->where(fn ($query) => $query->whereNull('usuario_rol.fecha_expiracion')->orWhere('usuario_rol.fecha_expiracion', '>', now()))->exists();
                if (! $hasManager) {
                    throw ValidationException::withMessages(['operacion' => 'No puedes dejar al sistema sin una cuenta activa con permiso para administrar usuarios.']);
                }
                if (! $actor->fresh()?->tienePermiso('usuarios.gestionar')) {
                    throw ValidationException::withMessages(['operacion' => 'No puedes retirar tu propio permiso de administración. Pide el cambio a otra cuenta administradora.']);
                }

                return $result;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505') {
                throw ValidationException::withMessages(['operacion' => 'Ya existe una cuenta o rol con esos datos. Revisa el nombre y el correo.']);
            }
            throw $exception;
        }
    }
}
