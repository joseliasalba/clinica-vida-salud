<?php

namespace Tests\Feature\Citas;

use App\Models\CitaMedica;
use App\Models\User;
use App\Services\Citas\ReprogramacionDisponibilidadService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class ReprogramacionTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2030-05-06 08:00:00', 'America/La_Paz'));
    }

    public function test_reception_can_reprogram_a_confirmed_appointment_and_audit_both_reservations(): void
    {
        $actor = $this->accountWithRole('recepcion');
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect();
        $cita = $this->createAppointment(['estado' => 'confirmada']);
        $antes = $cita->snapshot();

        $this->put(route('citas.reprogramar.update', $cita), $this->data($cita, ['motivo' => 'Petición del paciente']))
            ->assertRedirect(route('citas.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('citas_medicas', [
            'id' => $cita->id, 'inicio' => '2030-05-07 10:00:00', 'fin' => '2030-05-07 10:30:00',
            'estado' => 'confirmada', 'paciente_id' => $cita->paciente_id, 'especialidad_id' => $cita->especialidad_id,
        ]);
        $cambio = DB::table('cambios_cita')->where('cita_id', $cita->id)->sole();
        $this->assertSame('REPROGRAMAR', $cambio->accion);
        $this->assertSame($actor->id, $cambio->usuario_id);
        $this->assertSame('Petición del paciente', $cambio->motivo);
        $this->assertEquals($antes, json_decode($cambio->datos_anteriores, true));
        $this->assertEquals($cita->fresh()->snapshot(), json_decode($cambio->datos_nuevos, true));
    }

    public function test_form_and_slot_selection_work_without_javascript(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->get(route('citas.reprogramar.edit', $cita))
            ->assertOk()->assertSee('Consultar turnos')->assertSee('Confirmar reprogramación')
            ->assertSee($cita->paciente->nombres)->assertSee('name="turno"', false);
        $data = $this->data($cita);
        unset($data['inicio'], $data['horario_id']);
        $data['turno'] = $cita->horario_id.'|10:00';
        $this->put(route('citas.reprogramar.update', $cita), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('citas_medicas', ['id' => $cita->id, 'inicio' => '2030-05-07 10:00:00']);
    }

    public function test_occupied_turn_preserves_original_reservation_and_creates_no_audit(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->createAppointment(['paciente_id' => $cita->paciente_id, 'medico_id' => $cita->medico_id,
            'especialidad_id' => $cita->especialidad_id, 'horario_id' => $cita->horario_id,
            'inicio' => '2030-05-07 10:00:00', 'fin' => '2030-05-07 10:30:00']);
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->assertOriginal($cita);
    }

    public function test_exclusion_constraint_rolls_back_a_racing_reservation(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->createAppointment(['medico_id' => $cita->medico_id, 'especialidad_id' => $cita->especialidad_id,
            'horario_id' => $cita->horario_id, 'inicio' => '2030-05-07 10:00:00', 'fin' => '2030-05-07 10:30:00']);
        $this->mock(ReprogramacionDisponibilidadService::class)->shouldReceive('turnos')->once()->andReturn([
            ['horario_id' => $cita->horario_id, 'inicio' => '2030-05-07 10:00:00', 'fin' => '2030-05-07 10:30:00', 'duracion_minutos' => 30],
        ]);
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->assertOriginal($cita);
    }

    public function test_stale_version_does_not_overwrite_a_more_recent_change(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $data = $this->data($cita);
        DB::table('citas_medicas')->where('id', $cita->id)->update(['motivo' => 'Cambio de otro usuario']);
        $this->putJson(route('citas.reprogramar.update', $cita), $data)->assertUnprocessable()->assertJsonValidationErrors('version');
        $this->assertDatabaseHas('citas_medicas', ['id' => $cita->id, 'motivo' => 'Cambio de otro usuario', 'inicio' => '2030-05-07 09:00:00']);
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }

    public function test_terminal_states_cannot_be_reprogrammed(): void
    {
        $this->loginManager();
        foreach (['cancelada', 'completada', 'inasistencia', 'en_curso'] as $estado) {
            $cita = $this->createAppointment(['estado' => $estado]);
            $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertUnprocessable()->assertJsonValidationErrors('estado');
            $this->assertOriginal($cita);
        }
    }

    public function test_inactive_patient_doctor_specialty_or_assignment_are_rejected(): void
    {
        $this->loginManager();
        foreach (['pacientes', 'medicos', 'especialidades', 'medico_especialidad'] as $tabla) {
            $cita = $this->createAppointment();
            $query = DB::table($tabla);
            if ($tabla === 'medico_especialidad') {
                $query->where('medico_id', $cita->medico_id)->where('especialidad_id', $cita->especialidad_id);
            } else {
                $query->where('id', match ($tabla) {
                    'pacientes' => $cita->paciente_id, 'medicos' => $cita->medico_id, default => $cita->especialidad_id
                });
            }
            $query->update(['activo' => false]);
            $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertUnprocessable();
            $this->assertOriginal($cita);
        }
    }

    public function test_doctor_can_change_only_within_the_same_specialty(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $otro = $this->createAppointment(['especialidad_id' => $cita->especialidad_id]);
        $this->put(route('citas.reprogramar.update', $cita), $this->data($cita, ['medico_id' => $otro->medico_id, 'horario_id' => $otro->horario_id,
            'paciente_id' => $otro->paciente_id, 'especialidad_id' => 999999]))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas_medicas', ['id' => $cita->id, 'medico_id' => $otro->medico_id, 'paciente_id' => $cita->paciente_id,
            'especialidad_id' => $cita->especialidad_id, 'horario_id' => $otro->horario_id]);
        $fuera = $this->createAppointment();
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita->fresh(), ['medico_id' => $fuera->medico_id, 'horario_id' => $fuera->horario_id]))
            ->assertUnprocessable()->assertJsonValidationErrors('inicio');
    }

    public function test_blocked_past_out_of_schedule_and_misaligned_turns_are_rejected(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        DB::table('indisponibilidades_medico')->insert(['medico_id' => $cita->medico_id, 'inicio' => '2030-05-07 10:15:00', 'fin' => '2030-05-07 10:45:00', 'motivo' => 'Bloqueo', 'activo' => true]);
        foreach ([['inicio' => '10:00'], ['inicio' => '10:30'], ['inicio' => '08:30'], ['inicio' => '17:00'], ['inicio' => '11:15'], ['fecha' => '2030-04-30']] as $cambios) {
            $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita, $cambios))->assertUnprocessable()->assertJsonValidationErrors('inicio');
            $this->assertOriginal($cita);
        }
    }

    public function test_schedule_weekday_validity_and_active_state_are_enforced(): void
    {
        $this->loginManager();
        foreach ([['dia_semana' => 1], ['vigente_desde' => '2030-05-08'], ['vigente_hasta' => '2030-05-06'], ['activo' => false], ['duracion_turno_min' => 0]] as $cambio) {
            $cita = $this->createAppointment();
            DB::table('horarios_atencion')->where('id', $cita->horario_id)->update($cambio);
            $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertUnprocessable()->assertJsonValidationErrors('inicio');
            $this->assertOriginal($cita);
        }
    }

    public function test_availability_excludes_no_shows_and_all_other_occupied_states_but_includes_cancelled_slots(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        foreach (['inasistencia' => '10:00', 'completada' => '11:00', 'en_curso' => '12:00', 'confirmada' => '13:00', 'programada' => '14:00', 'cancelada' => '15:00'] as $estado => $hora) {
            $inicio = CarbonImmutable::parse('2030-05-07 '.$hora);
            $this->createAppointment(['medico_id' => $cita->medico_id, 'especialidad_id' => $cita->especialidad_id, 'horario_id' => $cita->horario_id,
                'inicio' => $inicio->format('Y-m-d H:i:s'), 'fin' => $inicio->addMinutes(30)->format('Y-m-d H:i:s'), 'estado' => $estado]);
        }
        $response = $this->getJson(route('citas.reprogramar.turnos', ['cita' => $cita, 'medico_id' => $cita->medico_id, 'fecha' => '2030-05-07']))->assertOk();
        $inicios = array_column($response->json('turnos'), 'inicio');
        $this->assertContains('2030-05-07 09:00:00', $inicios);
        $this->assertContains('2030-05-07 15:00:00', $inicios);
        foreach (['10:00', '11:00', '12:00', '13:00', '14:00'] as $hora) {
            $this->assertNotContains('2030-05-07 '.$hora.':00', $inicios);
        }
    }

    public function test_same_turn_and_forged_duration_or_schedule_are_rejected(): void
    {
        $this->loginManager();
        $cita = $this->createAppointment();
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita, ['inicio' => '09:00']))->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita, ['horario_id' => 999999]))->assertUnprocessable()->assertJsonValidationErrors('inicio');
        $this->put(route('citas.reprogramar.update', $cita), $this->data($cita, ['fin' => '2030-05-07 23:59:00', 'estado' => 'completada']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas_medicas', ['id' => $cita->id, 'fin' => '2030-05-07 10:30:00', 'estado' => 'programada']);
    }

    public function test_today_only_exposes_future_complete_turns_and_respects_adjacent_boundaries(): void
    {
        $this->travelTo(CarbonImmutable::parse('2030-05-07 09:30:00', 'America/La_Paz'));
        $this->loginManager();
        $cita = $this->createAppointment(['inicio' => '2030-05-07 09:00:00', 'fin' => '2030-05-07 09:30:00']);
        DB::table('horarios_atencion')->where('id', $cita->horario_id)->update(['hora_fin' => '10:45:00']);
        DB::table('indisponibilidades_medico')->insert(['medico_id' => $cita->medico_id, 'inicio' => '2030-05-07 10:30:00', 'fin' => '2030-05-07 11:00:00', 'motivo' => 'Límite', 'activo' => true]);
        $response = $this->getJson(route('citas.reprogramar.turnos', ['cita' => $cita, 'medico_id' => $cita->medico_id, 'fecha' => '2030-05-07']))->assertOk();
        $this->assertSame([['horario_id' => $cita->horario_id, 'inicio' => '2030-05-07 10:00:00', 'fin' => '2030-05-07 10:30:00', 'duracion_minutos' => 30]], $response->json('turnos'));
    }

    public function test_form_and_json_endpoints_require_permission_and_validate_inputs(): void
    {
        $cita = $this->createAppointment();
        $this->get(route('citas.reprogramar.edit', $cita))->assertRedirect(route('login'));
        $actor = User::factory()->create();
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect();
        $this->get(route('citas.reprogramar.edit', $cita))->assertForbidden();
        $this->getJson(route('citas.reprogramar.turnos', ['cita' => $cita, 'medico_id' => $cita->medico_id, 'fecha' => '2030-05-07']))->assertForbidden();
        $this->putJson(route('citas.reprogramar.update', $cita), $this->data($cita))->assertForbidden();
        $this->post('/logout');
        $this->loginManager();
        $this->getJson(route('citas.reprogramar.turnos', ['cita' => $cita, 'fecha' => 'fecha inválida']))->assertUnprocessable()->assertJsonValidationErrors(['medico_id', 'fecha']);
        $this->putJson(route('citas.reprogramar.update', $cita), ['fecha' => '2030-02-30', 'inicio' => '25:00', 'medico_id' => [], 'version' => []])
            ->assertUnprocessable()->assertJsonValidationErrors(['fecha', 'inicio', 'medico_id', 'horario_id', 'version']);
        $this->assertOriginal($cita);
    }

    public function test_csrf_is_required_before_rescheduling_and_original_reservation_is_preserved(): void
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
        $this->put(route('citas.reprogramar.update', $cita), $this->data($cita))->assertStatus(419);
        $this->assertOriginal($cita);
    }

    /**
     * @param  array<string, mixed>  $cambios
     * @return array<string, mixed>
     */
    private function data(CitaMedica $cita, array $cambios = []): array
    {
        return array_replace(['version' => $cita->editVersion(), 'medico_id' => $cita->medico_id, 'horario_id' => $cita->horario_id,
            'fecha' => $cita->inicio->format('Y-m-d'), 'inicio' => '10:00'], $cambios);
    }

    private function assertOriginal(CitaMedica $cita): void
    {
        $this->assertEquals($cita->snapshot(), $cita->fresh()->snapshot());
        $this->assertDatabaseMissing('cambios_cita', ['cita_id' => $cita->id]);
    }
}
