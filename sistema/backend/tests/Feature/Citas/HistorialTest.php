<?php

namespace Tests\Feature\Citas;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    public function test_history_requires_an_authenticated_session_and_effective_permission(): void
    {
        $cita = $this->createAppointment();
        $this->get('/historial')->assertRedirect(route('login'));
        $this->get('/pacientes/'.$cita->paciente_id.'/historial')->assertRedirect(route('login'));
        $actor = $this->accountWithRole('medico');
        $this->loginAs($actor);
        $this->get('/historial')->assertForbidden();
        $this->get('/pacientes/'.$cita->paciente_id.'/historial')->assertForbidden();
    }

    public function test_staff_must_select_a_patient_before_any_appointment_is_listed(): void
    {
        $cita = $this->createAppointment();
        $this->loginManager();
        $this->get('/historial')->assertOk()->assertSee('Selecciona un paciente')
            ->assertDontSee($cita->codigo)->assertViewHas('citas', fn ($citas) => $citas->total() === 0);
    }

    public function test_administrator_and_reception_see_selected_patient_history_including_inactive_records(): void
    {
        $paciente = Paciente::factory()->create(['activo' => false]);
        $past = $this->createAppointment(['paciente_id' => $paciente->id, 'estado' => 'cancelada', 'inicio' => '2025-01-01 08:00:00', 'fin' => '2025-01-01 08:30:00', 'motivo' => 'MOTIVO_PRIVADO', 'motivo_cancelacion' => 'CANCELACION_PRIVADA']);
        $future = $this->createAppointment(['paciente_id' => $paciente->id, 'inicio' => '2027-01-01 12:00:00', 'fin' => '2027-01-01 12:30:00']);
        $other = $this->createAppointment();
        foreach (['administrador', 'recepcion'] as $role) {
            $this->loginAs($this->accountWithRole($role));
            $this->get('/historial?paciente_id='.$paciente->id)->assertOk()
                ->assertSeeInOrder([$future->codigo, $past->codigo])
                ->assertSee('Ficha inactiva')->assertSee('Cancelada')
                ->assertDontSee($other->codigo)->assertDontSee('MOTIVO_PRIVADO')->assertDontSee('CANCELACION_PRIVADA')
                ->assertDontSee('Reprogramar')->assertDontSee('Registrar estado')->assertViewHas('citas', fn ($citas) => $citas->total() === 2);
            $this->get('/pacientes/'.$paciente->id.'/historial')->assertOk()->assertSee($past->codigo);
            $this->post('/logout');
        }
    }

    public function test_patient_management_permission_alone_can_read_history(): void
    {
        $actor = $this->accountWithRole('recepcion');
        DB::table('rol_permiso')->where('rol_id', $this->roleId('recepcion'))->where('permiso_id', $this->permissionId('citas.gestionar'))->update(['activo' => false]);
        $cita = $this->createAppointment();
        $this->loginAs($actor);
        $this->get('/historial?paciente_id='.$cita->paciente_id)->assertOk()->assertSee($cita->codigo);
    }

    public function test_a_patient_can_read_only_their_own_history_and_cannot_enumerate_patient_options(): void
    {
        $actor = $this->accountWithRole('paciente');
        $own = $this->createAppointment();
        $other = $this->createAppointment();
        DB::table('pacientes')->where('id', $own->paciente_id)->update(['usuario_id' => $actor->id]);
        $this->loginAs($actor);
        $this->get('/historial')->assertOk()->assertSee($own->codigo)->assertDontSee($other->codigo)
            ->assertDontSee('name="paciente_id"', false)->assertDontSee('Buscar paciente por código')
            ->assertViewHas('pacientes', fn ($pacientes) => $pacientes->isEmpty());
        $this->get('/historial?paciente_id='.$own->paciente_id)->assertOk()->assertSee($own->codigo);
        $this->get('/pacientes/'.$own->paciente_id.'/historial')->assertOk()->assertSee($own->codigo);
        $this->get('/historial?paciente_id='.$other->paciente_id)->assertForbidden();
        $this->get('/historial?paciente_id=999999999')->assertForbidden();
        $this->get('/pacientes/'.$other->paciente_id.'/historial')->assertForbidden();
        $this->get('/pacientes/'.$own->paciente_id.'/historial?paciente_id='.$other->paciente_id)->assertForbidden();
    }

    public function test_a_patient_without_a_linked_profile_receives_an_empty_history(): void
    {
        $other = $this->createAppointment();
        $this->loginAs($this->accountWithRole('paciente'));
        $this->get('/historial')->assertOk()->assertSee('Tu cuenta todavía no tiene una ficha de paciente vinculada.')
            ->assertDontSee($other->codigo)->assertViewHas('citas', fn ($citas) => $citas->total() === 0);
        $this->get('/historial?paciente_id='.$other->paciente_id)->assertForbidden();
    }

    public function test_staff_can_search_patients_by_code_ci_and_full_name_with_literal_wildcards(): void
    {
        $paciente = Paciente::factory()->create(['nombres' => 'Ana_María', 'apellidos' => 'Prueba%Literal', 'ci' => '987654 LP']);
        $other = Paciente::factory()->create(['nombres' => 'Juan', 'apellidos' => 'Diferente']);
        $this->loginManager();
        foreach ([$paciente->codigo, $paciente->ci, 'Ana_María Prueba%Literal', '%Literal', 'Ana_'] as $term) {
            $this->get('/historial?'.http_build_query(['q' => $term]))->assertOk()
                ->assertSee($paciente->codigo)->assertDontSee($other->codigo);
        }
        $this->get('/historial?q=sin_coincidencias')->assertOk()->assertDontSee($paciente->codigo);
    }

    public function test_date_range_is_inclusive_and_does_not_require_both_bounds(): void
    {
        $paciente = Paciente::factory()->create();
        $before = $this->createAppointment(['paciente_id' => $paciente->id, 'inicio' => '2026-10-20 23:00:00', 'fin' => '2026-10-20 23:30:00']);
        $first = $this->createAppointment(['paciente_id' => $paciente->id, 'inicio' => '2026-10-21 00:00:00', 'fin' => '2026-10-21 00:30:00']);
        $last = $this->createAppointment(['paciente_id' => $paciente->id, 'inicio' => '2026-10-22 23:30:00', 'fin' => '2026-10-23 00:00:00']);
        $after = $this->createAppointment(['paciente_id' => $paciente->id, 'inicio' => '2026-10-23 00:30:00', 'fin' => '2026-10-23 01:00:00']);
        $this->loginManager();
        $url = '/historial?paciente_id='.$paciente->id;
        $this->get($url.'&desde=2026-10-21&hasta=2026-10-22')->assertOk()
            ->assertSee($first->codigo)->assertSee($last->codigo)->assertDontSee($before->codigo)->assertDontSee($after->codigo);
        $this->get($url.'&hasta=2026-10-21')->assertOk()->assertSee($before->codigo)->assertSee($first->codigo)->assertDontSee($last->codigo);
        $this->get($url.'&desde=2026-10-23')->assertOk()->assertSee($after->codigo)->assertDontSee($last->codigo);
    }

    public function test_state_filter_includes_both_stored_spellings_of_attended_appointments(): void
    {
        $paciente = Paciente::factory()->create();
        $completed = $this->createAppointment(['paciente_id' => $paciente->id, 'estado' => 'completada']);
        $legacy = $this->createAppointment(['paciente_id' => $paciente->id, 'estado' => 'atendida', 'inicio' => $completed->inicio->addHour()]);
        $cancelled = $this->createAppointment(['paciente_id' => $paciente->id, 'estado' => 'cancelada']);
        $this->loginManager();
        $this->get('/historial?paciente_id='.$paciente->id.'&estado=completada')->assertOk()
            ->assertSee($completed->codigo)->assertSee($legacy->codigo)->assertDontSee($cancelled->codigo);
        $this->get('/historial?paciente_id='.$paciente->id.'&estado=cancelada')->assertOk()
            ->assertSee($cancelled->codigo)->assertDontSee($completed->codigo);
    }

    public function test_invalid_filters_are_rejected_before_querying_history(): void
    {
        $this->loginManager();
        foreach ([
            [['q' => ['invalid']], 'q'], [['q' => str_repeat('a', 101)], 'q'],
            [['paciente_id' => ['invalid']], 'paciente_id'], [['paciente_id' => '-1'], 'paciente_id'],
            [['desde' => 'ayer'], 'desde'], [['desde' => '2026-02-30'], 'desde'],
            [['hasta' => ['invalid']], 'hasta'], [['desde' => '2026-10-23', 'hasta' => '2026-10-22'], 'hasta'],
            [['estado' => 'inexistente'], 'estado'], [['estado' => ['invalid']], 'estado'],
            [['page' => '0'], 'page'], [['page' => ['invalid']], 'page'],
        ] as [$filters, $field]) {
            $this->get('/historial?'.http_build_query($filters))->assertSessionHasErrors($field);
        }
        $this->get('/historial?paciente_id=999999999')->assertNotFound();
        $this->get('/pacientes/999999999/historial')->assertNotFound();
    }

    public function test_history_is_paginated_and_preserves_filters_and_patient_selection(): void
    {
        $paciente = Paciente::factory()->create();
        $appointments = [];
        for ($index = 0; $index < 17; $index++) {
            $appointments[] = $this->createAppointment(['paciente_id' => $paciente->id, 'estado' => 'cancelada', 'inicio' => '2026-10-22 09:00:00', 'fin' => '2026-10-22 09:30:00']);
        }
        $this->loginManager();
        $url = '/historial?'.http_build_query(['paciente_id' => $paciente->id, 'desde' => '2026-10-22', 'hasta' => '2026-10-22', 'estado' => 'cancelada', 'q' => $paciente->codigo]);
        $this->get($url)->assertOk()->assertViewHas('citas', fn ($citas) => $citas->count() === 15 && $citas->total() === 17)
            ->assertSee($appointments[16]->codigo)->assertDontSee($appointments[0]->codigo)
            ->assertSee('estado=cancelada', false)->assertSee('paciente_id='.$paciente->id, false);
        $this->get($url.'&page=2')->assertOk()->assertViewHas('citas', fn ($citas) => $citas->count() === 2)
            ->assertSee($appointments[0]->codigo)->assertDontSee($appointments[16]->codigo);
        $this->get($url.'&page=3')->assertOk()->assertSee('No hay citas para mostrar.');
    }

    public function test_patient_options_are_bounded_and_selected_patient_is_preserved(): void
    {
        $selected = Paciente::factory()->create(['apellidos' => 'ZZZ Final']);
        Paciente::factory()->count(26)->create(['apellidos' => 'AAA Inicio']);
        $this->loginManager();
        $this->get('/historial?paciente_id='.$selected->id)->assertOk()->assertSee('Hay más de 25 coincidencias.')
            ->assertSee($selected->codigo)->assertViewHas('pacientes', fn ($pacientes) => $pacientes->count() === 26);
    }

    public function test_revoked_permissions_and_profile_reassignment_take_effect_on_the_next_request(): void
    {
        $actor = $this->accountWithRole('paciente');
        $own = $this->createAppointment();
        DB::table('pacientes')->where('id', $own->paciente_id)->update(['usuario_id' => $actor->id]);
        $this->loginAs($actor);
        $this->get('/historial')->assertOk()->assertSee($own->codigo);
        DB::table('pacientes')->where('id', $own->paciente_id)->update(['usuario_id' => null]);
        $this->get('/historial')->assertOk()->assertDontSee($own->codigo);
        $this->get('/historial?paciente_id='.$own->paciente_id)->assertForbidden();
        DB::table('permisos')->where('codigo', 'citas.propias')->update(['activo' => false]);
        $this->get('/historial')->assertForbidden();
    }

    public function test_html_is_escaped_and_read_operations_do_not_change_records_or_audit(): void
    {
        $paciente = Paciente::factory()->create(['nombres' => '<script>alert(1)</script>']);
        $cita = $this->createAppointment(['paciente_id' => $paciente->id, 'motivo' => 'CLINICO_SECRETO']);
        $before = $cita->getRawOriginal();
        $this->loginManager();
        $this->get('/historial?paciente_id='.$paciente->id)->assertOk()
            ->assertSee(e('<script>alert(1)</script>'), false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('CLINICO_SECRETO')->assertSee('data-column="registro"', false);
        $this->assertSame($before, $cita->fresh()->getRawOriginal());
        $this->assertDatabaseCount('cambios_cita', 0);
        $this->post('/historial', [])->assertStatus(405);
        $this->patch('/historial', [])->assertStatus(405);
    }

    private function loginAs(User $actor): void
    {
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
    }
}
