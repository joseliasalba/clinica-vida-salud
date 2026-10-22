<?php

namespace Tests\Feature\Medicos;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\Medicos\DoctorManagement;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class DoctorManagementTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_all_routes_require_a_session_and_effective_permission(): void
    {
        $doctor = Medico::factory()->create();
        $urls = ['/medicos', '/medicos/crear', '/medicos/'.$doctor->id.'/editar', '/medicos/'.$doctor->id.'/estado'];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/medicos', [])->assertRedirect(route('login'));
        foreach (['recepcion', 'medico', 'paciente'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
            foreach ($urls as $url) {
                $this->get($url)->assertForbidden();
            }
            $this->post('/medicos', [])->assertForbidden();
            $this->put('/medicos/'.$doctor->id, [])->assertForbidden();
            $this->patch('/medicos/'.$doctor->id.'/estado', [])->assertForbidden();
            $this->post('/logout');
        }
        $this->assertDatabaseCount('medicos', 1);
    }

    public function test_admin_can_open_all_screens_and_no_delete_route_exists(): void
    {
        $this->loginManager();
        $doctor = $this->doctor();
        foreach (['/medicos', '/medicos/crear', '/medicos/'.$doctor->id.'/editar', '/medicos/'.$doctor->id.'/estado'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/inicio')->assertOk()->assertSee(route('medicos.index'));
        $this->get('/medicos/999999999/editar')->assertNotFound();
        $this->delete('/medicos/'.$doctor->id)->assertStatus(405);
    }

    public function test_registration_generates_code_normalizes_data_and_keeps_accounts_separate(): void
    {
        $this->loginManager();
        $first = Especialidad::factory()->create();
        $second = Especialidad::factory()->create();
        $this->post('/medicos', $this->data(['especialidades' => [$first->id, $second->id], 'nombres' => ' María   Elena ', 'registro_profesional' => ' reg-001 ', 'correo' => ' MEDICA@EXAMPLE.TEST ', 'id' => 99999999, 'activo' => false, 'codigo' => 'INYECTADO']))->assertRedirect(route('medicos.index'))->assertSessionHasNoErrors();
        $doctor = Medico::query()->sole();
        $this->assertSame('María Elena', $doctor->nombres);
        $this->assertSame('REG-001', $doctor->registro_profesional);
        $this->assertSame('medica@example.test', $doctor->correo);
        $this->assertSame('MED-'.str_pad((string) $doctor->id, 6, '0', STR_PAD_LEFT), $doctor->codigo);
        $this->assertTrue($doctor->activo);
        $this->assertNull($doctor->usuario_id);
        $this->assertSame(2, $doctor->especialidades()->wherePivot('activo', true)->count());
        $this->assertDatabaseCount('usuarios', 1);
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_data_is_rejected_without_partial_records(array $changes, string $field): void
    {
        $this->loginManager();
        $this->post('/medicos', $this->data($changes))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('medicos', 0);
        $this->assertDatabaseCount('medico_especialidad', 0);
    }

    public static function invalidInput(): array
    {
        return [
            'sin nombres' => [['nombres' => ''], 'nombres'],
            'array' => [['registro_profesional' => ['dato']], 'registro_profesional'],
            'registro inválido' => [['registro_profesional' => '<script>'], 'registro_profesional'],
            'teléfono' => [['telefono' => 'incorrecto'], 'telefono'],
            'correo' => [['correo' => 'incorrecto'], 'correo'],
            'sin especialidades' => [['especialidades' => []], 'especialidades'],
            'especialidades no array' => [['especialidades' => '1'], 'especialidades'],
            'especialidad desconocida' => [['especialidades' => [999999999]], 'especialidades.0'],
            'cuenta desconocida' => [['usuario_id' => 999999999], 'usuario_id'],
        ];
    }

    public function test_inactive_specialties_and_duplicate_selections_are_rejected(): void
    {
        $this->loginManager();
        $specialty = Especialidad::factory()->create(['activo' => false]);
        $this->post('/medicos', $this->data(['especialidades' => [$specialty->id]]))->assertSessionHasErrors('especialidades.0');
        $specialty->activo = true;
        $specialty->save();
        $this->post('/medicos', $this->data(['especialidades' => [$specialty->id, $specialty->id]]))->assertSessionHasErrors('especialidades.0');
        $this->assertDatabaseCount('medicos', 0);
    }

    public function test_normalized_registration_is_unique_even_for_legacy_inactive_records_and_direct_writes(): void
    {
        $actor = $this->loginManager();
        Medico::factory()->create(['registro_profesional' => ' reg-001 ', 'activo' => false]);
        $this->post('/medicos', $this->data())->assertSessionHasErrors('registro_profesional');
        try {
            app(DoctorManagement::class)->save($actor, $this->data());
            $this->fail('PostgreSQL debe rechazar el duplicado normalizado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('registro_profesional', $exception->errors());
        }
        $this->assertDatabaseCount('medicos', 1);
        $this->assertDatabaseCount('medico_especialidad', 0);
    }

    public function test_edit_preserves_identity_and_retains_removed_specialty_rows(): void
    {
        $this->loginManager();
        $doctor = $this->doctor();
        $old = $doctor->especialidades->first();
        $new = Especialidad::factory()->create();
        $code = $doctor->codigo;
        $this->put('/medicos/'.$doctor->id, $this->data(['registro_profesional' => $doctor->registro_profesional, 'especialidades' => [$new->id], 'version' => $doctor->editVersion(), 'activo' => false, 'codigo' => 'CAMBIADO']))->assertSessionHasNoErrors();
        $this->assertSame($code, $doctor->fresh()->codigo);
        $this->assertTrue($doctor->fresh()->activo);
        $this->assertDatabaseHas('medico_especialidad', ['medico_id' => $doctor->id, 'especialidad_id' => $old->id, 'activo' => false]);
        $this->assertDatabaseHas('medico_especialidad', ['medico_id' => $doctor->id, 'especialidad_id' => $new->id, 'activo' => true]);
    }

    public function test_version_detects_changes_to_contact_or_only_specialties_and_rejects_old_state_confirmation(): void
    {
        $this->loginManager();
        $doctor = $this->doctor();
        $version = $doctor->editVersion();
        DB::table('medico_especialidad')->where('medico_id', $doctor->id)->update(['activo' => false]);
        $this->put('/medicos/'.$doctor->id, $this->data(['version' => $version]))->assertSessionHasErrors('version');
        $this->patch('/medicos/'.$doctor->id.'/estado', ['activo' => false, 'version' => $version])->assertSessionHasErrors('version');
        $this->assertTrue($doctor->fresh()->activo);
        $version = $doctor->fresh()->editVersion();
        $doctor->telefono = '+591 71111111';
        $doctor->save();
        $this->put('/medicos/'.$doctor->id, $this->data(['version' => $version]))->assertSessionHasErrors('version');
    }

    public function test_only_eligible_existing_accounts_can_be_linked_and_they_cannot_be_shared(): void
    {
        $this->loginManager();
        $account = $this->accountWithRole('medico');
        $this->post('/medicos', $this->data(['usuario_id' => $account->id]))->assertSessionHasNoErrors();
        $doctor = Medico::query()->sole();
        $this->assertSame($account->id, $doctor->usuario_id);
        $this->post('/medicos', $this->data(['registro_profesional' => 'REG-002', 'usuario_id' => $account->id]))->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseCount('medicos', 1);
        $invalid = $this->accountWithRole('paciente');
        $this->post('/medicos', $this->data(['registro_profesional' => 'REG-003', 'usuario_id' => $invalid->id]))->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseCount('medicos', 1);
        $account->activo = false;
        $account->save();
        $this->put('/medicos/'.$doctor->id, $this->data(['version' => $doctor->editVersion(), 'usuario_id' => $account->id]))->assertSessionHasNoErrors();
        $this->assertSame($account->id, $doctor->fresh()->usuario_id);
    }

    public function test_disabled_specialty_relations_are_preserved_but_cannot_alone_reactivate_a_doctor(): void
    {
        $this->loginManager();
        $doctor = $this->doctor();
        $specialty = $doctor->especialidades->first();
        $specialty->activo = false;
        $specialty->save();
        $this->patch('/medicos/'.$doctor->id.'/estado', ['activo' => false, 'version' => $doctor->editVersion()])->assertSessionHasNoErrors();
        $this->patch('/medicos/'.$doctor->id.'/estado', ['activo' => true, 'version' => $doctor->fresh()->editVersion()])->assertSessionHasErrors('activo');
        $this->put('/medicos/'.$doctor->id, $this->data(['version' => $doctor->fresh()->editVersion()]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('medico_especialidad', ['medico_id' => $doctor->id, 'especialidad_id' => $specialty->id, 'activo' => true]);
        $this->patch('/medicos/'.$doctor->id.'/estado', ['activo' => true, 'version' => $doctor->fresh()->editVersion()])->assertSessionHasNoErrors();
        $this->assertTrue($doctor->fresh()->activo);
    }

    public function test_state_change_preserves_future_appointments_schedules_and_account(): void
    {
        $this->loginManager();
        $account = $this->accountWithRole('medico');
        $doctor = $this->doctor(['usuario_id' => $account->id]);
        $schedule = DB::table('horarios_atencion')->insertGetId(['medico_id' => $doctor->id, 'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '10:00', 'duracion_turno_min' => 30, 'vigente_desde' => now()->toDateString(), 'activo' => true]);
        $date = now()->addDay()->toDateString();
        $appointment = DB::table('citas_medicas')->insertGetId(['codigo' => 'CITA-TEST', 'paciente_id' => Paciente::factory()->create()->id, 'medico_id' => $doctor->id, 'especialidad_id' => $doctor->especialidades->first()->id, 'horario_id' => $schedule, 'inicio' => $date.' 09:00', 'fin' => $date.' 09:30', 'estado' => 'programada', 'creada_por' => auth()->id()]);
        $this->get('/medicos/'.$doctor->id.'/estado')->assertOk()->assertSee('1 citas futuras pendientes');
        $this->patch('/medicos/'.$doctor->id.'/estado', ['activo' => false, 'version' => $doctor->editVersion()])->assertSessionHasNoErrors();
        $this->assertFalse($doctor->fresh()->activo);
        $this->assertTrue($account->fresh()->activo);
        $this->assertDatabaseHas('citas_medicas', ['id' => $appointment, 'estado' => 'programada']);
        $this->assertDatabaseHas('horarios_atencion', ['id' => $schedule, 'activo' => true]);
    }

    public function test_search_filters_pagination_and_escaped_names(): void
    {
        $this->loginManager();
        $doctor = $this->doctor(['nombres' => '<script>alert(1)</script>', 'registro_profesional' => 'REG-0', 'activo' => false]);
        $this->get('/medicos?q=REG-0&estado=inactivo&especialidad='.$doctor->especialidades->first()->id)->assertOk()->assertSee($doctor->nombres)->assertDontSee($doctor->nombres, false);
        $this->get('/medicos/'.$doctor->id.'/estado')->assertOk()->assertSee($doctor->nombres)->assertDontSee($doctor->nombres, false);
        Medico::factory()->count(16)->create();
        $this->get('/medicos?estado=activo')->assertOk()->assertSee('page=2', false);
        $this->get('/medicos?q=%25_nonexistent')->assertOk()->assertSee('No encontramos médicos');
        $this->get('/medicos?q=0')->assertOk()->assertSee('REG-0');
    }

    public function test_code_collision_rolls_back_the_doctor_and_its_specialty_assignments(): void
    {
        $actor = $this->loginManager();
        $reserved = Medico::factory()->create();
        $reserved->codigo = 'MED-'.str_pad((string) ($reserved->id + 1), 6, '0', STR_PAD_LEFT);
        $reserved->save();
        try {
            app(DoctorManagement::class)->save($actor, $this->data());
            $this->fail('Un código reservado debe revertir el registro.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('codigo', $exception->errors());
        }
        $this->assertDatabaseCount('medicos', 1);
        $this->assertDatabaseCount('medico_especialidad', 0);
    }

    public function test_service_revalidates_specialty_and_account_availability(): void
    {
        $actor = $this->loginManager();
        $specialty = Especialidad::factory()->create(['activo' => false]);
        try {
            app(DoctorManagement::class)->save($actor, $this->data(['especialidades' => [$specialty->id]]));
            $this->fail('El servicio debe rechazar una especialidad desactivada.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('especialidades', $exception->errors());
        }
        $account = $this->accountWithRole('medico');
        DB::table('usuario_rol')->where('usuario_id', $account->id)->update(['fecha_expiracion' => now()->subMinute()]);
        $this->post('/medicos', $this->data(['usuario_id' => $account->id]))->assertSessionHasErrors('usuario_id');
        $this->assertDatabaseCount('medicos', 0);
    }

    public function test_permission_revocation_and_csrf_prevent_changes(): void
    {
        $actor = $this->loginManager();
        $doctor = $this->doctor();
        DB::table('permisos')->where('codigo', 'medicos.gestionar')->update(['activo' => false]);
        $this->post('/medicos', $this->data())->assertForbidden();
        try {
            app(DoctorManagement::class)->save($actor, $this->data());
            $this->fail('Debe revalidar el permiso dentro del servicio.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        DB::table('permisos')->where('codigo', 'medicos.gestionar')->update(['activo' => true]);
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/medicos', $this->data())->assertStatus(419);
        $this->put('/medicos/'.$doctor->id, [])->assertStatus(419);
        $this->patch('/medicos/'.$doctor->id.'/estado', [])->assertStatus(419);
        $this->assertDatabaseCount('medicos', 1);
    }

    private function data(array $changes = []): array
    {
        $specialties = $changes['especialidades'] ?? [Especialidad::factory()->create()->id];

        return array_replace(['nombres' => 'María', 'apellidos' => 'Prueba', 'registro_profesional' => 'REG-001', 'telefono' => '+591 70000000', 'correo' => 'medica@example.test', 'especialidades' => $specialties], $changes);
    }

    private function doctor(array $changes = []): Medico
    {
        $doctor = Medico::factory()->create($changes);
        $doctor->especialidades()->attach(Especialidad::factory()->create()->id, ['activo' => true]);

        return $doctor;
    }
}
