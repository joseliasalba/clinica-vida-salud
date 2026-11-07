<?php

namespace App\Services\Recordatorios;

use App\Models\CitaMedica;
use App\Models\Paciente;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use stdClass;
use Throwable;

class ReminderManagement
{
    public const ESTADOS = [
        'pendiente' => 'Pendiente', 'enviando' => 'Envío en curso o sin confirmar',
        'enviado' => 'Aceptado por el servicio de correo', 'fallido' => 'Envío no confirmado',
        'omitido' => 'Omitido', 'simulado' => 'Simulado, sin entrega de correo',
    ];

    public function patient(User $actor): Paciente
    {
        abort_unless($actor->fresh()?->activo && $actor->tienePermiso('recordatorios.propios'), 403);
        abort_unless($actor->cumpleVerificacionParaAcceso(), redirect()->route('verification.notice'));

        return Paciente::query()->where('usuario_id', $actor->id)->where('activo', true)->firstOrFail();
    }

    public function preferences(int $patientId): ?stdClass
    {
        return DB::table('preferencias_paciente')->where('paciente_id', $patientId)->first();
    }

    public function version(?stdClass $preferences): string
    {
        return hash_hmac('sha256', json_encode([
            $preferences?->paciente_id, (bool) ($preferences?->recordatorios_activos ?? false),
            $preferences?->canal_preferido, $preferences?->anticipacion_horas ?? 24,
            (bool) ($preferences?->avisos_resultados_activos ?? false),
            $preferences?->preferencias_privacidad,
        ], JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public function details(?stdClass $preferences): bool
    {
        return (bool) (json_decode($preferences?->preferencias_privacidad ?? '{}', true)['incluir_detalles_correo'] ?? false);
    }

    public function save(User $actor, array $data): void
    {
        DB::transaction(function () use ($actor, $data): void {
            $patient = $this->patient($actor);
            Paciente::query()->whereKey($patient->id)->lockForUpdate()->firstOrFail();
            $preferences = DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->lockForUpdate()->first();
            if (! hash_equals($this->version($preferences), $data['version'])) {
                throw ValidationException::withMessages(['version' => 'Tus preferencias cambiaron. Recarga la página antes de guardar.']);
            }
            $privacy = json_decode($preferences?->preferencias_privacidad ?? '{}', true) ?? [];
            $privacy['incluir_detalles_correo'] = (bool) $data['incluir_detalles'];
            DB::table('preferencias_paciente')->updateOrInsert(['paciente_id' => $patient->id], [
                'recordatorios_activos' => (bool) $data['recordatorios_activos'],
                'canal_preferido' => 'email', 'anticipacion_horas' => $data['anticipacion_horas'],
                'preferencias_privacidad' => json_encode($privacy, JSON_THROW_ON_ERROR),
                'avisos_resultados_activos' => (bool) ($data['avisos_resultados_activos'] ?? $preferences?->avisos_resultados_activos ?? false),
            ]);
        });
    }

    /** @return array{programados: int, procesados: int, fallidos: int} */
    public function run(bool $simulate = false): array
    {
        $counts = ['programados' => 0, 'procesados' => 0, 'fallidos' => 0];
        CitaMedica::query()->whereIn('estado', ['programada', 'confirmada'])
            ->where('inicio', '>', now())->where('inicio', '<=', now()->addHours(48))
            ->chunkById(100, function ($appointments) use (&$counts, $simulate): void {
                foreach ($appointments as $appointment) {
                    if (! $this->recipient($appointment)) {
                        continue;
                    }
                    $preferences = $this->preferences($appointment->paciente_id);
                    $due = $appointment->inicio->subHours($preferences->anticipacion_horas);
                    if ($due->isFuture()) {
                        continue;
                    }
                    $existing = DB::table('recordatorios_cita')->where('cita_id', $appointment->id)->where('inicio_cita', $appointment->inicio)->where('canal', 'email')->first();
                    if ($existing) {
                        if ($existing->estado === 'omitido') {
                            $counts['programados'] += $simulate ? 1 : DB::table('recordatorios_cita')
                                ->where('id', $existing->id)->where('estado', 'omitido')->update([
                                    'estado' => 'pendiente', 'fecha_programada' => $due, 'fecha_intento' => null,
                                ]);
                        }

                        continue;
                    }
                    if ($simulate) {
                        $counts['programados']++;

                        continue;
                    }
                    $counts['programados'] += DB::table('recordatorios_cita')->insertOrIgnore([
                        'cita_id' => $appointment->id, 'canal' => 'email', 'inicio_cita' => $appointment->inicio,
                        'fecha_programada' => $due, 'estado' => 'pendiente',
                    ]);
                }
            });
        if ($simulate) {
            return $counts;
        }

        DB::table('recordatorios_cita')->where('estado', 'pendiente')->where('canal', 'email')->where('fecha_programada', '<=', now())
            ->orderBy('id')->chunkById(100, function ($reminders) use (&$counts): void {
                foreach ($reminders as $reminder) {
                    if (! $this->claim($reminder)) {
                        continue;
                    }
                    try {
                        $this->deliver($reminder);
                        $counts['procesados']++;
                    } catch (Throwable $exception) {
                        DB::table('recordatorios_cita')->where('id', $reminder->id)->where('estado', 'enviando')->update(['estado' => 'fallido']);
                        Log::warning('recordatorio.envio_no_confirmado', ['recordatorio_id' => $reminder->id, 'tipo' => $exception::class]);
                        $counts['fallidos']++;
                    }
                }
            });

        return $counts;
    }

    private function recipient(CitaMedica $appointment): ?User
    {
        if (! in_array($appointment->estado, ['programada', 'confirmada'], true) || $appointment->inicio->isPast() || $appointment->inicio->equalTo(now())) {
            return null;
        }
        $patient = Paciente::query()->whereKey($appointment->paciente_id)->where('activo', true)->first();
        $user = $patient?->usuario_id ? User::query()->find($patient->usuario_id) : null;
        $preferences = $patient ? $this->preferences($patient->id) : null;
        if (! $user?->activo || ! $user->hasVerifiedEmail() || ! $user->tienePermiso('recordatorios.propios')
            || ! $preferences?->recordatorios_activos || $preferences->canal_preferido !== 'email') {
            return null;
        }

        return $user;
    }

    private function validOccurrence(CitaMedica $appointment, stdClass $reminder): bool
    {
        return $appointment->inicio->equalTo(Carbon::parse($reminder->inicio_cita));
    }

    private function claim(stdClass $reminder): bool
    {
        return DB::transaction(function () use ($reminder): bool {
            $appointment = CitaMedica::query()->lockForUpdate()->findOrFail($reminder->cita_id);
            $current = DB::table('recordatorios_cita')->where('id', $reminder->id)->lockForUpdate()->first();
            if ($current->estado !== 'pendiente') {
                return false;
            }
            if (! $this->recipient($appointment) || ! $this->validOccurrence($appointment, $current)) {
                DB::table('recordatorios_cita')->where('id', $current->id)->update(['estado' => 'omitido']);

                return false;
            }
            $due = $appointment->inicio->subHours($this->preferences($appointment->paciente_id)->anticipacion_horas);
            if ($due->isFuture()) {
                DB::table('recordatorios_cita')->where('id', $current->id)->update(['fecha_programada' => $due]);

                return false;
            }
            DB::table('recordatorios_cita')->where('id', $current->id)->update(['estado' => 'enviando', 'fecha_intento' => now()]);

            return true;
        });
    }

    /** La reserva del intento se confirma antes del envío externo; no se reintenta un resultado ambiguo. */
    private function deliver(stdClass $reminder): void
    {
        DB::transaction(function () use ($reminder): void {
            $appointment = CitaMedica::query()->lockForUpdate()->findOrFail($reminder->cita_id);
            $current = DB::table('recordatorios_cita')->where('id', $reminder->id)->lockForUpdate()->first();
            $recipient = $this->recipient($appointment);
            if (! $recipient || ! $this->validOccurrence($appointment, $current)) {
                DB::table('recordatorios_cita')->where('id', $current->id)->update(['estado' => 'omitido']);

                return;
            }
            $due = $appointment->inicio->subHours($this->preferences($appointment->paciente_id)->anticipacion_horas);
            if ($due->isFuture()) {
                DB::table('recordatorios_cita')->where('id', $current->id)->update(['estado' => 'pendiente', 'fecha_programada' => $due]);

                return;
            }
            $recipient->notifyNow(new AppointmentReminder($appointment, $this->details($this->preferences($appointment->paciente_id))));
            DB::table('recordatorios_cita')->where('id', $current->id)->update([
                'estado' => in_array(config('mail.default'), ['log', 'array'], true) ? 'simulado' : 'enviado',
                'fecha_envio' => now(),
            ]);
        });
    }
}
