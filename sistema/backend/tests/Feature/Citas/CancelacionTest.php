<?php

namespace Tests\Feature\Citas;

use App\Models\CitaMedica;
use App\Services\Citas\CancelacionService;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class CancelacionTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    public function test_cancellation_requires_a_session_and_effective_permission(): void
    {
        $cita = $this->createAppointment();
        $this->get($this->url($cita))->assertRedirect(route('login'));
        $this->patch($this->url($cita), $this->data($cita))->assertRedirect(route('login'));

        foreach (['medico', 'paciente'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
            $this->get($this->url($cita))->assertForbidden();
            $this->patch($this->url($cita), $this->data($cita))->assertForbidden();
            $this->post('/logout');
        }

        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_admin_can_review_without_mutation_and_cancel_with_audited_reason(): void
    {
        $actor = $this->loginManager();
        $cita = $this->createAppointment(['motivo' => 'Control anual']);
        $before = $cita->snapshot();
        $this->get($this->url($cita))->assertOk()->assertSee($cita->codigo)
            ->assertSee('Confirmar cancelación')->assertSee('name="confirmar"', false)->assertSee('name="_token"', false);
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);

        $this->patch($this->url($cita), $this->data($cita, ['motivo' => '  Paciente   solicitó cancelar  ', 'estado' => 'completada', 'paciente_id' => 999999999]))
            ->assertSessionHasNoErrors()->assertRedirect(route('citas.index'))->assertSessionHas('status');

        $cita->refresh();
        $this->assertSame('cancelada', $cita->estado);
        $this->assertSame('Paciente solicitó cancelar', $cita->motivo_cancelacion);
        $this->assertSame('Control anual', $cita->motivo);
        $this->assertSame($before['paciente_id'], $cita->paciente_id);
        $this->assertSame($before['inicio'], $cita->inicio->format('Y-m-d H:i:s'));
        $this->assertSame($before['fin'], $cita->fin->format('Y-m-d H:i:s'));
        $audit = DB::table('cambios_cita')->where('cita_id', $cita->id)->sole();
        $this->assertSame($actor->id, $audit->usuario_id);
        $this->assertSame('CANCELAR', $audit->accion);
        $this->assertSame('Paciente solicitó cancelar', $audit->motivo);
        $this->assertSame('programada', json_decode($audit->datos_anteriores, true)['estado']);
        $this->assertSame('cancelada', json_decode($audit->datos_nuevos, true)['estado']);
        $this->assertNotNull($audit->fecha_cambio);
    }

    public function test_reception_can_cancel_a_confirmed_appointment(): void
    {
        $user = $this->accountWithRole('recepcion');
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $cita = $this->createAppointment(['estado' => 'confirmada']);
        $this->get($this->url($cita))->assertOk();
        $this->patch($this->url($cita), $this->data($cita))->assertSessionHasNoErrors()->assertRedirect(route('citas.index'));
        $this->assertSame('cancelada', $cita->fresh()->estado);
    }

    public function test_in_progress_attended_cancelled_and_no_show_appointments_cannot_be_cancelled(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        foreach (['en_curso', 'completada', 'cancelada', 'inasistencia'] as $state) {
            $cita->update(['estado' => $state]);
            $this->get($this->url($cita))->assertStatus(409);
            $this->patchJson($this->url($cita), $this->data($cita))->assertUnprocessable()->assertJsonValidationErrors('estado');
            $this->assertSame($state, $cita->fresh()->estado);
        }
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_reason_version_and_explicit_confirmation_are_validated_on_server(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        foreach ([
            [['motivo' => null], 'motivo'],
            [['motivo' => '   '], 'motivo'],
            [['motivo' => []], 'motivo'],
            [['motivo' => str_repeat('x', 256)], 'motivo'],
            [['version' => null], 'version'],
            [['version' => []], 'version'],
            [['version' => 'invalid'], 'version'],
            [['confirmar' => null], 'confirmar'],
            [['confirmar' => '0'], 'confirmar'],
        ] as [$changes, $field]) {
            $this->patch($this->url($cita), $this->data($cita, $changes))->assertSessionHasErrors($field);
        }
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_a_stale_form_cannot_cancel_an_appointment_changed_by_another_operator(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $version = $cita->editVersion();
        $cita->update(['motivo' => 'Motivo actualizado por recepción']);
        $this->followingRedirects()->from($this->url($cita))->patch($this->url($cita), $this->data($cita, ['version' => $version]))
            ->assertOk()->assertSee('La cita cambió')->assertSee('Volver a la lista de citas');
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertSame('Motivo actualizado por recepción', $cita->fresh()->motivo);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_repeated_submission_does_not_duplicate_audit_or_change_the_cancel_reason(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $data = $this->data($cita);
        $this->patch($this->url($cita), $data)->assertSessionHasNoErrors();
        $this->patch($this->url($cita), array_replace($data, ['motivo' => 'Otro motivo']))->assertSessionHasErrors('version');
        $this->assertSame(1, DB::table('cambios_cita')->where('cita_id', $cita->id)->count());
        $this->assertSame('Solicitud del paciente', $cita->fresh()->motivo_cancelacion);
    }

    public function test_cancellation_preserves_the_row_and_releases_the_doctor_slot_in_postgresql(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->patch($this->url($cita), $this->data($cita))->assertSessionHasNoErrors();
        $replacement = $this->createAppointment([
            'medico_id' => $cita->medico_id, 'horario_id' => $cita->horario_id,
            'especialidad_id' => $cita->especialidad_id, 'inicio' => $cita->inicio, 'fin' => $cita->fin,
        ]);
        $this->assertDatabaseHas('citas_medicas', ['id' => $cita->id, 'estado' => 'cancelada']);
        $this->assertDatabaseHas('citas_medicas', ['id' => $replacement->id, 'estado' => 'programada']);
        $this->assertDatabaseHas('cambios_cita', ['cita_id' => $cita->id, 'accion' => 'CANCELAR']);
        $this->delete($this->url($cita))->assertStatus(405);
    }

    public function test_revoked_permission_is_checked_in_the_service_and_http_layer(): void
    {
        $actor = $this->loginManager();
        $cita = $this->createAppointment();
        DB::table('permisos')->where('codigo', 'citas.gestionar')->update(['activo' => false]);
        $this->patch($this->url($cita), $this->data($cita))->assertForbidden();

        try {
            app(CancelacionService::class)->cancelar($actor, $cita->id, $this->data($cita));
            $this->fail('El servicio debía rechazar el permiso revocado.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_csrf_is_required_for_cancellation(): void
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
        $this->patch($this->url($cita), $this->data($cita))->assertStatus(419);
        $this->assertSame('programada', $cita->fresh()->estado);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_cancellation_form_escapes_patient_and_specialty_names_and_retained_input(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $payload = '<script>alert(1)</script>';
        $cita->paciente->update(['nombres' => $payload]);
        $this->get($this->url($cita))->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
        $this->from($this->url($cita))->patch($this->url($cita), $this->data($cita, ['motivo' => $payload, 'confirmar' => '0']))->assertSessionHasErrors('confirmar');
        $this->get($this->url($cita))->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
    }

    public function test_unknown_and_non_numeric_identifiers_do_not_cancel_any_appointment(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->get('/citas/999999999/cancelar')->assertNotFound();
        $this->patch('/citas/999999999/cancelar', $this->data($cita))->assertNotFound();
        $this->get('/citas/no-numero/cancelar')->assertNotFound();
        $this->assertSame('programada', $cita->fresh()->estado);
    }

    private function url(CitaMedica $cita): string
    {
        return '/citas/'.$cita->id.'/cancelar';
    }

    /** @param array<string, mixed> $changes
     * @return array<string, mixed>
     */
    private function data(CitaMedica $cita, array $changes = []): array
    {
        return array_replace(['motivo' => 'Solicitud del paciente', 'version' => $cita->editVersion(), 'confirmar' => '1'], $changes);
    }
}
