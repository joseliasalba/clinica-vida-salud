<?php

namespace Tests\Feature\Citas;

use App\Models\CitaMedica;
use App\Services\Citas\AsistenciaService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class AsistenciaTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    public function test_session_and_permission_are_required_for_every_action(): void
    {
        $cita = $this->createAppointment();
        $this->get($this->url($cita))->assertRedirect(route('login'));
        $this->patch($this->url($cita), $this->payload($cita))->assertRedirect(route('login'));
        $paciente = $this->accountWithRole('paciente');
        $this->post('/login', ['identificador' => $paciente->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->get($this->url($cita))->assertForbidden();
        $this->patch($this->url($cita), $this->payload($cita))->assertForbidden();
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_administrator_and_reception_can_confirm_a_scheduled_appointment(): void
    {
        foreach (['administrador', 'recepcion'] as $role) {
            $actor = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
            $cita = $this->createAppointment();
            $this->get($this->url($cita))->assertOk()->assertSee($cita->codigo)->assertSee('Confirmar cambio de estado');
            $this->patch($this->url($cita), $this->payload($cita))->assertSessionHasNoErrors()->assertRedirect(route('citas.index'));
            $this->assertSame('confirmada', $cita->fresh()->estado);
            $this->assertDatabaseHas('cambios_cita', ['cita_id' => $cita->id, 'usuario_id' => $actor->id, 'accion' => 'CAMBIAR_ESTADO']);
            $this->post('/logout');
        }
    }

    public function test_a_doctor_can_update_only_appointments_assigned_to_their_account(): void
    {
        $actor = $this->accountWithRole('medico');
        $own = $this->createAppointment(['inicio' => now()->subHour(), 'fin' => now()->subMinutes(30)]);
        $other = $this->createAppointment();
        DB::table('medicos')->where('id', $own->medico_id)->update(['usuario_id' => $actor->id]);
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->get($this->url($other))->assertForbidden();
        $this->patch($this->url($other), $this->payload($other))->assertForbidden();
        $this->get($this->url($own))->assertOk();
        $this->patch($this->url($own), $this->payload($own, ['estado' => 'completada']))->assertSessionHasNoErrors()->assertRedirect(route('citas.index'));
        $this->assertSame('completada', $own->fresh()->estado);
        $this->assertSame('programada', $other->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 1);
    }

    public function test_started_appointments_can_be_attended_or_marked_absent_and_audited(): void
    {
        $actor = $this->loginManager();
        foreach (['completada', 'inasistencia'] as $estado) {
            $cita = $this->createAppointment(['estado' => 'confirmada', 'inicio' => now()->subHour(), 'fin' => now()->subMinutes(30)]);
            $before = $cita->snapshot();
            $this->patch($this->url($cita), $this->payload($cita, ['estado' => $estado, 'motivo' => 'Resultado verificado']))->assertSessionHasNoErrors()->assertRedirect(route('citas.index'));
            $this->assertSame($estado, $cita->fresh()->estado);
            $audit = DB::table('cambios_cita')->where('cita_id', $cita->id)->sole();
            $this->assertSame($actor->id, $audit->usuario_id);
            $this->assertSame('CAMBIAR_ESTADO', $audit->accion);
            $this->assertSame('Resultado verificado', $audit->motivo);
            $this->assertSame($before['estado'], json_decode($audit->datos_anteriores, true)['estado']);
            $this->assertSame($estado, json_decode($audit->datos_nuevos, true)['estado']);
        }
    }

    public function test_future_appointments_cannot_be_closed_and_only_offer_confirmation(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment(['inicio' => now()->addDay(), 'fin' => now()->addDay()->addMinutes(30)]);
        $this->get($this->url($cita))->assertOk()->assertViewHas('estados', ['confirmada' => 'Confirmada']);
        foreach (['completada', 'inasistencia'] as $estado) {
            $this->patch($this->url($cita), $this->payload($cita, ['estado' => $estado]))->assertSessionHasErrors('estado');
        }
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_attendance_is_allowed_exactly_at_start_time_in_bolivia(): void
    {
        $this->travelTo(now('America/La_Paz')->setTime(9, 0));
        $this->loginManager();
        $cita = $this->createAppointment(['inicio' => now(), 'fin' => now()->addMinutes(30)]);
        $this->patch($this->url($cita), $this->payload($cita, ['estado' => 'completada']))->assertSessionHasNoErrors();
        $this->assertSame('completada', $cita->fresh()->estado);
    }

    public function test_terminal_appointments_cannot_be_reopened_or_modified(): void
    {
        $this->loginManager();
        foreach (['completada', 'atendida', 'cancelada', 'inasistencia'] as $actual) {
            $cita = $this->createAppointment(['estado' => $actual, 'inicio' => now()->subHour(), 'fin' => now()->subMinutes(30)]);
            $this->get($this->url($cita))->assertOk()->assertViewHas('estados', [])->assertDontSee('Confirmar cambio de estado');
            foreach (['confirmada', 'completada', 'inasistencia'] as $estado) {
                $this->patch($this->url($cita), $this->payload($cita, ['estado' => $estado]))->assertSessionHasErrors('estado');
            }
            $this->assertSame($actual, $cita->fresh()->estado);
        }
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_an_in_progress_appointment_can_only_be_completed(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment(['estado' => 'en_curso', 'inicio' => now()->subHour(), 'fin' => now()->subMinutes(30)]);
        $this->get($this->url($cita))->assertOk()->assertViewHas('estados', ['completada' => 'Atendida']);
        foreach (['confirmada', 'inasistencia'] as $estado) {
            $this->patch($this->url($cita), $this->payload($cita, ['estado' => $estado]))->assertSessionHasErrors('estado');
        }
        $this->patch($this->url($cita), $this->payload($cita, ['estado' => 'completada']))->assertSessionHasNoErrors();
        $this->assertSame('completada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 1);
    }

    public function test_invalid_targets_versions_observations_and_missing_confirmation_do_not_mutate(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        foreach ([
            [['estado' => 'cancelada'], 'estado'], [['estado' => 'atendida'], 'estado'],
            [['estado' => []], 'estado'], [['version' => null], 'version'],
            [['version' => []], 'version'], [['version' => 'otra'], 'version'],
            [['motivo' => []], 'motivo'], [['motivo' => str_repeat('a', 256)], 'motivo'],
            [['confirmacion' => null], 'confirmacion'], [['confirmacion' => '0'], 'confirmacion'],
        ] as [$changes, $field]) {
            $this->patch($this->url($cita), $this->payload($cita, $changes))->assertSessionHasErrors($field);
        }
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_stale_confirmation_does_not_overwrite_rescheduling_or_add_history(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $data = $this->payload($cita);
        $cita->inicio = $cita->inicio->addHour();
        $cita->fin = $cita->fin->addHour();
        $cita->save();
        $this->followingRedirects()->from($this->url($cita))->patch($this->url($cita), $data)->assertOk()->assertSee('Recargar la cita actualizada');
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_effective_permissions_and_doctor_assignment_are_rechecked_at_submission(): void
    {
        $actor = $this->accountWithRole('medico');
        $cita = $this->createAppointment();
        DB::table('medicos')->where('id', $cita->medico_id)->update(['usuario_id' => $actor->id]);
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->get($this->url($cita))->assertOk();
        DB::table('medicos')->where('id', $cita->medico_id)->update(['usuario_id' => null]);
        $this->patch($this->url($cita), $this->payload($cita))->assertForbidden();
        DB::table('medicos')->where('id', $cita->medico_id)->update(['usuario_id' => $actor->id]);
        DB::table('permisos')->where('codigo', 'agenda.propia')->update(['activo' => false]);
        $this->patch($this->url($cita), $this->payload($cita))->assertForbidden();
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_service_rechecks_revoked_administrative_permission(): void
    {
        $actor = $this->loginManager();
        $cita = $this->createAppointment();
        DB::table('permisos')->where('codigo', 'citas.gestionar')->update(['activo' => false]);
        $this->get($this->url($cita))->assertForbidden();
        $this->patch($this->url($cita), $this->payload($cita))->assertForbidden();
        try {
            app(AsistenciaService::class)->changeState($actor, $cita->id, $this->payload($cita));
            $this->fail('El servicio debe rechazar permisos revocados.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_replayed_submission_is_rejected_and_protected_fields_are_ignored(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $inicio = $cita->inicio->toDateTimeString();
        $data = $this->payload($cita, ['paciente_id' => 9999999, 'medico_id' => 9999999, 'inicio' => '2000-01-01', 'motivo_cancelacion' => 'forzado']);
        $this->patch($this->url($cita), $data)->assertSessionHasNoErrors();
        $this->patch($this->url($cita), $data)->assertSessionHasErrors('version');
        $this->assertSame($inicio, $cita->fresh()->inicio->toDateTimeString());
        $this->assertNull($cita->fresh()->motivo_cancelacion);
        $this->assertDatabaseCount('cambios_cita', 1);
    }

    public function test_csrf_protection_rejects_changes_without_a_token(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->patch($this->url($cita), $this->payload($cita))->assertStatus(419);
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_unknown_appointments_return_not_found_and_patient_text_is_escaped(): void
    {
        $this->loginManager();
        $this->get('/citas/999999999/asistencia')->assertNotFound();
        $this->patch('/citas/999999999/asistencia', [])->assertNotFound();
        $cita = $this->createAppointment();
        DB::table('pacientes')->where('id', $cita->paciente_id)->update(['nombres' => '<script>alert(1)</script>']);
        $this->get($this->url($cita))->assertOk()->assertSee(e('<script>alert(1)</script>'), false)->assertDontSee('<script>alert(1)</script>', false);
    }

    private function url(CitaMedica $cita): string
    {
        return '/citas/'.$cita->id.'/asistencia';
    }

    /** @return array<string, mixed> */
    private function payload(CitaMedica $cita, array $changes = []): array
    {
        return array_replace(['estado' => 'confirmada', 'version' => $cita->editVersion(), 'confirmacion' => '1'], $changes);
    }
}
