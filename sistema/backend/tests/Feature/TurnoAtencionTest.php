<?php

namespace Tests\Feature;

use App\Models\CitaMedica;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\PagoConsulta;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class TurnoAtencionTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    private function login(User $user): void
    {
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
    }

    private function cita(array $attributes = []): CitaMedica
    {
        return CitaMedica::factory()->create(array_merge(['inicio' => now()->setTime(10, 0), 'fin' => now()->setTime(10, 30)], $attributes))->fresh();
    }

    public function test_reception_registers_arrival_once_and_logs_it(): void
    {
        $reception = $this->accountWithRole('recepcion');
        $this->login($reception);
        $cita = $this->cita();
        $this->post(route('turnos.llegada', $cita), ['version' => $cita->editVersion()])->assertSessionHasNoErrors()->assertRedirect(route('turnos.index'));
        $this->assertDatabaseHas('llegadas_citas', ['cita_id' => $cita->id, 'registrada_por' => $reception->id]);
        $this->assertDatabaseHas('cambios_cita', ['cita_id' => $cita->id, 'accion' => 'REGISTRAR_LLEGADA']);
        $this->post(route('turnos.llegada', $cita), ['version' => $cita->editVersion()])->assertSessionHasErrors('llegada');
        $this->assertDatabaseCount('llegadas_citas', 1);
    }

    public function test_arrival_rejects_unpaid_other_days_and_stale_citations(): void
    {
        $this->loginManager();
        $unpaid = $this->cita();
        PagoConsulta::factory()->create(['cita_id' => $unpaid->id]);
        $this->post(route('turnos.llegada', $unpaid), ['version' => $unpaid->editVersion()])->assertSessionHasErrors('llegada');
        $future = $this->cita(['inicio' => now()->addDay()->setTime(10, 0), 'fin' => now()->addDay()->setTime(10, 30)]);
        $this->post(route('turnos.llegada', $future), ['version' => $future->editVersion()])->assertSessionHasErrors('llegada');
        $this->post(route('turnos.llegada', $future), ['version' => str_repeat('a', 64)])->assertSessionHasErrors('version');
        $this->assertDatabaseCount('llegadas_citas', 0);
    }

    public function test_patient_only_sees_own_status_and_count_of_arrivals_for_same_doctor(): void
    {
        $this->loginManager();
        $user = $this->accountWithRole('paciente');
        $user->forceFill(['email_verified_at' => now()])->save();
        $patient = Paciente::factory()->create(['usuario_id' => $user->id]);
        $first = $this->cita();
        $second = $this->cita(['paciente_id' => $patient->id, 'medico_id' => $first->medico_id, 'inicio' => now()->setTime(11, 0), 'fin' => now()->setTime(11, 30)]);
        $this->post(route('turnos.llegada', $first), ['version' => $first->editVersion()]);
        $this->post(route('turnos.llegada', $second), ['version' => $second->editVersion()]);
        $this->post('/logout');
        $this->login($user);
        $this->get(route('turnos.index'))->assertOk()->assertSee($second->codigo)->assertDontSee($first->codigo)->assertDontSee($first->paciente->nombres)->assertSee('Personas antes')->assertSee('>1<', false);
        $this->post(route('turnos.llegada', $second), ['version' => $second->editVersion()])->assertForbidden();
        $first->update(['estado' => 'cancelada']);
        $this->get(route('turnos.index'))->assertSee('>0<', false);
    }

    public function test_doctor_only_sees_own_patients_and_rescheduling_invalidates_arrival(): void
    {
        $this->loginManager();
        $doctor = $this->accountWithRole('medico');
        $own = Medico::factory()->create(['usuario_id' => $doctor->id]);
        $cita = $this->cita(['medico_id' => $own->id]);
        $other = $this->cita();
        $this->post(route('turnos.llegada', $cita), ['version' => $cita->editVersion()]);
        $this->post(route('turnos.llegada', $other), ['version' => $other->editVersion()]);
        $this->post('/logout');
        $this->login($doctor);
        $this->get(route('turnos.index'))->assertOk()->assertSee($cita->codigo)->assertDontSee($other->codigo);
        $this->post(route('turnos.llegada', $cita), ['version' => $cita->editVersion()])->assertForbidden();
        $cita->update(['inicio' => now()->setTime(11, 0), 'fin' => now()->setTime(11, 30)]);
        $this->get(route('turnos.index'))->assertDontSee($cita->codigo);
        $this->post('/logout');
        $this->loginManager();
        $cita = $cita->fresh();
        $this->post(route('turnos.llegada', $cita), ['version' => $cita->editVersion()])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('llegadas_citas', ['cita_id' => $cita->id, 'inicio_programado' => $cita->inicio->format('Y-m-d H:i:s')]);
        $cita->update(['medico_id' => Medico::factory()->create()->id]);
        $this->get(route('turnos.index'))->assertDontSee($cita->codigo);
    }
}
