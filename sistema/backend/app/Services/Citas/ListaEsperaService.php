<?php

namespace App\Services\Citas;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListaEsperaService
{
    public function __construct(private AppointmentBooking $booking) {}

    public function inscribir(User $actor, array $data): void
    {
        DB::transaction(function () use ($actor, $data): void {
            $patient = $this->booking->patient($actor, isset($data['paciente_id']) ? (int) $data['paciente_id'] : null, true);
            if (! Especialidad::query()->whereKey($data['especialidad_id'])->where('activo', true)->exists()) {
                throw ValidationException::withMessages(['especialidad_id' => 'Selecciona una especialidad activa.']);
            }
            if (! empty($data['medico_id']) && ! Medico::query()->whereKey($data['medico_id'])->where('activo', true)->whereHas('especialidades', fn ($query) => $query->where('especialidades.id', $data['especialidad_id'])->where('medico_especialidad.activo', true))->exists()) {
                throw ValidationException::withMessages(['medico_id' => 'El médico no atiende esta especialidad.']);
            }
            if (DB::table('lista_espera_citas')->where('paciente_id', $patient->id)->where('especialidad_id', $data['especialidad_id'])->where('fecha', $data['fecha'])->whereIn('estado', ['esperando', 'contactado'])->exists()) {
                throw ValidationException::withMessages(['lista_espera' => 'Ya existe una solicitud activa para esta especialidad y fecha.']);
            }
            DB::table('lista_espera_citas')->insert([
                'paciente_id' => $patient->id, 'especialidad_id' => $data['especialidad_id'], 'medico_id' => $data['medico_id'] ?? null,
                'fecha' => $data['fecha'], 'estado' => 'esperando', 'creado_por' => $actor->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
    }

    public function cambiarEstado(User $actor, int $id, string $state): void
    {
        DB::transaction(function () use ($actor, $id, $state): void {
            $entry = DB::table('lista_espera_citas')->where('id', $id)->lockForUpdate()->first();
            abort_unless($entry, 404);
            $manager = $actor->tienePermiso('citas.gestionar');
            $owner = $actor->tienePermiso('citas.propias') && $actor->cumpleVerificacionParaAcceso() && DB::table('pacientes')->where('id', $entry->paciente_id)->where('usuario_id', $actor->id)->where('activo', true)->exists();
            abort_unless($manager || ($owner && $state === 'cancelado'), 403);
            $allowed = $manager ? ['esperando' => ['contactado', 'finalizado', 'cancelado'], 'contactado' => ['esperando', 'finalizado', 'cancelado']] : ['esperando' => ['cancelado'], 'contactado' => ['cancelado']];
            if (! in_array($state, $allowed[$entry->estado] ?? [], true)) {
                throw ValidationException::withMessages(['lista_espera' => 'La solicitud ya cambió de estado. Actualiza la página.']);
            }
            DB::table('lista_espera_citas')->where('id', $id)->update(['estado' => $state, 'actualizado_por' => $actor->id, 'updated_at' => now()]);
        });
    }
}
