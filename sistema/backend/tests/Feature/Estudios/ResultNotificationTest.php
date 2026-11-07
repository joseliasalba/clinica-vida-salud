<?php

namespace Tests\Feature\Estudios;

use App\Models\CitaMedica;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\SolicitudEstudio;
use App\Notifications\StudyResultAvailable;
use App\Services\Estudios\ResultNotificationManagement;
use App\Services\Estudios\StudyManagement;
use App\Services\Recordatorios\ReminderManagement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class ResultNotificationTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    private function context(bool $enabled = true, bool $verified = true): array
    {
        $patientUser = $this->accountWithRole('paciente');
        if ($verified) {
            $patientUser->forceFill(['email_verified_at' => now()])->save();
        }
        $doctor = $this->accountWithRole('medico');
        $study = SolicitudEstudio::factory()->create();
        $consultation = DB::table('consultas_medicas')->where('id', $study->consulta_id)->first();
        Medico::query()->whereKey($consultation->medico_id)->update(['usuario_id' => $doctor->id]);
        $appointment = CitaMedica::findOrFail($consultation->cita_id);
        $patient = Paciente::findOrFail($appointment->paciente_id);
        $patient->forceFill(['usuario_id' => $patientUser->id])->save();
        DB::table('preferencias_paciente')->insert(['paciente_id' => $patient->id, 'avisos_resultados_activos' => $enabled]);

        return [$doctor, $study, $patientUser, $patient];
    }

    private function publish($doctor, $study): void
    {
        $study = $study->fresh();
        app(StudyManagement::class)->saveResult($doctor, $study->id, [
            'version' => $study->editVersion(), 'accion' => 'publicar', 'descripcion' => 'RESULTADO CLÍNICO PRIVADO',
        ], null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['seguridad.avisos_resultados_habilitados' => true, 'mail.default' => 'smtp']);
    }

    public function test_only_opted_in_verified_patient_gets_one_generic_notice(): void
    {
        Notification::fake();
        [$doctor, $study, $patient] = $this->context();
        $this->publish($doctor, $study);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'usuario_id' => $patient->id, 'estado' => 'pendiente']);
        $notices = app(ResultNotificationManagement::class);
        $notices->run(10);
        $notices->run(10);
        Notification::assertSentToTimes($patient, StudyResultAvailable::class, 1);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'enviado']);
        $mail = (new StudyResultAvailable)->toMail($patient);
        $content = json_encode($mail->toArray(), JSON_UNESCAPED_UNICODE);
        $this->assertStringNotContainsString('RESULTADO CLÍNICO PRIVADO', $content);
        $this->assertStringNotContainsString($study->nombre_estudio, $content);
        $this->assertSame(route('inicio'), $mail->actionUrl);
    }

    public function test_no_notice_is_queued_without_consent_or_verified_email(): void
    {
        Notification::fake();
        config(['seguridad.omitir_verificacion_local' => true]);
        [$doctor, $study] = $this->context(false, true);
        $this->publish($doctor, $study);
        [$doctor2, $study2] = $this->context(true, false);
        $this->publish($doctor2, $study2);
        $this->assertDatabaseCount('avisos_resultados', 0);
        Notification::assertNothingSent();
    }

    public function test_drafts_and_rolled_back_publications_do_not_create_notices(): void
    {
        Notification::fake();
        [$doctor, $study] = $this->context();
        app(StudyManagement::class)->saveResult($doctor, $study->id, [
            'version' => $study->editVersion(), 'accion' => 'borrador', 'descripcion' => 'Borrador privado',
        ], null);
        $this->assertDatabaseCount('avisos_resultados', 0);
        try {
            DB::transaction(function () use ($doctor, $study): void {
                $this->publish($doctor, $study);
                throw new RuntimeException('Revertir');
            });
        } catch (RuntimeException) {
            $this->assertDatabaseCount('avisos_resultados', 0);
            $this->assertSame('en_proceso', $study->fresh()->estado);
        }
        Notification::assertNothingSent();
    }

    public function test_relinked_patient_account_does_not_receive_the_previous_notice(): void
    {
        Notification::fake();
        [$doctor, $study, $user, $patient] = $this->context();
        $this->publish($doctor, $study);
        $other = $this->accountWithRole('paciente');
        $other->forceFill(['email_verified_at' => now()])->save();
        $patient->forceFill(['usuario_id' => $other->id])->save();
        app(ResultNotificationManagement::class)->run(10);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'omitido']);
        Notification::assertNothingSent();
    }

    public function test_revoked_consent_or_withdrawn_result_is_not_sent(): void
    {
        Notification::fake();
        [$doctor, $study, $patient, $patientRecord] = $this->context();
        $this->publish($doctor, $study);
        DB::table('preferencias_paciente')->where('paciente_id', $patientRecord->id)->update(['avisos_resultados_activos' => false]);
        app(ResultNotificationManagement::class)->run(10);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'omitido']);
        [$doctor2, $study2] = $this->context();
        $this->publish($doctor2, $study2);
        app(StudyManagement::class)->saveResult($doctor2, $study2->id, ['version' => $study2->fresh()->editVersion(), 'accion' => 'retirar', 'motivo' => 'Corrección'], null);
        app(ResultNotificationManagement::class)->run(10);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study2->id, 'estado' => 'omitido']);
        Notification::assertNothingSent();
    }

    public function test_republication_suppresses_old_notice_and_sends_the_new_one(): void
    {
        Notification::fake();
        [$doctor, $study, $patient] = $this->context();
        $this->publish($doctor, $study);
        app(StudyManagement::class)->saveResult($doctor, $study->id, ['version' => $study->fresh()->editVersion(), 'accion' => 'retirar', 'motivo' => 'Corrección'], null);
        $this->publish($doctor, $study);
        app(ResultNotificationManagement::class)->run(10);
        $this->assertSame(['omitido', 'enviado'], DB::table('avisos_resultados')->where('solicitud_id', $study->id)->orderBy('id')->pluck('estado')->all());
        Notification::assertSentToTimes($patient, StudyResultAvailable::class, 1);
    }

    public function test_ambiguous_mail_failure_is_not_automatically_retried(): void
    {
        [$doctor, $study] = $this->context();
        $this->publish($doctor, $study);
        Notification::shouldReceive('sendNow')->once()->andThrow(new RuntimeException('timeout'));
        $service = app(ResultNotificationManagement::class);
        $this->assertSame(1, $service->run(10)['fallidos']);
        $this->assertSame(0, $service->run(10)['fallidos']);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'fallido']);
    }

    public function test_log_mailer_is_recorded_as_simulated(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        [$doctor, $study] = $this->context();
        $this->publish($doctor, $study);
        app(ResultNotificationManagement::class)->run(10);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'simulado']);
    }

    public function test_disabled_processor_keeps_the_notice_pending(): void
    {
        Notification::fake();
        [$doctor, $study] = $this->context();
        $this->publish($doctor, $study);
        config(['seguridad.avisos_resultados_habilitados' => false]);
        app(ResultNotificationManagement::class)->run(10);
        $this->assertDatabaseHas('avisos_resultados', ['solicitud_id' => $study->id, 'estado' => 'pendiente']);
        Notification::assertNothingSent();
    }

    public function test_patient_preference_is_opt_in_and_versioned(): void
    {
        [$doctor, $study, $user, $patient] = $this->context(false);
        $service = app(ReminderManagement::class);
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('recordatorios.edit'))->assertOk()->assertSee('Avisos de resultados');
        $version = $service->version($service->preferences($patient->id));
        $this->put(route('recordatorios.update'), ['version' => $version, 'recordatorios_activos' => '0', 'anticipacion_horas' => '24',
            'incluir_detalles' => '0', 'avisos_resultados_activos' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('preferencias_paciente', ['paciente_id' => $patient->id, 'avisos_resultados_activos' => true]);
        $this->put(route('recordatorios.update'), ['version' => $version, 'recordatorios_activos' => '0', 'anticipacion_horas' => '24',
            'incluir_detalles' => '0', 'avisos_resultados_activos' => '0'])->assertSessionHasErrors('version');
    }

    public function test_worker_endpoint_requires_secret_and_does_not_run_when_disabled(): void
    {
        config(['seguridad.avisos_resultados_secret' => str_repeat('a', 32), 'seguridad.avisos_resultados_habilitados' => false]);
        Artisan::shouldReceive('call')->never();
        $this->postJson(route('estudios.avisos.procesar'))->assertUnauthorized();
        $this->withToken(str_repeat('b', 32))->postJson(route('estudios.avisos.procesar'))->assertUnauthorized();
        $this->withToken(str_repeat('a', 32))->postJson(route('estudios.avisos.procesar'))->assertStatus(503);
    }

    public function test_worker_runs_only_one_notice_and_does_not_overlap(): void
    {
        config(['seguridad.avisos_resultados_secret' => str_repeat('a', 32), 'seguridad.avisos_resultados_habilitados' => true]);
        Artisan::shouldReceive('call')->once()->with('clinica:enviar-avisos-resultados', ['--limit' => 1])->andReturn(0);
        $this->withToken(str_repeat('a', 32))->postJson(route('estudios.avisos.procesar'))->assertOk()->assertJson(['status' => 'ok']);
        $lock = Cache::lock('cu20:avisos', 55);
        $this->assertTrue($lock->get());
        try {
            $this->withToken(str_repeat('a', 32))->postJson(route('estudios.avisos.procesar'))->assertStatus(409);
        } finally {
            $lock->release();
        }
    }
}
