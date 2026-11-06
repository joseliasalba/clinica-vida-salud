<?php

namespace Tests\Feature\Especialidades;

use App\Models\Especialidad;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class SpecialtyFeeTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_fees_are_exact_and_every_change_records_actor_and_previous_amount(): void
    {
        $actor = $this->loginManager();
        foreach (['0.01' => 1, '100.10' => 10010, '999999.99' => 99999999] as $amount => $cents) {
            $this->post('/especialidades', ['nombre' => 'Tarifa '.$amount, 'tarifa_consulta' => $amount, 'tarifa_centavos' => 5])
                ->assertSessionHasNoErrors();
            $specialty = Especialidad::query()->where('nombre', 'Tarifa '.$amount)->sole();
            $this->assertSame($cents, $specialty->tarifa_centavos);
            $this->assertSame($amount, $specialty->consultationFee());
            $this->assertDatabaseHas('cambios_tarifa_especialidad', [
                'especialidad_id' => $specialty->id, 'usuario_id' => $actor->id,
                'anterior_centavos' => null, 'nueva_centavos' => $cents,
            ]);
        }
        $specialty = Especialidad::query()->where('nombre', 'Tarifa 100.10')->sole();
        $this->put('/especialidades/'.$specialty->id, [
            'nombre' => $specialty->nombre, 'version' => $specialty->editVersion(), 'tarifa_consulta' => '150.5',
        ])->assertSessionHasNoErrors();
        $this->assertSame('150.50', $specialty->fresh()->consultationFee());
        $this->assertDatabaseHas('cambios_tarifa_especialidad', [
            'especialidad_id' => $specialty->id, 'usuario_id' => $actor->id,
            'anterior_centavos' => 10010, 'nueva_centavos' => 15050,
        ]);
    }

    public function test_invalid_amounts_cannot_create_specialties_or_audit_events(): void
    {
        $this->loginManager();
        foreach (['0', '0.00', '-1', '1.001', '1000000', '1e2', '1,50', '1,000.00', 'NaN', '001', [], true] as $amount) {
            $this->post('/especialidades', ['nombre' => 'Prueba tarifa', 'tarifa_consulta' => $amount])
                ->assertSessionHasErrors('tarifa_consulta');
        }
        $this->assertDatabaseCount('especialidades', 0);
        $this->assertDatabaseCount('cambios_tarifa_especialidad', 0);
    }

    public function test_missing_fee_is_unconfigured_and_omitted_updates_do_not_erase_it(): void
    {
        $this->loginManager();
        $this->post('/especialidades', ['nombre' => 'Sin tarifa'])->assertSessionHasNoErrors();
        $specialty = Especialidad::query()->sole();
        $this->assertNull($specialty->consultationFee());
        $this->assertDatabaseCount('cambios_tarifa_especialidad', 0);
        foreach (['10.00', '10.0'] as $amount) {
            $this->put('/especialidades/'.$specialty->id, [
                'nombre' => $specialty->nombre, 'version' => $specialty->fresh()->editVersion(), 'tarifa_consulta' => $amount,
            ])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('cambios_tarifa_especialidad', 1);
        $this->put('/especialidades/'.$specialty->id, [
            'nombre' => 'Descripción actualizada', 'version' => $specialty->fresh()->editVersion(), 'tarifa_centavos' => 3,
        ])->assertSessionHasNoErrors();
        $this->assertSame(1000, $specialty->fresh()->tarifa_centavos);
        $this->put('/especialidades/'.$specialty->id, [
            'nombre' => $specialty->fresh()->nombre, 'version' => $specialty->fresh()->editVersion(), 'tarifa_consulta' => '',
        ])->assertSessionHasNoErrors();
        $this->assertNull($specialty->fresh()->tarifa_centavos);
        $this->assertDatabaseHas('cambios_tarifa_especialidad', ['especialidad_id' => $specialty->id, 'anterior_centavos' => 1000, 'nueva_centavos' => null]);
    }

    public function test_stale_form_cannot_replace_a_newer_price(): void
    {
        $this->loginManager();
        $specialty = Especialidad::factory()->create(['tarifa_centavos' => 10000])->fresh();
        $version = $specialty->editVersion();
        $this->put('/especialidades/'.$specialty->id, ['nombre' => $specialty->nombre, 'version' => $version, 'tarifa_consulta' => '120'])->assertSessionHasNoErrors();
        $this->put('/especialidades/'.$specialty->id, ['nombre' => $specialty->nombre, 'version' => $version, 'tarifa_consulta' => '90'])->assertSessionHasErrors('version');
        $this->assertSame(12000, $specialty->fresh()->tarifa_centavos);
        $this->assertDatabaseCount('cambios_tarifa_especialidad', 1);
    }

    public function test_unauthorized_roles_cannot_set_prices(): void
    {
        $specialty = Especialidad::factory()->create(['tarifa_centavos' => 10000])->fresh();
        foreach (['paciente', 'medico', 'recepcion'] as $role) {
            $user = $this->accountWithRole($role);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect();
            $this->put('/especialidades/'.$specialty->id, ['nombre' => $specialty->nombre, 'version' => $specialty->editVersion(), 'tarifa_consulta' => '1'])->assertForbidden();
            $this->post('/logout');
        }
        $this->assertSame(10000, $specialty->fresh()->tarifa_centavos);
        $this->assertDatabaseCount('cambios_tarifa_especialidad', 0);
    }

    public function test_database_rejects_non_positive_and_out_of_range_fees(): void
    {
        $specialty = Especialidad::factory()->create();
        foreach ([0, -1, 100000000] as $invalid) {
            try {
                DB::transaction(fn () => DB::table('especialidades')->where('id', $specialty->id)->update(['tarifa_centavos' => $invalid]));
                $this->fail('La base de datos debía rechazar la tarifa.');
            } catch (QueryException $exception) {
                $this->assertSame('23514', $exception->getCode());
            }
        }
        $this->assertNull($specialty->fresh()->tarifa_centavos);
    }

    public function test_views_distinguish_missing_fees_and_render_exact_amounts(): void
    {
        $this->loginManager();
        $specialty = Especialidad::factory()->create(['nombre' => str_repeat('Cardiología ', 15), 'tarifa_centavos' => 99999999]);
        Especialidad::factory()->create(['nombre' => 'Sin tarifa']);
        $list = $this->get('/especialidades')->assertOk()->assertSee('Bs 999999.99')->assertSee('Sin configurar')->assertSee('Tarifa de consulta');
        $form = $this->get('/especialidades/'.$specialty->id.'/editar')->assertOk()->assertSee('value="999999.99"', false)->assertSee('inputmode="decimal"', false);
        if (getenv('CU18_PREVIEW') === '1') {
            File::ensureDirectoryExists(public_path('build/cu18-preview'));
            File::put(public_path('build/cu18-preview/index.html'), $list->getContent());
            File::put(public_path('build/cu18-preview/form.html'), $form->getContent());
        }
    }
}
