<?php

namespace App\Services\Pacientes;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientRegistration
{
    /** @param array<string, mixed> $data */
    public function save(User $actor, array $data, ?int $id = null): Paciente
    {
        try {
            $paciente = DB::transaction(function () use ($actor, $data, $id): Paciente {
                abort_unless($actor->fresh()?->activo && $actor->tienePermiso('pacientes.gestionar'), 403);
                $paciente = $id ? Paciente::query()->lockForUpdate()->findOrFail($id) : new Paciente;
                if ($id && (! is_string($data['version'] ?? null) || ! hash_equals($paciente->editVersion(), $data['version']))) {
                    throw ValidationException::withMessages(['version' => 'La ficha cambió desde que la abriste. Recarga la edición y revisa los datos antes de guardar.']);
                }
                $paciente->fill(Arr::except($data, ['version']));
                if (! $id) {
                    $paciente->codigo = 'PEND-'.Str::ulid();
                }
                $paciente->save();
                if (! $id) {
                    $paciente->codigo = 'PAC-'.str_pad((string) $paciente->id, 6, '0', STR_PAD_LEFT);
                    $paciente->save();
                }
                DB::afterCommit(fn () => Log::info($id ? 'paciente.actualizado' : 'paciente.creado', ['actor_id' => $actor->id, 'paciente_id' => $paciente->id]));

                return $paciente;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'pacientes_ci_')) {
                throw ValidationException::withMessages(['ci' => 'Ya existe un paciente con ese CI. Busca su ficha antes de registrar otro.']);
            }
            if (str_contains($exception->getMessage(), 'pacientes_codigo_unique')) {
                throw ValidationException::withMessages(['codigo' => 'No se pudo asignar el código. Intenta registrar nuevamente.']);
            }
            throw $exception;
        }

        return $paciente;
    }
}
