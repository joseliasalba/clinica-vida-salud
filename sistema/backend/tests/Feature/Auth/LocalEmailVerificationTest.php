<?php

namespace Tests\Feature\Auth;

use App\Models\Paciente;
use App\Models\SolicitudEstudio;
use App\Models\User;
use App\Services\Citas\AppointmentBooking;
use App\Services\Recordatorios\ReminderManagement;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Request;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class LocalEmailVerificationTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    protected function tearDown(): void
    {
        Request::setTrustedHosts([]);
        parent::tearDown();
    }

    public function test_bypass_requires_local_environment_an_explicit_flag_and_no_vercel_deployment(): void
    {
        $user = User::factory()->create();
        foreach ([
            ['local', false, false, false], ['testing', true, false, false],
            ['production', true, false, false], ['staging', true, false, false],
            ['local', true, true, false], ['local', true, false, true],
        ] as [$environment, $enabled, $vercel, $allowed]) {
            $this->app->instance('env', $environment);
            config(['seguridad.omitir_verificacion_local' => $enabled, 'seguridad.despliegue_vercel' => $vercel]);
            $this->assertSame($allowed, $user->cumpleVerificacionParaAcceso());
            $this->assertFalse($user->hasVerifiedEmail());
            $this->assertNull($user->fresh()->email_verified_at);
        }
    }

    public function test_local_patient_can_open_studies_preferences_and_booking_without_marking_email_verified(): void
    {
        Notification::fake();
        $user = $this->accountWithRole('paciente');
        $study = SolicitudEstudio::factory()->create();
        $consultation = DB::table('consultas_medicas')->find($study->consulta_id);
        $patientId = DB::table('historias_clinicas')->where('id', $consultation->historia_id)->value('paciente_id');
        Paciente::query()->whereKey($patientId)->update(['usuario_id' => $user->id]);
        $this->enableLocalBypass();
        $this->actingAs($user)->withSession([
            'auth_last_activity' => now()->timestamp, 'auth_password_hash' => $user->getAuthPassword(),
            'auth_session_token' => $user->remember_token ?? '',
        ]);
        $this->get('/estudios')->assertOk();
        $this->get('/estudios/'.$study->id)->assertOk();
        $this->get('/mi-cuenta/recordatorios')->assertOk();
        $this->get('/citas')->assertOk();
        $this->assertSame($patientId, app(AppointmentBooking::class)->patient($user, null)->id);
        $reminders = app(ReminderManagement::class);
        $reminders->save($user, [
            'version' => $reminders->version(null), 'recordatorios_activos' => '1',
            'anticipacion_horas' => 24, 'incluir_detalles' => '0',
        ]);
        $this->createAppointment(['paciente_id' => $patientId, 'inicio' => now()->addHour(), 'fin' => now()->addMinutes(90)]);
        $reminders->run();
        Notification::assertNothingSent();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->app->instance('env', 'production');
        $this->get('/mi-cuenta/recordatorios')->assertRedirect(route('verification.notice'));
        $this->get('/estudios')->assertRedirect(route('verification.notice'));
        $this->get('/citas')->assertRedirect(route('verification.notice'));
    }

    private function enableLocalBypass(): void
    {
        $this->app->instance('env', 'local');
        config(['seguridad.omitir_verificacion_local' => true, 'seguridad.despliegue_vercel' => false]);
    }
}
