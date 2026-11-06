<?php

namespace App\Services\Especialidades;

use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SpecialtyManagement
{
    /**
     * Guarda únicamente los campos editables; el UNIQUE de PostgreSQL resuelve las carreras de altas.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(User $actor, array $data, ?int $id = null): Especialidad
    {
        try {
            return DB::transaction(function () use ($actor, $data, $id): Especialidad {
                $this->authorize($actor);
                $especialidad = $id ? $this->lockForEditing($id, $data) : new Especialidad;
                $previousFee = $especialidad->tarifa_centavos;
                if (array_key_exists('tarifa_consulta', $data)) {
                    $especialidad->tarifa_centavos = $this->feeInCents($data['tarifa_consulta']);
                }
                $especialidad->fill(['nombre' => $data['nombre'], 'descripcion' => $data['descripcion'] ?? null]);
                if (! $id) {
                    $especialidad->activo = true;
                }
                $especialidad->save();
                if ($previousFee !== $especialidad->tarifa_centavos) {
                    DB::table('cambios_tarifa_especialidad')->insert([
                        'especialidad_id' => $especialidad->id,
                        'usuario_id' => $actor->id,
                        'anterior_centavos' => $previousFee,
                        'nueva_centavos' => $especialidad->tarifa_centavos,
                    ]);
                }
                DB::afterCommit(fn () => Log::info($id ? 'especialidad.actualizada' : 'especialidad.creada', ['actor_id' => $actor->id, 'especialidad_id' => $especialidad->id]));

                return $especialidad;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if (str_contains($exception->getMessage(), 'especialidades_nombre_normalizado_unique')) {
                throw ValidationException::withMessages(['nombre' => 'Ya existe una especialidad con ese nombre. Revisa también las especialidades inactivas.']);
            }
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function changeState(User $actor, int $id, array $data): Especialidad
    {
        return DB::transaction(function () use ($actor, $id, $data): Especialidad {
            $this->authorize($actor);
            $especialidad = $this->lockForEditing($id, $data);
            $especialidad->activo = (bool) $data['activo'];
            $especialidad->save();
            DB::afterCommit(fn () => Log::info('especialidad.estado_actualizado', ['actor_id' => $actor->id, 'especialidad_id' => $especialidad->id, 'activo' => $especialidad->activo]));

            return $especialidad;
        });
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->fresh()?->tienePermiso('medicos.gestionar'), 403);
    }

    private function feeInCents(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) || ! preg_match('/\A(?:0|[1-9][0-9]{0,5})(?:\.[0-9]{1,2})?\z/', $value)) {
            throw ValidationException::withMessages(['tarifa_consulta' => 'Ingresa un importe de 0.01 a 999999.99 Bs con punto decimal.']);
        }
        [$units, $decimals] = array_pad(explode('.', $value), 2, '');
        $cents = ((int) $units * 100) + (int) str_pad($decimals, 2, '0');
        if ($cents < 1) {
            throw ValidationException::withMessages(['tarifa_consulta' => 'La tarifa debe ser mayor que cero.']);
        }

        return $cents;
    }

    /**
     * El bloqueo y la versión impiden que una confirmación antigua sobrescriba cambios recientes.
     *
     * @param  array<string, mixed>  $data
     */
    private function lockForEditing(int $id, array $data): Especialidad
    {
        $especialidad = Especialidad::query()->lockForUpdate()->findOrFail($id);
        if (! is_string($data['version'] ?? null) || ! hash_equals($especialidad->editVersion(), $data['version'])) {
            throw ValidationException::withMessages(['version' => 'La especialidad cambió desde que la abriste. Recarga la página y revisa los datos antes de guardar.']);
        }

        return $especialidad;
    }
}
