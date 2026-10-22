<?php

namespace App\Services\Medicos;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use App\Services\Auth\UserAdministration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DoctorManagement
{
    /** @return Builder<User> */
    public function eligibleAccounts(): Builder
    {
        return User::query()->where('usuarios.activo', true)->whereExists(function ($query): void {
            $query->selectRaw('1')->from('usuario_rol')->join('roles', 'roles.id', '=', 'usuario_rol.rol_id')
                ->join('rol_permiso', 'rol_permiso.rol_id', '=', 'roles.id')->join('permisos', 'permisos.id', '=', 'rol_permiso.permiso_id')
                ->whereColumn('usuario_rol.usuario_id', 'usuarios.id')->where('usuario_rol.activo', true)->where('roles.activo', true)
                ->where('rol_permiso.activo', true)->where('permisos.activo', true)->where('permisos.codigo', 'agenda.propia')
                ->where('usuario_rol.fecha_asignacion', '<=', now())->where(fn ($dates) => $dates->whereNull('usuario_rol.fecha_expiracion')->orWhere('usuario_rol.fecha_expiracion', '>', now()));
        });
    }

    /** @param array<string, mixed> $data */
    public function save(User $actor, array $data, ?int $id = null): Medico
    {
        try {
            return DB::transaction(function () use ($actor, $data, $id): Medico {
                DB::select('SELECT pg_advisory_xact_lock(?)', [UserAdministration::LOCK_KEY]);
                $this->authorize($actor);
                $doctor = $id ? $this->lock($id, $data) : new Medico;
                $ids = array_map('intval', $data['especialidades']);
                $specialties = Especialidad::query()->whereIn('id', $ids)->where('activo', true)->orderBy('id')->sharedLock()->get();
                if (count($ids) === 0 || $specialties->count() !== count($ids)) {
                    throw ValidationException::withMessages(['especialidades' => 'Selecciona especialidades activas; alguna pudo cambiar mientras editabas.']);
                }
                $accountId = empty($data['usuario_id']) ? null : (int) $data['usuario_id'];
                if ($accountId !== $doctor->usuario_id && $accountId !== null && ! $this->eligibleAccounts()->whereKey($accountId)->exists()) {
                    throw ValidationException::withMessages(['usuario_id' => 'Selecciona una cuenta activa con permiso para consultar su agenda propia.']);
                }
                $doctor->fill(Arr::only($data, ['registro_profesional', 'nombres', 'apellidos', 'telefono', 'correo']));
                $doctor->usuario_id = $accountId;
                if (! $id) {
                    $doctor->codigo = 'PEND-'.Str::ulid();
                    $doctor->activo = true;
                }
                $doctor->save();
                if (! $id) {
                    $doctor->codigo = 'MED-'.str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT);
                    $doctor->save();
                }
                $activeIds = Especialidad::query()->where('activo', true)->pluck('id');
                DB::table('medico_especialidad')->where('medico_id', $doctor->id)->whereIn('especialidad_id', $activeIds)->update(['activo' => false]);
                foreach ($ids as $specialtyId) {
                    DB::table('medico_especialidad')->updateOrInsert(['medico_id' => $doctor->id, 'especialidad_id' => $specialtyId], ['activo' => true]);
                }
                DB::afterCommit(fn () => Log::info($id ? 'medico.actualizado' : 'medico.creado', ['actor_id' => $actor->id, 'medico_id' => $doctor->id]));

                return $doctor;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $detail = $exception->errorInfo[2] ?? '';
            $field = str_contains($detail, 'registro') ? 'registro_profesional' : (str_contains($detail, 'usuario_id') ? 'usuario_id' : 'codigo');
            throw ValidationException::withMessages([$field => match ($field) {
                'registro_profesional' => 'Ese registro profesional ya existe.',
                'usuario_id' => 'Esa cuenta ya está vinculada a otro médico.',
                default => 'No se pudo asignar el código. Intenta registrar nuevamente.',
            }]);
        }
    }

    /** @param array<string, mixed> $data */
    public function changeState(User $actor, int $id, array $data): Medico
    {
        return DB::transaction(function () use ($actor, $id, $data): Medico {
            $this->authorize($actor);
            $doctor = $this->lock($id, $data);
            if ($data['activo'] && ! $doctor->especialidades()->where('especialidades.activo', true)->wherePivot('activo', true)->exists()) {
                throw ValidationException::withMessages(['activo' => 'Asigna al menos una especialidad activa antes de reactivar al médico.']);
            }
            $doctor->activo = (bool) $data['activo'];
            $doctor->save();
            DB::afterCommit(fn () => Log::info('medico.estado_actualizado', ['actor_id' => $actor->id, 'medico_id' => $doctor->id, 'activo' => $doctor->activo]));

            return $doctor;
        });
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->fresh()?->tienePermiso('medicos.gestionar'), 403);
    }

    /** @param array<string, mixed> $data */
    private function lock(int $id, array $data): Medico
    {
        $doctor = Medico::query()->lockForUpdate()->findOrFail($id);
        if (! is_string($data['version'] ?? null) || ! hash_equals($doctor->editVersion(), $data['version'])) {
            throw ValidationException::withMessages(['version' => 'La ficha o sus especialidades cambiaron. Recarga la página antes de guardar.']);
        }

        return $doctor;
    }
}
