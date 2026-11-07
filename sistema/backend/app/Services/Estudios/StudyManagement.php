<?php

namespace App\Services\Estudios;

use App\Models\Medico;
use App\Models\Paciente;
use App\Models\ResultadoEstudio;
use App\Models\SolicitudEstudio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudyManagement
{
    public function isDoctor(User $actor): bool
    {
        return $actor->tienePermiso('estudios.solicitar') || $actor->tienePermiso('estudios.publicar');
    }

    public function doctorId(User $actor, ?string $permission = null): int
    {
        abort_unless($actor->fresh()?->activo && ($permission ? $actor->tienePermiso($permission) : $this->isDoctor($actor)), 403);
        $id = Medico::query()->where('usuario_id', $actor->id)->where('activo', true)->value('id');
        abort_unless($id, 403, 'Se requiere un médico activo vinculado a la cuenta.');

        return (int) $id;
    }

    /** Solo usa el contrato de tablas de CU-15, sin crear ni modificar consultas. */
    public function consultations(): QueryBuilder
    {
        return DB::table('consultas_medicas as cm')
            ->join('citas_medicas as c', 'c.id', '=', 'cm.cita_id')
            ->join('historias_clinicas as h', 'h.id', '=', 'cm.historia_id')
            ->join('pacientes as p', 'p.id', '=', 'h.paciente_id')
            ->join('medicos as m', 'm.id', '=', 'cm.medico_id')
            ->whereColumn('c.medico_id', 'cm.medico_id')
            ->whereColumn('c.paciente_id', 'h.paciente_id')
            ->whereIn('c.estado', ['en_curso', 'completada', 'atendida'])
            ->where('c.inicio', '<=', now())
            ->where('cm.fecha_atencion', '<=', now())
            ->select(['cm.id', 'cm.fecha_atencion', 'cm.medico_id', 'p.id as paciente_id', 'p.nombres', 'p.apellidos', 'p.codigo', 'c.codigo as cita_codigo']);
    }

    public function visible(User $actor): Builder
    {
        $consultations = $this->consultations();
        if ($this->isDoctor($actor)) {
            $consultations->where('cm.medico_id', $this->doctorId($actor));
        } else {
            abort_unless($actor->fresh()?->activo && $actor->tienePermiso('estudios.propios'), 403);
            abort_unless($actor->cumpleVerificacionParaAcceso(), redirect()->route('verification.notice'));
            $patientId = Paciente::query()->where('usuario_id', $actor->id)->value('id');
            abort_unless($patientId, 403);
            $consultations->where('p.id', $patientId);
        }

        return SolicitudEstudio::query()->whereIn('consulta_id', $consultations->reorder()->select('cm.id'));
    }

    public function findVisible(User $actor, int $id): SolicitudEstudio
    {
        $query = $this->visible($actor);
        $query->with(['resultado' => function (HasOne $query) use ($actor): void {
            if (! $this->isDoctor($actor)) {
                $query->where('estado_publicacion', 'publicado');
            }
        }]);

        return $query->findOrFail($id);
    }

    public function request(User $actor, array $data): SolicitudEstudio
    {
        return DB::transaction(function () use ($actor, $data): SolicitudEstudio {
            $doctorId = $this->doctorId($actor, 'estudios.solicitar');
            $consultation = $this->consultations()->where('cm.medico_id', $doctorId)
                ->where('cm.id', $data['consulta_id'])->lockForUpdate()->first();
            abort_unless($consultation, 404);
            $study = SolicitudEstudio::query()->create([
                'consulta_id' => $consultation->id, 'nombre_estudio' => $data['nombre_estudio'],
                'indicaciones' => $data['indicaciones'] ?? null, 'estado' => 'solicitado', 'fecha_solicitud' => now(),
            ]);
            $this->audit($study, $actor, 'SOLICITAR');

            return $study;
        });
    }

    public function saveResult(User $actor, int $id, array $data, ?UploadedFile $file): SolicitudEstudio
    {
        $newPath = null;
        try {
            return DB::transaction(function () use ($actor, $id, $data, $file, &$newPath): SolicitudEstudio {
                $study = $this->lockOwned($actor, $id, $data['version'], 'estudios.publicar');
                $result = $study->resultado;
                if ($data['accion'] === 'retirar') {
                    $this->assert($result?->estado_publicacion === 'publicado', 'accion', 'Solo se puede retirar un resultado publicado.');
                    $result->update(['estado_publicacion' => 'retirado']);
                    $study->update(['estado' => 'en_proceso']);
                    $this->audit($study, $actor, 'RETIRAR', $data['motivo']);

                    return $study;
                }
                $this->assert(in_array($study->estado, ['solicitado', 'en_proceso'], true), 'accion', 'El estudio está cerrado. Retira el resultado publicado antes de corregirlo.');
                $this->assert($result?->estado_publicacion !== 'publicado', 'accion', 'Un resultado publicado no se modifica directamente.');
                if ($file) {
                    $newPath = $file->store('', 'estudios');
                }
                $description = $data['descripcion'] ?? null;
                $path = $newPath ?? $result?->archivo_privado;
                $this->assert(filled($description) || $path !== null, 'descripcion', 'Ingresa una descripción o adjunta un PDF.');
                $published = $data['accion'] === 'publicar';
                ResultadoEstudio::query()->updateOrCreate(['solicitud_id' => $study->id], [
                    'descripcion' => $description, 'archivo_privado' => $path,
                    'estado_publicacion' => $published ? 'publicado' : 'borrador',
                    'publicado_por' => $published ? $actor->id : null,
                    'fecha_publicacion' => $published ? now() : null,
                ]);
                $study->update(['estado' => $published ? 'completado' : 'en_proceso']);
                $eventId = $this->audit($study, $actor, $published ? 'PUBLICAR' : 'GUARDAR_BORRADOR');
                if ($published) {
                    $patientId = $this->consultations()->where('cm.id', $study->consulta_id)->first()?->paciente_id;
                    if ($patientId) {
                        app(ResultNotificationManagement::class)->enqueue($study, $eventId, (int) $patientId);
                    }
                }

                return $study;
            });
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                Storage::disk('estudios')->delete($newPath);
            }
            throw $exception;
        }
    }

    public function changeState(User $actor, int $id, array $data): void
    {
        DB::transaction(function () use ($actor, $id, $data): void {
            $study = $this->lockOwned($actor, $id, $data['version'], 'estudios.solicitar');
            $this->assert(in_array($study->estado, ['solicitado', 'en_proceso'], true), 'estado', 'El estudio ya está cerrado.');
            $this->assert($data['estado'] === 'cancelado' || $study->estado === 'solicitado', 'estado', 'El estudio ya está en proceso.');
            $study->update(['estado' => $data['estado']]);
            $this->audit($study, $actor, $data['estado'] === 'cancelado' ? 'CANCELAR' : 'INICIAR_PROCESO', $data['motivo'] ?? null);
        });
    }

    private function lockOwned(User $actor, int $id, string $version, string $permission): SolicitudEstudio
    {
        $doctorId = $this->doctorId($actor, $permission);
        $study = SolicitudEstudio::query()->lockForUpdate()->findOrFail($id);
        abort_unless($this->consultations()->where('cm.medico_id', $doctorId)->where('cm.id', $study->consulta_id)->lockForUpdate()->first(), 404);
        $study->load('resultado');
        $this->assert(hash_equals($study->editVersion(), $version), 'version', 'El estudio cambió. Recarga la página antes de guardar.');

        return $study;
    }

    private function assert(bool $condition, string $field, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function audit(SolicitudEstudio $study, User $actor, string $action, ?string $reason = null): int
    {
        return DB::table('eventos_estudio')->insertGetId([
            'solicitud_id' => $study->id, 'usuario_id' => $actor->id, 'accion' => $action,
            'motivo' => $reason, 'fecha' => now(),
        ]);
    }
}
