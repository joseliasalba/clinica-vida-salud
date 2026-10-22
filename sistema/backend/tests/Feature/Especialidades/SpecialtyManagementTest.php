<?php

namespace Tests\Feature\Especialidades;

use App\Models\Especialidad;
use App\Services\Especialidades\SpecialtyManagement;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class SpecialtyManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_all_actions_require_a_session_and_effective_permission(): void
    {
        $especialidad = Especialidad::factory()->create();
        $urls = ['/especialidades', '/especialidades/crear', '/especialidades/'.$especialidad->id.'/editar', '/especialidades/'.$especialidad->id.'/estado'];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/especialidades', [])->assertRedirect(route('login'));
        $this->put('/especialidades/'.$especialidad->id, [])->assertRedirect(route('login'));
        $this->patch('/especialidades/'.$especialidad->id.'/estado', [])->assertRedirect(route('login'));
        foreach (['recepcion', 'medico', 'paciente'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/especialidades', $this->specialtyData())->assertForbidden();
            $this->put('/especialidades/'.$especialidad->id, $this->specialtyData())->assertForbidden();
            $this->patch('/especialidades/'.$especialidad->id.'/estado', ['activo' => '0', 'version' => $especialidad->editVersion()])->assertForbidden();
            $this->get('/inicio')->assertDontSee(route('especialidades.index'), false);
            $this->post('/logout');
        }
        $this->assertDatabaseCount('especialidades', 1);
        $this->assertTrue($especialidad->fresh()->activo);
    }

    public function test_admin_can_open_screens_and_no_physical_delete_route_exists(): void
    {
        $this->loginManager();
        $especialidad = Especialidad::factory()->create();
        $this->get('/inicio')->assertOk()->assertSee(route('especialidades.index'), false);
        foreach (['/especialidades', '/especialidades/crear', '/especialidades/'.$especialidad->id.'/editar', '/especialidades/'.$especialidad->id.'/estado'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/especialidades/999999999/editar')->assertNotFound();
        $this->get('/especialidades/999999999/estado')->assertNotFound();
        $this->delete('/especialidades/'.$especialidad->id)->assertStatus(405);
    }

    public function test_creation_normalizes_name_and_ignores_protected_fields(): void
    {
        $this->loginManager();
        $this->post('/especialidades', $this->specialtyData([
            'nombre' => "  MEDICINA\t  Familiar  ", 'descripcion' => '  Atención integral  ',
            'nombre_normalizado' => 'forzado', 'activo' => '0', 'id' => 999999999,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('especialidades.index'));
        $especialidad = Especialidad::query()->sole();
        $this->assertSame('MEDICINA Familiar', $especialidad->nombre);
        $this->assertSame('medicina familiar', $especialidad->nombre_normalizado);
        $this->assertSame('Atención integral', $especialidad->descripcion);
        $this->assertTrue($especialidad->activo);
        $this->assertNotSame(999999999, $especialidad->id);
        $this->post('/especialidades', $this->specialtyData(['nombre' => 'Pediatría', 'descripcion' => '  ']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('especialidades', ['nombre_normalizado' => 'pediatria', 'descripcion' => null]);
    }

    public function test_duplicates_ignore_accents_case_and_whitespace_even_when_inactive(): void
    {
        $this->loginManager();
        Especialidad::factory()->create(['nombre' => 'Cirugía Pediátrica', 'activo' => false]);
        foreach (['cirugia pediatrica', ' CIRUGÍA   PEDIÁTRICA ', "Cirugi\u{0301}a pedia\u{0301}trica"] as $nombre) {
            $this->post('/especialidades', $this->specialtyData(['nombre' => $nombre]))->assertSessionHasErrors('nombre');
        }
        $this->assertDatabaseCount('especialidades', 1);
    }

    public function test_database_unique_constraint_produces_a_useful_error_and_rolls_back(): void
    {
        $actor = $this->loginManager();
        Especialidad::factory()->create(['nombre' => 'Cardiología']);
        try {
            app(SpecialtyManagement::class)->save($actor, $this->specialtyData(['nombre' => ' CARDIOLOGIA ']));
            $this->fail('La restricción única debía rechazar el duplicado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('nombre', $exception->errors());
        }
        $this->assertDatabaseCount('especialidades', 1);
    }

    public function test_invalid_input_cannot_create_a_partial_specialty(): void
    {
        $this->loginManager();
        foreach ([
            [['nombre' => '   '], 'nombre'], [['nombre' => []], 'nombre'],
            [['nombre' => str_repeat('x', 256)], 'nombre'],
            [['nombre' => str_repeat('ß', 200)], 'nombre'],
            [['descripcion' => []], 'descripcion'], [['descripcion' => str_repeat('x', 256)], 'descripcion'],
        ] as [$changes, $field]) {
            $this->post('/especialidades', $this->specialtyData($changes))->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('especialidades', 0);
    }

    public function test_edit_can_keep_its_name_and_cannot_change_state_or_identity(): void
    {
        $this->loginManager();
        $especialidad = Especialidad::factory()->create(['nombre' => 'Cardiología', 'activo' => false])->fresh();
        $this->put('/especialidades/'.$especialidad->id, $this->specialtyData([
            'nombre' => ' CARDIOLOGIA ', 'descripcion' => 'Control cardiovascular',
            'version' => $especialidad->editVersion(), 'activo' => '1', 'nombre_normalizado' => 'forzado', 'id' => 999999999,
        ]))->assertSessionHasNoErrors()->assertRedirect(route('especialidades.index'));
        $especialidad->refresh();
        $this->assertSame('CARDIOLOGIA', $especialidad->nombre);
        $this->assertSame('cardiologia', $especialidad->nombre_normalizado);
        $this->assertSame('Control cardiovascular', $especialidad->descripcion);
        $this->assertFalse($especialidad->activo);
        Especialidad::factory()->create(['nombre' => 'Pediatría']);
        $this->put('/especialidades/'.$especialidad->id, $this->specialtyData(['nombre' => 'pediatria', 'version' => $especialidad->editVersion()]))->assertSessionHasErrors('nombre');
        $this->assertSame('CARDIOLOGIA', $especialidad->fresh()->nombre);
    }

    public function test_state_changes_preserve_doctor_links_and_appointments(): void
    {
        $actor = $this->loginManager();
        $especialidad = Especialidad::factory()->create()->fresh();
        $medicoId = DB::table('medicos')->insertGetId([
            'codigo' => 'MED-CU03', 'registro_profesional' => 'REG-CU03', 'nombres' => 'Luis',
            'apellidos' => 'Prueba', 'telefono' => '70000001', 'correo' => 'medico-cu03@example.test',
        ]);
        DB::table('medico_especialidad')->insert(['medico_id' => $medicoId, 'especialidad_id' => $especialidad->id, 'activo' => true]);
        $pacienteId = DB::table('pacientes')->insertGetId(['codigo' => 'PAC-CU03', 'nombres' => 'Ana', 'apellidos' => 'Prueba', 'fecha_nacimiento' => '2000-01-01', 'telefono' => '70000000']);
        $horarioId = DB::table('horarios_atencion')->insertGetId(['medico_id' => $medicoId, 'dia_semana' => 4, 'hora_inicio' => '09:00', 'hora_fin' => '12:00', 'duracion_turno_min' => 30, 'vigente_desde' => '2026-01-01']);
        $citaId = DB::table('citas_medicas')->insertGetId([
            'codigo' => 'CITA-CU03', 'paciente_id' => $pacienteId, 'medico_id' => $medicoId,
            'especialidad_id' => $especialidad->id, 'horario_id' => $horarioId,
            'inicio' => '2026-10-22 09:00:00', 'fin' => '2026-10-22 09:30:00',
            'estado' => 'programada', 'creada_por' => $actor->id,
        ]);
        $originalName = $especialidad->nombre;
        foreach ([false, true] as $active) {
            $this->patch('/especialidades/'.$especialidad->id.'/estado', [
                'activo' => $active ? '1' : '0', 'version' => $especialidad->editVersion(), 'nombre' => 'Nombre forzado',
            ])->assertSessionHasNoErrors()->assertRedirect(route('especialidades.index'));
            $especialidad->refresh();
            $this->assertSame($active, $especialidad->activo);
            $this->assertSame($originalName, $especialidad->nombre);
            $this->assertDatabaseHas('medico_especialidad', ['medico_id' => $medicoId, 'especialidad_id' => $especialidad->id, 'activo' => true]);
            $this->assertDatabaseHas('citas_medicas', ['id' => $citaId, 'especialidad_id' => $especialidad->id, 'estado' => 'programada']);
        }
        $this->assertDatabaseCount('especialidades', 1);
    }

    public function test_invalid_state_and_missing_versions_do_not_change_data(): void
    {
        $this->loginManager();
        $especialidad = Especialidad::factory()->create()->fresh();
        foreach (['otro', [], null] as $value) {
            $this->patch('/especialidades/'.$especialidad->id.'/estado', ['activo' => $value, 'version' => $especialidad->editVersion()])->assertSessionHasErrors('activo');
        }
        $this->patch('/especialidades/'.$especialidad->id.'/estado', ['activo' => '0'])->assertSessionHasErrors('version');
        $this->put('/especialidades/'.$especialidad->id, $this->specialtyData())->assertSessionHasErrors('version');
        $this->assertTrue($especialidad->fresh()->activo);
    }

    public function test_stale_edits_and_state_confirmations_cannot_overwrite_newer_changes(): void
    {
        $this->loginManager();
        $especialidad = Especialidad::factory()->create()->fresh();
        $version = $especialidad->editVersion();
        $especialidad->update(['descripcion' => 'Actualizada por otro administrador']);
        $this->followingRedirects()->from(route('especialidades.edit', $especialidad))->put('/especialidades/'.$especialidad->id,
            $this->specialtyData(['version' => $version]))
            ->assertOk()->assertSee('Recargar la especialidad actualizada');
        $this->followingRedirects()->from(route('especialidades.estado.edit', $especialidad))->patch('/especialidades/'.$especialidad->id.'/estado', ['activo' => '0', 'version' => $version])
            ->assertOk()->assertSee('Recargar');
        $this->assertSame('Actualizada por otro administrador', $especialidad->fresh()->descripcion);
        $this->assertTrue($especialidad->fresh()->activo);
    }

    public function test_search_normalization_state_filters_and_pagination(): void
    {
        $this->loginManager();
        $target = Especialidad::factory()->create(['nombre' => 'Cirugía Pediátrica', 'descripcion' => 'Atención quirúrgica infantil', 'activo' => false]);
        Especialidad::factory()->count(17)->create();
        foreach (['cirugia', 'CIRUGÍA', '  cirugía   pediatrica  '] as $q) {
            $this->get('/especialidades?q='.urlencode($q))->assertOk()
                ->assertViewHas('especialidades', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $target->id);
        }
        $this->get('/especialidades?estado=inactivo')->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 1);
        $this->get('/especialidades?q=INFANTIL&estado=inactivo')->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $target->id);
        $this->get('/especialidades?q=INFANTIL&estado=activo')->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 0);
        $this->get('/especialidades?estado=activo')->assertOk()->assertSee('page=2', false)->assertDontSee('Cirugía Pediátrica');
        $this->get('/especialidades?q=%25')->assertSee('No encontramos especialidades');
        $this->get('/especialidades?q=_')->assertSee('No encontramos especialidades');
        $this->getJson('/especialidades?q[]=x')->assertUnprocessable();
        $this->getJson('/especialidades?estado=otro')->assertUnprocessable();
    }

    public function test_zero_search_is_not_treated_as_empty_and_wildcards_are_literal(): void
    {
        $this->loginManager();
        $target = Especialidad::factory()->create(['nombre' => 'Área 0']);
        Especialidad::factory()->create(['nombre' => 'Medicina General']);
        $literal = Especialidad::factory()->create(['nombre' => 'Prueba %_ especial']);
        $this->get('/especialidades?q=0')->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $target->id);
        $this->get('/especialidades?q=%25_')->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $literal->id);
        $this->get('/especialidades?q='.urlencode("' OR 1=1 --"))->assertOk()->assertViewHas('especialidades', fn ($rows) => $rows->total() === 0);
        $this->assertDatabaseCount('especialidades', 3);
    }

    public function test_html_is_escaped_in_all_screens(): void
    {
        $this->loginManager();
        $payload = '<script>alert(1)</script>';
        $especialidad = Especialidad::factory()->create(['nombre' => $payload, 'descripcion' => $payload]);
        foreach (['/especialidades', '/especialidades/'.$especialidad->id.'/editar', '/especialidades/'.$especialidad->id.'/estado'] as $url) {
            $this->get($url)->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
        }
    }

    public function test_revoked_permission_and_csrf_reject_mutations(): void
    {
        $this->loginManager();
        DB::table('permisos')->where('codigo', 'medicos.gestionar')->update(['activo' => false]);
        $this->post('/especialidades', $this->specialtyData())->assertForbidden();
        DB::table('permisos')->where('codigo', 'medicos.gestionar')->update(['activo' => true]);
        $especialidad = Especialidad::factory()->create()->fresh();
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/especialidades', $this->specialtyData())->assertStatus(419);
        $this->put('/especialidades/'.$especialidad->id, $this->specialtyData(['version' => $especialidad->editVersion()]))->assertStatus(419);
        $this->patch('/especialidades/'.$especialidad->id.'/estado', ['activo' => '0', 'version' => $especialidad->editVersion()])->assertStatus(419);
        $this->assertDatabaseCount('especialidades', 1);
        $this->assertTrue($especialidad->fresh()->activo);
    }

    /** @return array<string, mixed> */
    private function specialtyData(array $changes = []): array
    {
        return array_replace(['nombre' => 'Cardiología', 'descripcion' => 'Atención cardiovascular'], $changes);
    }
}
