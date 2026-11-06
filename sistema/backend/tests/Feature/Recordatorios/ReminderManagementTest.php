<?php

namespace Tests\Feature\Recordatorios;

use App\Models\CitaMedica;
use App\Models\Paciente;
use App\Models\User;
use App\Notifications\AppointmentReminder;
use App\Services\Recordatorios\ReminderManagement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class ReminderManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-06 09:00:00', 'America/La_Paz'));
        Notification::fake();
        config(['mail.default' => 'smtp']);
    }

    public function test_preferences_require_session_permission_verified_email_and_linked_patient(): void
    {
        $this->get('/mi-cuenta/recordatorios')->assertRedirect(route('login'));
        $this->put('/mi-cuenta/recordatorios', [])->assertRedirect(route('login'));
        foreach (['administrador', 'recepcion', 'medico'] as $role) {
            $this->signIn($this->accountWithRole($role));
            $this->get('/mi-cuenta/recordatorios')->assertForbidden();
            $this->put('/mi-cuenta/recordatorios', [])->assertForbidden();
        }
        $user = $this->accountWithRole('paciente');
        $this->signIn($user);
        $this->get('/mi-cuenta/recordatorios')->assertRedirect(route('verification.notice'));
        $this->put('/mi-cuenta/recordatorios', [
            'recordatorios_activos' => '1', 'anticipacion_horas' => '24',
            'incluir_detalles' => '0', 'version' => str_repeat('0', 64),
        ])->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('preferencias_paciente', 0);
        $user->markEmailAsVerified();
        $this->get('/mi-cuenta/recordatorios')->assertNotFound();
    }

    public function test_patient_saves_only_own_preferences_and_preserves_other_privacy_settings(): void
    {
        [$user, $patient] = $this->context();
        DB::table('preferencias_paciente')->insert(['paciente_id' => $patient->id, 'preferencias_privacidad' => json_encode(['otra_opcion' => true])]);
        $this->signIn($user);
        $this->get('/mi-cuenta/recordatorios')->assertOk()->assertSee($user->correo);
        $other = Paciente::factory()->create();
        $this->put('/mi-cuenta/recordatorios', $this->payload($patient, ['paciente_id' => $other->id, 'canal_preferido' => 'sms']))->assertSessionHasNoErrors()->assertRedirect(route('recordatorios.edit'));
        $this->assertDatabaseHas('preferencias_paciente', ['paciente_id' => $patient->id, 'recordatorios_activos' => true, 'anticipacion_horas' => 24, 'canal_preferido' => 'email']);
        $this->assertDatabaseMissing('preferencias_paciente', ['paciente_id' => $other->id]);
        $privacy = json_decode($this->service()->preferences($patient->id)->preferencias_privacidad, true);
        $this->assertTrue($privacy['otra_opcion']);
        $this->assertFalse($privacy['incluir_detalles_correo']);
        Notification::assertNothingSent();
    }

    public function test_defaults_do_not_send_until_preferences_are_explicitly_enabled(): void
    {
        [$user, $patient] = $this->context();
        $this->appointment($patient);
        $this->signIn($user);
        $this->get('/mi-cuenta/recordatorios')->assertOk()->assertViewHas('preferences', null);
        $this->service()->run();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('recordatorios_cita', 0);
    }

    public function test_invalid_or_stale_preferences_do_not_overwrite_current_settings(): void
    {
        [$user, $patient] = $this->context();
        $this->signIn($user);
        foreach ([
            [['anticipacion_horas' => 3], 'anticipacion_horas'], [['anticipacion_horas' => []], 'anticipacion_horas'],
            [['recordatorios_activos' => 'si'], 'recordatorios_activos'], [['incluir_detalles' => []], 'incluir_detalles'],
            [['version' => 'incorrecta'], 'version'],
        ] as [$changes, $field]) {
            $this->put('/mi-cuenta/recordatorios', $this->payload($patient, $changes))->assertSessionHasErrors($field);
        }
        $stale = $this->payload($patient);
        $this->put('/mi-cuenta/recordatorios', $stale)->assertSessionHasNoErrors();
        $this->put('/mi-cuenta/recordatorios', array_replace($stale, ['recordatorios_activos' => '0']))->assertSessionHasErrors('version');
        $this->assertTrue((bool) $this->service()->preferences($patient->id)->recordatorios_activos);
    }

    public function test_exact_due_time_sends_once_to_verified_account_email(): void
    {
        [$user, $patient] = $this->context(true);
        $patient->update(['correo' => 'otro@example.test']);
        $appointment = $this->appointment($patient);
        $first = $this->service()->run();
        $second = $this->service()->run();
        $this->assertSame(1, $first['programados']);
        $this->assertSame(0, $second['programados']);
        Notification::assertSentToTimes($user, AppointmentReminder::class, 1);
        Notification::assertSentTo($user, AppointmentReminder::class, fn ($notification, $channels) => $notification->cita->id === $appointment->id && $channels === ['mail']);
        $this->assertDatabaseHas('recordatorios_cita', ['cita_id' => $appointment->id, 'estado' => 'enviado', 'fecha_envio' => now()]);
        $this->assertSame($user->correo, $user->routeNotificationForMail());
    }

    public function test_anticipation_controls_delivery_and_late_opt_in_is_allowed_before_start(): void
    {
        [$user, $patient] = $this->context(true);
        DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->update(['anticipacion_horas' => 2]);
        $appointment = $this->appointment($patient, ['inicio' => now()->addHours(3), 'fin' => now()->addHours(3)->addMinutes(30)]);
        $this->service()->run();
        Notification::assertNothingSent();
        $this->travel(1)->hours();
        $this->service()->run();
        Notification::assertSentToTimes($user, AppointmentReminder::class, 1);
        [$lateUser, $latePatient] = $this->context(true);
        $this->appointment($latePatient, ['inicio' => now()->addHour(), 'fin' => now()->addMinutes(90)]);
        $this->service()->run();
        Notification::assertSentToTimes($lateUser, AppointmentReminder::class, 1);
    }

    public function test_disabled_unverified_inactive_and_unlinked_accounts_do_not_receive_reminders(): void
    {
        foreach ([
            fn ($user, $patient) => DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->update(['recordatorios_activos' => false]),
            fn ($user, $patient) => $user->forceFill(['email_verified_at' => null])->save(),
            fn ($user, $patient) => $user->forceFill(['activo' => false])->save(),
            fn ($user, $patient) => $patient->update(['activo' => false]),
            fn ($user, $patient) => DB::table('pacientes')->where('id', $patient->id)->update(['usuario_id' => null]),
        ] as $disable) {
            [$user, $patient] = $this->context(true);
            $this->appointment($patient);
            $disable($user, $patient);
            $this->service()->run();
        }
        Notification::assertNothingSent();
        $this->assertDatabaseCount('recordatorios_cita', 0);
    }

    public function test_canceled_completed_and_started_appointments_are_never_sent(): void
    {
        foreach (['cancelada', 'completada', 'inasistencia', 'en_curso'] as $state) {
            [$user, $patient] = $this->context(true);
            $this->appointment($patient, ['estado' => $state]);
        }
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient, ['inicio' => now(), 'fin' => now()->addMinutes(30)]);
        $this->service()->run();
        Notification::assertNothingSent();
        $this->assertDatabaseCount('recordatorios_cita', 0);
    }

    public function test_pending_old_occurrence_is_omitted_and_rescheduling_gets_its_own_reminder(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $oldId = $this->pending($appointment);
        $appointment->update(['inicio' => now()->addHours(23), 'fin' => now()->addHours(23)->addMinutes(30)]);
        $this->service()->run();
        $this->assertDatabaseHas('recordatorios_cita', ['id' => $oldId, 'estado' => 'omitido']);
        $this->assertDatabaseHas('recordatorios_cita', ['cita_id' => $appointment->id, 'estado' => 'enviado', 'inicio_cita' => $appointment->inicio]);
        Notification::assertSentToTimes($user, AppointmentReminder::class, 1);
    }

    public function test_pending_reminder_respects_new_preferences_and_cancellation(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $id = $this->pending($appointment);
        DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->update(['anticipacion_horas' => 2]);
        $this->service()->run();
        $this->assertDatabaseHas('recordatorios_cita', ['id' => $id, 'estado' => 'pendiente', 'fecha_programada' => $appointment->inicio->subHours(2)]);
        Notification::assertNothingSent();
        $appointment->update(['estado' => 'cancelada']);
        $this->travel(23)->hours();
        $this->service()->run();
        $this->assertDatabaseHas('recordatorios_cita', ['id' => $id, 'estado' => 'omitido']);
        Notification::assertNothingSent();
    }

    public function test_failed_or_interrupted_attempts_are_not_automatically_retried(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('Dato privado que no debe guardarse'));
        $result = $this->service()->run();
        $this->assertSame(1, $result['fallidos']);
        $this->assertDatabaseHas('recordatorios_cita', ['cita_id' => $appointment->id, 'estado' => 'fallido']);
        $this->service()->run();
        DB::table('recordatorios_cita')->update(['estado' => 'enviando']);
        $this->service()->run();
        $this->assertDatabaseCount('recordatorios_cita', 1);
    }

    public function test_reenabling_preferences_can_resume_an_omitted_unsent_reminder(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $id = $this->pending($appointment);
        DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->update(['recordatorios_activos' => false]);
        $this->service()->run();
        $this->assertDatabaseHas('recordatorios_cita', ['id' => $id, 'estado' => 'omitido']);
        DB::table('preferencias_paciente')->where('paciente_id', $patient->id)->update(['recordatorios_activos' => true]);
        $this->service()->run();
        $this->service()->run();
        $this->assertDatabaseHas('recordatorios_cita', ['id' => $id, 'estado' => 'enviado']);
        $this->assertDatabaseCount('recordatorios_cita', 1);
        Notification::assertSentToTimes($user, AppointmentReminder::class, 1);
    }

    public function test_mail_content_respects_privacy_and_uses_bolivian_time(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $appointment->update(['motivo' => 'MOTIVO PRIVADO']);
        $minimal = (new AppointmentReminder($appointment, false))->toMail($user);
        $content = implode(' ', $minimal->introLines).' '.implode(' ', $minimal->outroLines);
        $this->assertStringContainsString('07/11/2026', $content);
        $this->assertStringContainsString('09:00', $content);
        $this->assertStringNotContainsString('MOTIVO PRIVADO', $content);
        $this->assertStringNotContainsString($appointment->medico->apellidos, $content);
        $this->assertSame(route('citas.receipt', $appointment), $minimal->actionUrl);
        $detailed = (new AppointmentReminder($appointment, true))->toMail($user);
        $this->assertStringContainsString($appointment->medico->apellidos, implode(' ', $detailed->introLines));
        $this->assertStringContainsString($appointment->especialidad->nombre, implode(' ', $detailed->introLines));
    }

    public function test_dry_run_changes_nothing_and_log_mailer_is_recorded_as_simulated(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $this->artisan('clinica:enviar-recordatorios', ['--simular' => true])->expectsOutputToContain('Simulación. Programados: 1')->assertSuccessful();
        $this->assertDatabaseCount('recordatorios_cita', 0);
        Notification::assertNothingSent();
        config(['mail.default' => 'log']);
        $this->artisan('clinica:enviar-recordatorios')->assertSuccessful();
        $this->assertDatabaseHas('recordatorios_cita', ['cita_id' => $appointment->id, 'estado' => 'simulado']);
    }

    public function test_patient_history_is_private_and_paginated_with_responsive_labels(): void
    {
        [$user, $patient] = $this->context(true);
        $appointment = $this->appointment($patient);
        $this->pending($appointment);
        [$otherUser, $otherPatient] = $this->context(true);
        $foreign = $this->appointment($otherPatient);
        $this->pending($foreign);
        for ($index = 1; $index <= 12; $index++) {
            DB::table('recordatorios_cita')->insert([
                'cita_id' => $appointment->id, 'canal' => 'email',
                'inicio_cita' => $appointment->inicio->addDays($index),
                'fecha_programada' => now(), 'estado' => 'omitido',
            ]);
        }
        $this->signIn($user);
        $response = $this->get('/mi-cuenta/recordatorios')->assertOk()->assertSee($appointment->codigo)->assertDontSee($foreign->codigo);
        $this->assertStringContainsString('table-cell-label', $response->getContent());
        $response->assertViewHas('history', fn ($history) => $history->total() === 13 && $history->count() === 10);
        if (getenv('CU17_PREVIEW') === '1') {
            File::ensureDirectoryExists(public_path('build/cu17-preview'));
            File::put(public_path('build/cu17-preview/index.html'), $response->getContent());
        }
    }

    public function test_revoked_effective_permission_blocks_saving_and_delivery(): void
    {
        [$user, $patient] = $this->context(true);
        $this->appointment($patient);
        $this->signIn($user);
        DB::table('permisos')->where('codigo', 'recordatorios.propios')->update(['activo' => false]);
        $this->put('/mi-cuenta/recordatorios', $this->payload($patient))->assertForbidden();
        $this->service()->run();
        Notification::assertNothingSent();
    }

    /** @return array{User, Paciente} */
    private function context(bool $enabled = false): array
    {
        $user = $this->accountWithRole('paciente');
        $user->markEmailAsVerified();
        $patient = Paciente::factory()->create(['usuario_id' => $user->id]);
        if ($enabled) {
            $this->service()->save($user, $this->payload($patient));
        }

        return [$user, $patient];
    }

    private function service(): ReminderManagement
    {
        return app(ReminderManagement::class);
    }

    private function appointment(Paciente $patient, array $changes = []): CitaMedica
    {
        return $this->createAppointment(array_replace([
            'paciente_id' => $patient->id, 'inicio' => now()->addDay(), 'fin' => now()->addDay()->addMinutes(30),
        ], $changes));
    }

    private function pending(CitaMedica $appointment): int
    {
        return DB::table('recordatorios_cita')->insertGetId([
            'cita_id' => $appointment->id, 'canal' => 'email', 'inicio_cita' => $appointment->inicio,
            'fecha_programada' => now(), 'estado' => 'pendiente',
        ]);
    }

    private function payload(Paciente $patient, array $changes = []): array
    {
        return array_replace([
            'version' => $this->service()->version($this->service()->preferences($patient->id)),
            'recordatorios_activos' => '1', 'anticipacion_horas' => '24', 'incluir_detalles' => '0',
        ], $changes);
    }

    private function signIn(User $user): void
    {
        $this->actingAs($user)->withSession([
            'auth_last_activity' => now()->timestamp, 'auth_password_hash' => $user->getAuthPassword(),
            'auth_session_token' => $user->remember_token ?? '',
        ]);
    }
}
