<?php

namespace Tests\Feature\Horarios;

use App\Models\HorarioAtencion;
use App\Models\IndisponibilidadMedico;
use App\Models\Medico;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesSchedulingData;
use Tests\TestCase;

class UnavailabilityManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesSchedulingData, DatabaseTransactions;

    public function test_all_block_routes_require_permission(): void
    {
        $block = IndisponibilidadMedico::factory()->create();
        $urls = ['/horarios/bloqueos', '/horarios/bloqueos/crear', '/horarios/bloqueos/'.$block->id.'/editar', '/horarios/bloqueos/'.$block->id.'/estado'];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['recepcion', 'medico', 'paciente'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/horarios/bloqueos', [])->assertForbidden();
            $this->put('/horarios/bloqueos/'.$block->id, [])->assertForbidden();
            $this->patch('/horarios/bloqueos/'.$block->id.'/estado', [])->assertForbidden();
            $this->post('/logout');
        }
    }

    public function test_screens_creation_editing_and_no_physical_delete(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $this->get('/horarios/bloqueos')->assertOk()->assertSee('No encontramos bloqueos');
        $this->get('/horarios/bloqueos/crear')->assertOk();
        $this->post('/horarios/bloqueos', $this->blockData($doctor, ['motivo' => '  Vacaciones  ', 'activo' => false, 'id' => 99999999]))->assertRedirect(route('horarios.bloqueos.index'))->assertSessionHasNoErrors();
        $block = IndisponibilidadMedico::query()->sole();
        $this->assertTrue($block->activo);
        $this->assertSame('Vacaciones', $block->motivo);
        $this->get('/horarios/bloqueos/'.$block->id.'/editar')->assertOk();
        $this->get('/horarios/bloqueos/'.$block->id.'/estado')->assertOk();
        $this->put('/horarios/bloqueos/'.$block->id, $this->blockData($doctor, ['motivo' => 'Permiso', 'version' => $block->editVersion()]))->assertSessionHasNoErrors();
        $this->assertSame('Permiso', $block->fresh()->motivo);
        $this->delete('/horarios/bloqueos/'.$block->id)->assertStatus(405);
        $this->get('/horarios/bloqueos/99999999/editar')->assertNotFound();
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_blocks_do_not_save(array $changes, string $field): void
    {
        $this->loginManager();
        $this->post('/horarios/bloqueos', $this->blockData(Medico::factory()->create(), $changes))->assertSessionHasErrors($field);
        $this->get('/horarios/bloqueos/crear')->assertOk();
        $this->assertDatabaseCount('indisponibilidades_medico', 0);
    }

    public static function invalidInput(): array
    {
        return [
            'médico desconocido' => [['medico_id' => 999999999], 'medico_id'],
            'intervalo inverso' => [['inicio' => '2026-11-03T10:00', 'fin' => '2026-11-03T09:00'], 'fin'],
            'fecha inválida' => [['inicio' => '2026-02-30T10:00'], 'inicio'],
            'sin motivo' => [['motivo' => '   '], 'motivo'],
            'motivo como array' => [['motivo' => ['dato']], 'motivo'],
            'motivo largo' => [['motivo' => str_repeat('A', 256)], 'motivo'],
        ];
    }

    public function test_overlaps_are_rejected_and_touching_boundaries_allowed(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create();
        $first = $this->blockData($doctor, ['inicio' => '2026-11-02T09:00', 'fin' => '2026-11-02T12:00']);
        $this->post('/horarios/bloqueos', $first)->assertSessionHasNoErrors();
        $this->post('/horarios/bloqueos', array_replace($first, ['inicio' => '2026-11-02T10:00']))->assertSessionHasErrors('intervalo');
        $this->post('/horarios/bloqueos', array_replace($first, ['inicio' => '2026-11-02T12:00', 'fin' => '2026-11-03T12:00']))->assertSessionHasNoErrors();
        $this->post('/horarios/bloqueos', array_replace($first, ['medico_id' => Medico::factory()->create()->id]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('indisponibilidades_medico', 3);
    }

    public function test_stale_forms_and_doctor_changes_are_rejected(): void
    {
        $this->loginManager();
        $block = IndisponibilidadMedico::factory()->create();
        $old = $block->editVersion();
        $this->put('/horarios/bloqueos/'.$block->id, $this->blockData($block->medico, ['version' => $old]))->assertSessionHasNoErrors();
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', ['activo' => false, 'version' => $old])->assertSessionHasErrors('version');
        $this->put('/horarios/bloqueos/'.$block->id, $this->blockData(Medico::factory()->create(), ['version' => $block->fresh()->editVersion()]))->assertSessionHasErrors('medico_id');
    }

    public function test_pending_appointments_prevent_creating_and_reactivating_a_conflicting_block(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        $appointment = $this->appointmentFor($schedule);
        $this->post('/horarios/bloqueos', $this->blockData($schedule->medico))->assertSessionHasErrors('intervalo');
        $block = IndisponibilidadMedico::factory()->create(['medico_id' => $schedule->medico_id, 'inicio' => $this->blockData($schedule->medico)['inicio'], 'fin' => $this->blockData($schedule->medico)['fin'], 'activo' => false]);
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', ['activo' => true, 'version' => $block->editVersion()])->assertSessionHasErrors('intervalo');
        $this->assertDatabaseHas('citas_medicas', ['id' => $appointment, 'estado' => 'programada']);
        DB::table('citas_medicas')->where('id', $appointment)->update(['estado' => 'cancelada']);
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', ['activo' => true, 'version' => $block->fresh()->editVersion()])->assertSessionHasNoErrors();
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', ['activo' => false, 'version' => $block->fresh()->editVersion()])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('citas_medicas', 1);
    }

    public function test_editing_active_block_cannot_move_it_over_an_appointment(): void
    {
        $this->loginManager();
        $schedule = HorarioAtencion::factory()->create();
        $this->appointmentFor($schedule);
        $block = IndisponibilidadMedico::factory()->create(['medico_id' => $schedule->medico_id]);
        $this->put('/horarios/bloqueos/'.$block->id, $this->blockData($schedule->medico, ['version' => $block->editVersion()]))->assertSessionHasErrors('intervalo');
    }

    public function test_inactive_doctor_and_overlapping_reactivation_are_rejected(): void
    {
        $this->loginManager();
        $doctor = Medico::factory()->create(['activo' => false]);
        $this->post('/horarios/bloqueos', $this->blockData($doctor))->assertSessionHasErrors('medico_id');
        $doctor->activo = true;
        $doctor->save();
        $this->post('/horarios/bloqueos', $this->blockData($doctor))->assertSessionHasNoErrors();
        $block = IndisponibilidadMedico::factory()->create(['medico_id' => $doctor->id, 'inicio' => $this->blockData($doctor)['inicio'], 'fin' => $this->blockData($doctor)['fin'], 'activo' => false]);
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', ['activo' => true, 'version' => $block->editVersion()])->assertSessionHasErrors('intervalo');
    }

    public function test_pagination_filters_and_escaping(): void
    {
        $this->loginManager();
        $block = IndisponibilidadMedico::factory()->create(['motivo' => '<script>alert(1)</script>']);
        IndisponibilidadMedico::factory()->count(15)->create();
        $this->get('/horarios/bloqueos')->assertOk()->assertSee('page=2', false);
        $this->get('/horarios/bloqueos?medico_id='.$block->medico_id.'&estado=activo')->assertOk()->assertSee($block->motivo)->assertDontSee($block->motivo, false)->assertViewHas('bloqueos', fn ($rows) => $rows->total() === 1);
        $this->get('/horarios/bloqueos/'.$block->id.'/estado')->assertOk()->assertDontSee($block->motivo, false);
    }

    public function test_csrf_and_permission_revocation_prevent_writes(): void
    {
        $this->loginManager();
        $block = IndisponibilidadMedico::factory()->create();
        DB::table('permisos')->where('codigo', 'horarios.gestionar')->update(['activo' => false]);
        $this->post('/horarios/bloqueos', $this->blockData($block->medico))->assertForbidden();
        DB::table('permisos')->where('codigo', 'horarios.gestionar')->update(['activo' => true]);
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/horarios/bloqueos', [])->assertStatus(419);
        $this->put('/horarios/bloqueos/'.$block->id, [])->assertStatus(419);
        $this->patch('/horarios/bloqueos/'.$block->id.'/estado', [])->assertStatus(419);

    }
}
