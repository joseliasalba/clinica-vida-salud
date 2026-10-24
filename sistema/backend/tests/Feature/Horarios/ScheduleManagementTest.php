<?php

namespace Tests\Feature\Horarios;

use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Services\Horarios\ScheduleManagement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesSchedulingData;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesSchedulingData, DatabaseTransactions;

    public function test_every_action_requires_session_and_effective_permission(): void
    {
        $schedule = HorarioAtencion::factory()->create();
        $urls = ['/horarios', '/horarios/crear', '/horarios/'.$schedule->id.'/editar', '/horarios/'.$schedule->id.'/estado'];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['recepcion', 'medico', 'paciente'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/horarios', [])->assertForbidden();
            $this->put('/horarios/'.$schedule->id, [])->assertForbidden();
            $this->patch('/horarios/'.$schedule->id.'/estado', [])->assertForbidden();
            $this->post('/logout');
        }
        $this->assertDatabaseCount('horarios_atencion', 1);
    }

    public function test_admin_can_open_all_screens_and_no_delete_route_exists(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        foreach (['/horarios', '/horarios/crear', '/horarios/'.$schedule->id.'/editar', '/horarios/'.$schedule->id.'/estado'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/horarios/999999999/editar')->assertNotFound();
        $this->delete('/horarios/'.$schedule->id)->assertStatus(405);
        $this->get('/inicio')->assertOk()->assertSee(route('horarios.index'));
    }

    public function test_creation_keeps_the_selected_doctor_and_ignores_privileged_fields(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $this->post('/horarios', $this->scheduleData($doctor, ['activo' => false, 'id' => 999999999]))->assertRedirect(route('horarios.index'))->assertSessionHasNoErrors();
        $schedule = HorarioAtencion::query()->sole();
        $this->assertSame($doctor->id, $schedule->medico_id);
        $this->assertTrue($schedule->activo);
        $this->assertSame(30, $schedule->duracion_turno_min);
        $this->assertNull($schedule->vigente_hasta);
        $this->assertDatabaseCount('citas_medicas', 0);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_schedule_data_is_rejected(array $changes, string $field): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $this->from('/horarios/crear')->post('/horarios', $this->scheduleData($doctor, $changes))->assertSessionHasErrors($field);
        $this->get('/horarios/crear')->assertOk();
        $this->assertDatabaseCount('horarios_atencion', 0);
    }

    public static function invalidInput(): array
    {
        return [
            'día' => [['dia_semana' => 0], 'dia_semana'],
            'día como array' => [['dia_semana' => ['1']], 'dia_semana'],
            'hora inválida' => [['hora_inicio' => '25:00'], 'hora_inicio'],
            'franja inversa' => [['hora_fin' => '08:00'], 'hora_fin'],
            'sin duración' => [['duracion_turno_min' => 0], 'duracion_turno_min'],
            'duración como array' => [['duracion_turno_min' => ['30']], 'duracion_turno_min'],
            'turno incompleto' => [['duracion_turno_min' => 50], 'duracion_turno_min'],
            'vigencia inversa' => [['vigente_desde' => '2026-11-02', 'vigente_hasta' => '2026-11-01'], 'vigente_hasta'],
            'sin fecha del día' => [['dia_semana' => 1, 'vigente_desde' => '2026-11-03', 'vigente_hasta' => '2026-11-04'], 'vigente_hasta'],
            'médico inexistente' => [['medico_id' => 999999999], 'medico_id'],
        ];
    }

    public function test_overlaps_are_rejected_but_adjacent_shifts_and_other_doctors_or_days_are_allowed(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $this->post('/horarios', $this->scheduleData($doctor))->assertSessionHasNoErrors();
        $this->post('/horarios', $this->scheduleData($doctor, ['hora_inicio' => '10:00', 'hora_fin' => '13:00']))->assertSessionHasErrors('intervalo');
        $this->post('/horarios', $this->scheduleData($doctor, ['hora_inicio' => '12:00', 'hora_fin' => '15:00']))->assertSessionHasNoErrors();
        $this->post('/horarios', $this->scheduleData($doctor, ['dia_semana' => 2]))->assertSessionHasNoErrors();
        $this->post('/horarios', $this->scheduleData(Medico::factory()->create()))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('horarios_atencion', 4);
    }

    public function test_validity_uses_actual_weekday_occurrences_and_inclusive_end_dates(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $this->post('/horarios', $this->scheduleData($doctor, ['vigente_desde' => '2026-11-02', 'vigente_hasta' => '2026-11-04']))->assertSessionHasNoErrors();
        $this->post('/horarios', $this->scheduleData($doctor, ['vigente_desde' => '2026-11-03', 'vigente_hasta' => '2026-11-09']))->assertSessionHasNoErrors();
        $this->post('/horarios', $this->scheduleData($doctor, ['vigente_desde' => '2026-11-09', 'vigente_hasta' => '2026-11-16']))->assertSessionHasErrors('intervalo');
        $this->assertDatabaseCount('horarios_atencion', 2);
    }

    public function test_edit_and_state_change_keep_identity_reject_old_versions_and_do_not_move_doctor(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        $old = $schedule->editVersion();
        $this->put('/horarios/'.$schedule->id, $this->scheduleData($schedule->medico, ['hora_fin' => '13:00', 'version' => $old, 'activo' => false]))->assertSessionHasNoErrors();
        $this->assertTrue($schedule->fresh()->activo);
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => false, 'version' => $old])->assertSessionHasErrors('version');
        $this->put('/horarios/'.$schedule->id, $this->scheduleData(Medico::factory()->create(), ['version' => $schedule->fresh()->editVersion()]))->assertSessionHasErrors('medico_id');
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => false, 'version' => $schedule->fresh()->editVersion()])->assertSessionHasNoErrors();
        $this->assertFalse($schedule->fresh()->activo);
    }

    public function test_inactive_records_do_not_conflict_but_cannot_be_reactivated_over_another_shift(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create(['activo' => false]);
        $this->post('/horarios', $this->scheduleData($schedule->medico))->assertSessionHasNoErrors();
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => true, 'version' => $schedule->editVersion()])->assertSessionHasErrors('intervalo');
        $this->assertFalse($schedule->fresh()->activo);
    }

    public function test_inactive_doctors_cannot_receive_new_or_reactivated_schedules(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create(['activo' => false]);
        $this->post('/horarios', $this->scheduleData($doctor))->assertSessionHasErrors('medico_id');
        $schedule = HorarioAtencion::factory()->create(['medico_id' => $doctor->id, 'activo' => false]);
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => true, 'version' => $schedule->editVersion()])->assertSessionHasErrors('medico_id');
    }

    public function test_booked_schedules_keep_their_structure_and_do_not_orphan_pending_appointments(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        $appointment = $this->appointmentFor($schedule);
        $this->put('/horarios/'.$schedule->id, $this->scheduleData($schedule->medico, ['duracion_turno_min' => 60, 'version' => $schedule->editVersion()]))->assertSessionHasErrors('intervalo');
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => false, 'version' => $schedule->editVersion()])->assertSessionHasErrors('intervalo');
        $this->put('/horarios/'.$schedule->id, $this->scheduleData($schedule->medico, ['vigente_hasta' => now()->endOfWeek()->toDateString(), 'version' => $schedule->editVersion()]))->assertSessionHasErrors('intervalo');
        $this->put('/horarios/'.$schedule->id, $this->scheduleData($schedule->medico, ['vigente_hasta' => now()->addWeeks(2)->toDateString(), 'version' => $schedule->editVersion()]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('citas_medicas', ['id' => $appointment, 'estado' => 'programada', 'horario_id' => $schedule->id]);
        DB::table('citas_medicas')->where('id', $appointment)->update(['estado' => 'cancelada']);
        $this->patch('/horarios/'.$schedule->id.'/estado', ['activo' => false, 'version' => $schedule->fresh()->editVersion()])->assertSessionHasNoErrors();
    }

    public function test_database_guard_and_service_permission_check_cannot_be_bypassed(): void
    {
        $actor = $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        try {
            app(ScheduleManagement::class)->saveSchedule($actor, $this->scheduleData($schedule->medico));
            $this->fail('Debe rechazar el solapamiento al omitir el Form Request.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('intervalo', $exception->errors());
        }
        DB::table('permisos')->where('codigo', 'horarios.gestionar')->update(['activo' => false]);
        $this->post('/horarios', $this->scheduleData($schedule->medico))->assertForbidden();
        try {
            app(ScheduleManagement::class)->saveSchedule($actor, $this->scheduleData($schedule->medico));
            $this->fail('Debe revalidar el permiso.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_filters_pagination_and_empty_states(): void
    {
        $this->loginManager();
        $this->get('/horarios')->assertOk()->assertSee('No encontramos horarios');
        $schedule = HorarioAtencion::factory()->create();
        HorarioAtencion::factory()->count(15)->create();
        $this->get('/horarios')->assertOk()->assertSee('page=2', false);
        $this->get('/horarios?medico_id='.$schedule->medico_id.'&dia_semana=1&estado=activo')->assertOk()->assertViewHas('horarios', fn ($rows) => $rows->total() === 1);
    }

    public function test_csrf_is_required_for_mutations(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/horarios', $this->scheduleData($schedule->medico))->assertStatus(419);
        $this->put('/horarios/'.$schedule->id, [])->assertStatus(419);
        $this->patch('/horarios/'.$schedule->id.'/estado', [])->assertStatus(419);

    }

    public function test_database_rejects_invalid_intervals_even_without_the_service(): void
    {
        $doctor = Medico::factory()->create();
        foreach ([['dia_semana' => 0], ['duracion_turno_min' => 0], ['duracion_turno_min' => 50], ['hora_fin' => '08:00']] as $change) {
            try {
                DB::transaction(fn () => DB::table('horarios_atencion')->insert($this->scheduleData($doctor, $change)));
                $this->fail('La base debe rechazar un intervalo inválido.');
            } catch (QueryException $exception) {
                $this->assertSame('23514', $exception->getCode());
            }
        }
        $this->assertDatabaseCount('horarios_atencion', 0);
    }
}
