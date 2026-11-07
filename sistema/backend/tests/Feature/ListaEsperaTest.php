<?php

namespace Tests\Feature;

use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class ListaEsperaTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    private function patient(): array
    {
        $user = $this->accountWithRole('paciente');
        $user->forceFill(['email_verified_at' => now()])->save();
        $patient = Paciente::factory()->create(['usuario_id' => $user->id]);
        $specialty = Especialidad::factory()->create();

        return [$user, $patient, $specialty];
    }

    private function login(User $user): void
    {
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
    }

    public function test_patient_registers_once_and_can_cancel_without_reserving(): void
    {
        [$user, $patient, $specialty] = $this->patient();
        $this->login($user);
        $data = ['especialidad_id' => $specialty->id, 'fecha' => now()->addDay()->toDateString()];
        $this->get(route('citas.index', $data + ['accion' => 'consultar']))->assertOk()->assertSee('Inscribirse en la lista');
        $this->post(route('lista-espera.store'), $data)->assertRedirect(route('lista-espera.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lista_espera_citas', ['paciente_id' => $patient->id, 'estado' => 'esperando']);
        $this->assertDatabaseCount('citas_medicas', 0);
        $this->post(route('lista-espera.store'), $data)->assertSessionHasErrors('lista_espera');
        $id = DB::table('lista_espera_citas')->value('id');
        $this->get(route('lista-espera.index'))->assertOk()->assertSee($specialty->nombre);
        $this->post(route('lista-espera.update', $id), ['estado' => 'cancelado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lista_espera_citas', ['id' => $id, 'estado' => 'cancelado']);
    }

    public function test_patient_cannot_enroll_or_cancel_for_another_patient(): void
    {
        [$user, $patient, $specialty] = $this->patient();
        $other = Paciente::factory()->create();
        $this->login($user);
        $data = ['paciente_id' => $other->id, 'especialidad_id' => $specialty->id, 'fecha' => now()->addDay()->toDateString()];
        $this->post(route('lista-espera.store'), $data)->assertSessionHasErrors('paciente_id');
        $data['paciente_id'] = $patient->id;
        $this->post(route('lista-espera.store'), $data)->assertSessionHasNoErrors();
        $id = DB::table('lista_espera_citas')->value('id');
        $this->post(route('lista-espera.update', $id), ['estado' => 'finalizado'])->assertForbidden();
        $this->post('/logout');
        [$otherUser] = $this->patient();
        $this->login($otherUser);
        $this->get(route('lista-espera.index'))->assertOk()->assertDontSee($specialty->nombre);
        $this->post(route('lista-espera.update', $id), ['estado' => 'cancelado'])->assertForbidden();
    }

    public function test_reception_contacts_and_closes_the_request(): void
    {
        [$user, $patient, $specialty] = $this->patient();
        $this->login($user);
        $this->post(route('lista-espera.store'), ['especialidad_id' => $specialty->id, 'fecha' => now()->addDay()->toDateString()]);
        $id = DB::table('lista_espera_citas')->value('id');
        $this->post('/logout');
        $reception = $this->accountWithRole('recepcion');
        $this->login($reception);
        $this->get(route('lista-espera.index'))->assertOk()->assertSee($patient->telefono)->assertSee('Consultar turnos');
        $this->post(route('lista-espera.update', $id), ['estado' => 'contactado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lista_espera_citas', ['id' => $id, 'estado' => 'contactado', 'actualizado_por' => $reception->id]);
        $this->post(route('lista-espera.update', $id), ['estado' => 'finalizado'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('lista_espera_citas', ['id' => $id, 'estado' => 'finalizado']);
    }

    public function test_invalid_date_and_unrelated_doctor_are_rejected(): void
    {
        [$user, $patient, $specialty] = $this->patient();
        $this->login($user);
        $doctor = Medico::factory()->create();
        $this->post(route('lista-espera.store'), ['especialidad_id' => $specialty->id, 'fecha' => now()->subDay()->toDateString()])->assertSessionHasErrors('fecha');
        $this->post(route('lista-espera.store'), ['especialidad_id' => $specialty->id, 'medico_id' => $doctor->id, 'fecha' => now()->addDay()->toDateString()])->assertSessionHasErrors('medico_id');
        $this->assertDatabaseCount('lista_espera_citas', 0);
    }
}
