<?php

namespace Tests\Feature\Pacientes;

use App\Models\Paciente;
use App\Models\User;
use App\Services\Pacientes\PatientRegistration;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_all_actions_require_a_session_and_effective_permission(): void
    {
        $paciente = Paciente::factory()->create();
        $urls = ['/pacientes', '/pacientes/crear', '/pacientes/'.$paciente->id, '/pacientes/'.$paciente->id.'/editar'];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/pacientes', [])->assertRedirect(route('login'));
        $this->put('/pacientes/'.$paciente->id, [])->assertRedirect(route('login'));
        foreach (['medico', 'paciente'] as $role) {
            $this->loginReception($role);
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/pacientes', $this->patientData())->assertForbidden();
            $this->put('/pacientes/'.$paciente->id, $this->patientData())->assertForbidden();
            $this->post('/logout');
        }
        $this->assertDatabaseCount('pacientes', 1);
    }

    public function test_reception_can_open_screens_and_home_links_to_patients_without_access_admin_tabs(): void
    {
        $this->loginReception();
        $paciente = Paciente::factory()->create();
        $this->get('/inicio')->assertOk()->assertSee(route('pacientes.index'));
        foreach (['/pacientes', '/pacientes/crear', '/pacientes/'.$paciente->id, '/pacientes/'.$paciente->id.'/editar'] as $url) {
            $this->get($url)->assertOk()->assertDontSee(route('usuarios.index'), false);
        }
        $this->get('/pacientes/99999999')->assertNotFound();
        $this->get('/pacientes/99999999/editar')->assertNotFound();
        $this->delete('/pacientes/'.$paciente->id)->assertStatus(405);
    }

    public function test_admin_registers_without_an_account_and_server_generates_the_code(): void
    {
        $actor = $this->loginReception('administrador');
        $this->post('/pacientes', $this->patientData(['ci' => ' 001234-ab ', 'correo' => ' PERSONA@EXAMPLE.TEST ', 'nombres' => ' María ', 'id' => 99999999, 'codigo' => 'forzado', 'usuario_id' => $actor->id]))
            ->assertSessionHasNoErrors()->assertRedirect();
        $paciente = Paciente::query()->sole();
        $this->assertSame('PAC-'.str_pad((string) $paciente->id, 6, '0', STR_PAD_LEFT), $paciente->codigo);
        $this->assertSame('001234-AB', $paciente->ci);
        $this->assertSame('María', $paciente->nombres);
        $this->assertSame('persona@example.test', $paciente->correo);
        $this->assertNull($paciente->usuario_id);
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('historias_clinicas', 0);
    }

    public function test_multiple_patients_without_ci_have_distinct_codes_and_can_share_contact_email(): void
    {
        $this->loginReception();
        foreach ([null, '  ', ''] as $ci) {
            $this->post('/pacientes', $this->patientData(['ci' => $ci, 'correo' => 'familia@example.test']))->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('pacientes', 3);
        $this->assertSame(3, Paciente::query()->whereNull('ci')->count());
        $this->assertSame(3, Paciente::query()->distinct()->count('codigo'));
    }

    public function test_duplicate_ci_is_rejected_even_for_inactive_or_legacy_lowercase_records(): void
    {
        $this->loginReception();
        Paciente::factory()->create(['ci' => ' 00123-ab ', 'activo' => false]);
        $this->post('/pacientes', $this->patientData(['ci' => '00123-AB']))->assertSessionHasErrors('ci');
        $this->assertDatabaseCount('pacientes', 1);
        $this->assertFalse(Paciente::query()->sole()->activo);
    }

    public function test_database_uniqueness_protects_writes_that_bypass_form_validation(): void
    {
        $actor = $this->loginReception();
        Paciente::factory()->create(['ci' => 'abcd-123']);
        try {
            app(PatientRegistration::class)->save($actor, $this->patientData(['ci' => 'ABCD-123']));
            $this->fail('La unicidad normalizada debía rechazar el duplicado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('ci', $exception->errors());
        }
        $this->assertDatabaseCount('pacientes', 1);
        $this->assertSame(0, Paciente::query()->where('codigo', 'like', 'PEND-%')->count());
    }

    public function test_invalid_data_never_creates_a_partial_patient(): void
    {
        $this->loginReception();
        $cases = [
            [['nombres' => ' '], 'nombres'], [['apellidos' => []], 'apellidos'],
            [['fecha_nacimiento' => '2026-02-30'], 'fecha_nacimiento'], [['fecha_nacimiento' => today()->addDay()->toDateString()], 'fecha_nacimiento'],
            [['telefono' => 'abc'], 'telefono'], [['telefono' => []], 'telefono'], [['correo' => 'sin-correo'], 'correo'],
            [['ci' => []], 'ci'], [['ci' => '<script>'], 'ci'], [['ci' => str_repeat('1', 51)], 'ci'],
            [['direccion' => str_repeat('x', 256)], 'direccion'], [['sexo' => []], 'sexo'], [['activo' => 'otro'], 'activo'],
        ];
        foreach ($cases as [$changes, $field]) {
            $this->post('/pacientes', $this->patientData($changes))->assertSessionHasErrors($field);
        }
        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_edit_and_state_changes_preserve_code_account_and_history(): void
    {
        $actor = $this->loginReception();
        $account = User::factory()->create();
        $paciente = Paciente::factory()->create(['usuario_id' => $account->id, 'ci' => '123456']);
        $code = $paciente->codigo;
        DB::table('historias_clinicas')->insert(['paciente_id' => $paciente->id, 'nro_expediente' => 'HC-TEST']);
        foreach ([false, true] as $active) {
            $paciente->refresh();
            $this->put('/pacientes/'.$paciente->id, $this->patientData(['ci' => '123456', 'activo' => $active, 'version' => $paciente->editVersion(), 'codigo' => 'cambiar', 'usuario_id' => $actor->id]))
                ->assertSessionHasNoErrors()->assertRedirect(route('pacientes.show', $paciente));
            $paciente->refresh();
            $this->assertSame($active, $paciente->activo);
            $this->assertSame($code, $paciente->codigo);
            $this->assertSame($account->id, $paciente->usuario_id);
            $this->assertTrue($account->fresh()->activo);
            $this->assertDatabaseHas('historias_clinicas', ['paciente_id' => $paciente->id, 'nro_expediente' => 'HC-TEST']);
        }
    }

    public function test_stale_editor_cannot_overwrite_a_newer_change_even_within_the_same_second(): void
    {
        $this->loginReception();
        $paciente = Paciente::factory()->create()->fresh();
        $version = $paciente->editVersion();
        $paciente->update(['telefono' => '77777777']);
        $this->followingRedirects()->from(route('pacientes.edit', $paciente))->put('/pacientes/'.$paciente->id, $this->patientData(['ci' => $paciente->ci, 'version' => $version, 'telefono' => '66666666']))
            ->assertOk()->assertSee('Recargar la ficha actualizada');
        $this->assertSame('77777777', $paciente->fresh()->telefono);
    }

    public function test_search_filters_pagination_and_literal_wildcards_work(): void
    {
        $this->loginReception();
        $target = Paciente::factory()->create(['nombres' => 'Elena', 'apellidos' => 'Zárate', 'ci' => '0099-A', 'codigo' => 'PAC-BUSCAR', 'activo' => false]);
        Paciente::factory()->count(17)->create();
        foreach (['elena zárate', '0099-a', 'pac-buscar'] as $q) {
            $this->get('/pacientes?q='.urlencode($q))->assertOk()->assertViewHas('pacientes', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $target->id);
        }
        $this->get('/pacientes?estado=inactivo')->assertViewHas('pacientes', fn ($rows) => $rows->total() === 1);
        $this->get('/pacientes?estado=activo')->assertOk()->assertSee('page=2', false)->assertDontSee('PAC-BUSCAR');
        $this->get('/pacientes?q=%25')->assertSee('No encontramos pacientes');
        $this->get('/pacientes?q=_')->assertSee('No encontramos pacientes');
        $this->getJson('/pacientes?q[]=x')->assertUnprocessable();
        $this->getJson('/pacientes?estado=otro')->assertUnprocessable();
    }

    public function test_names_are_escaped_in_list_form_and_dynamic_page_heading(): void
    {
        $this->loginReception();
        $payload = '<script>alert(1)</script>';
        $paciente = Paciente::factory()->create(['nombres' => $payload]);
        foreach (['/pacientes', '/pacientes/'.$paciente->id, '/pacientes/'.$paciente->id.'/editar'] as $url) {
            $this->get($url)->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
        }
    }

    public function test_zero_is_a_real_search_term_instead_of_an_empty_filter(): void
    {
        $this->loginReception();
        $target = Paciente::factory()->create(['ci' => '001', 'codigo' => 'FIRST', 'nombres' => 'Ana', 'apellidos' => 'Pérez']);
        Paciente::factory()->create(['ci' => '999', 'codigo' => 'SECOND', 'nombres' => 'Rosa', 'apellidos' => 'Rojas']);
        $this->get('/pacientes?q=0')->assertOk()->assertViewHas('pacientes', fn ($rows) => $rows->total() === 1 && $rows->first()->id === $target->id);
    }

    public function test_revoked_permission_and_csrf_prevent_writes(): void
    {
        $actor = $this->loginReception();
        DB::table('permisos')->where('codigo', 'pacientes.gestionar')->update(['activo' => false]);
        $this->post('/pacientes', $this->patientData())->assertForbidden();
        DB::table('permisos')->where('codigo', 'pacientes.gestionar')->update(['activo' => true]);
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/pacientes', $this->patientData())->assertStatus(419);
        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_code_collision_rolls_back_the_entire_registration(): void
    {
        $actor = $this->loginReception();
        $reserved = Paciente::factory()->create();
        $reserved->codigo = 'PAC-'.str_pad((string) ($reserved->id + 1), 6, '0', STR_PAD_LEFT);
        $reserved->save();
        try {
            app(PatientRegistration::class)->save($actor, $this->patientData());
            $this->fail('El código reservado debía provocar rollback.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('codigo', $exception->errors());
        }
        $this->assertDatabaseCount('pacientes', 1);
    }

    private function loginReception(string $role = 'recepcion'): User
    {
        $actor = $this->accountWithRole($role);
        $this->post('/login', ['identificador' => $actor->correo, 'password' => 'password'])->assertRedirect(route('inicio'));

        return $actor;
    }

    /** @return array<string, mixed> */
    private function patientData(array $changes = []): array
    {
        return array_replace(['ci' => null, 'nombres' => 'Ana María', 'apellidos' => 'Pérez', 'fecha_nacimiento' => '2000-02-29', 'telefono' => '+591 71234567', 'correo' => null, 'sexo' => null, 'direccion' => null, 'activo' => '1'], $changes);
    }
}
