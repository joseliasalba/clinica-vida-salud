<?php

namespace App\Services\Pacientes;

use App\Models\Paciente;
use App\Models\User;
use App\Services\Auth\UserAdministration;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientSelfRegistration
{
    /** @param array<string, mixed> $data */
    public function register(array $data): User
    {
        try {
            return DB::transaction(function () use ($data): User {
                DB::select('SELECT pg_advisory_xact_lock(?)', [UserAdministration::LOCK_KEY]);
                $role = DB::table('roles')->where('nombre', 'paciente')->where('activo', true)->first();
                if (! $role) {
                    throw ValidationException::withMessages(['registro' => 'El registro no está disponible en este momento. Contacta a recepción.']);
                }
                $user = User::query()->create([
                    'nombre_usuario' => $data['nombre_usuario'], 'correo' => $data['correo'], 'contrasena_hash' => $data['password'],
                ]);
                DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => $role->id, 'activo' => true, 'fecha_asignacion' => now()]);
                $patient = new Paciente(Arr::only($data, ['ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'correo', 'direccion']));
                $patient->usuario_id = $user->id;
                $patient->activo = true;
                $patient->codigo = 'PEND-'.Str::ulid();
                $patient->save();
                $patient->codigo = 'PAC-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT);
                $patient->save();
                DB::afterCommit(fn () => Log::info('paciente.autorregistrado', ['usuario_id' => $user->id, 'paciente_id' => $patient->id]));

                return $user;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['registro' => 'No se pudo completar el registro con esos datos. Si ya tienes una cuenta, inicia sesión; si fuiste atendido antes, contacta a recepción.']);
        }
    }
}
