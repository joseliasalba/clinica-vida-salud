<?php

namespace App\Services\Estudios;

use App\Models\Paciente;
use App\Models\SolicitudEstudio;
use App\Models\User;
use App\Notifications\StudyResultAvailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ResultNotificationManagement
{
    public function enqueue(SolicitudEstudio $study, int $eventId, int $patientId): void
    {
        $patient = Paciente::query()->whereKey($patientId)->where('activo', true)->lockForUpdate()->first();
        $user = $patient?->usuario_id ? User::query()->find($patient->usuario_id) : null;
        $preferences = $patient ? DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->first() : null;
        if (! $user?->activo || ! $user->hasVerifiedEmail() || ! $user->tienePermiso('estudios.propios') || ! $preferences?->avisos_resultados_activos) {
            return;
        }
        DB::table('avisos_resultados')->insertOrIgnore([
            'evento_estudio_id' => $eventId, 'solicitud_id' => $study->id,
            'paciente_id' => $patient->id, 'usuario_id' => $user->id, 'estado' => 'pendiente',
        ]);
    }

    /** @return array{procesados:int,fallidos:int} */
    public function run(int $limit): array
    {
        $counts = ['procesados' => 0, 'fallidos' => 0];
        if (! config('seguridad.avisos_resultados_habilitados')) {
            return $counts;
        }
        $ids = DB::table('avisos_resultados')->where('estado', 'pendiente')->orderBy('id')->limit($limit)->pluck('id');
        foreach ($ids as $id) {
            if (! $this->claim((int) $id)) {
                continue;
            }
            try {
                $this->deliver((int) $id);
                $counts['procesados']++;
            } catch (Throwable $error) {
                DB::table('avisos_resultados')->where('id', $id)->where('estado', 'enviando')->update(['estado' => 'fallido']);
                Log::warning('aviso_resultado.envio_no_confirmado', ['aviso_id' => $id, 'tipo' => $error::class]);
                $counts['fallidos']++;
            }
        }

        return $counts;
    }

    private function claim(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $notice = DB::table('avisos_resultados')->where('id', $id)->lockForUpdate()->first();
            if (! $notice || $notice->estado !== 'pendiente') {
                return false;
            }
            DB::table('avisos_resultados')->where('id', $id)->update(['estado' => 'enviando', 'fecha_intento' => now()]);

            return true;
        });
    }

    private function deliver(int $id): void
    {
        DB::transaction(function () use ($id): void {
            $snapshot = DB::table('avisos_resultados')->where('id', $id)->first();
            if (! $snapshot) {
                return;
            }
            $study = SolicitudEstudio::query()->whereKey($snapshot->solicitud_id)->lockForUpdate()->first();
            $patient = Paciente::query()->whereKey($snapshot->paciente_id)->lockForUpdate()->first();
            $user = User::query()->whereKey($snapshot->usuario_id)->lockForUpdate()->first();
            $preferences = DB::table('preferencias_paciente')->where('paciente_id', $snapshot->paciente_id)->lockForUpdate()->first();
            $notice = DB::table('avisos_resultados')->where('id', $id)->lockForUpdate()->first();
            $latestPublication = DB::table('eventos_estudio')->where('solicitud_id', $snapshot->solicitud_id)
                ->where('accion', 'PUBLICAR')->max('id');
            $published = $study && $study->estado === 'completado'
                && DB::table('resultados_estudio')->where('solicitud_id', $study->id)->where('estado_publicacion', 'publicado')->exists();
            if (! $notice || $notice->estado !== 'enviando') {
                return;
            }
            if (! $published || (int) $latestPublication !== (int) $notice->evento_estudio_id
                || ! $patient?->activo || $patient->usuario_id !== $notice->usuario_id
                || ! $user?->activo || ! $user->hasVerifiedEmail() || ! $user->tienePermiso('estudios.propios')
                || ! $preferences?->avisos_resultados_activos) {
                DB::table('avisos_resultados')->where('id', $id)->update(['estado' => 'omitido']);

                return;
            }
            $mailer = config('mail.default');
            if (! in_array($mailer, ['smtp', 'log', 'array'], true)) {
                throw new RuntimeException('El transporte no permite distinguir una simulación de una entrega.');
            }
            $user->notifyNow(new StudyResultAvailable);
            DB::table('avisos_resultados')->where('id', $id)->update([
                'estado' => in_array($mailer, ['log', 'array'], true) ? 'simulado' : 'enviado', 'fecha_envio' => now(),
            ]);
        }, 1);
    }
}
