<?php

namespace App\Services\Citas;

use App\Models\CitaMedica;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CitaWorkflow
{
    /** Una sola fuente de transiciones; completada conserva el valor histórico de la BD. */
    public const TRANSITIONS = [
        'programada' => ['confirmada', 'completada', 'cancelada', 'inasistencia'],
        'confirmada' => ['completada', 'cancelada', 'inasistencia'],
        'en_curso' => ['completada'],
        'completada' => [], 'atendida' => [], 'cancelada' => [], 'inasistencia' => [],
    ];

    public function authorize(User $actor, CitaMedica $cita, bool $allowOwnDoctor = false): void
    {
        $current = $actor->fresh();
        abort_unless($current?->activo, 403);
        if ($current->tienePermiso('citas.gestionar')) {
            return;
        }
        abort_unless($allowOwnDoctor && $current->tienePermiso('agenda.propia')
            && DB::table('medicos')->where('id', $cita->medico_id)->where('usuario_id', $current->id)->exists(), 403);
    }

    /** Debe llamarse dentro de la transacción que realiza el cambio. */
    public function lock(int $id, string $version): CitaMedica
    {
        $cita = CitaMedica::query()->lockForUpdate()->findOrFail($id);
        if (! hash_equals($cita->editVersion(), $version)) {
            throw ValidationException::withMessages(['version' => 'La cita cambió desde que abriste el formulario. Recarga sus datos antes de continuar.']);
        }

        return $cita;
    }

    public function assertTransition(CitaMedica $cita, string $target): void
    {
        if (! in_array($target, self::TRANSITIONS[$cita->estado] ?? [], true)) {
            throw ValidationException::withMessages(['estado' => 'No se permite ese cambio desde el estado actual de la cita.']);
        }
        if (in_array($target, ['completada', 'inasistencia'], true) && $cita->inicio->isAfter(now('America/La_Paz'))) {
            throw ValidationException::withMessages(['estado' => 'La atención o inasistencia solo puede registrarse cuando llegó la hora de inicio.']);
        }
    }

    /** El historial se escribe en la misma transacción para no dejar cambios sin trazabilidad. */
    public function audit(CitaMedica $cita, User $actor, string $action, array $before, ?string $motivo = null): void
    {
        DB::table('cambios_cita')->insert([
            'cita_id' => $cita->id, 'usuario_id' => $actor->id, 'accion' => $action,
            'datos_anteriores' => json_encode($before, JSON_THROW_ON_ERROR),
            'datos_nuevos' => json_encode($cita->snapshot(), JSON_THROW_ON_ERROR),
            'motivo' => $motivo, 'fecha_cambio' => now('America/La_Paz'),
        ]);
    }
}
