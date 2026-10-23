<?php

namespace Tests\Feature\Citas;

use App\Services\Citas\AsistenciaService;
use App\Services\Citas\CancelacionService;
use App\Services\Citas\ReprogramacionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\Support\CreatesAppointments;
use Tests\TestCase;

class CitaIntegrationTest extends TestCase
{
    use CreatesAdministrativeUsers, CreatesAppointments, DatabaseTransactions;

    public function test_reschedule_confirm_cancel_and_history_share_one_appointment_and_audit_trail(): void
    {
        $actor = $this->loginManager();
        $cita = $this->createAppointment(['motivo' => 'Motivo privado que no debe aparecer en el historial']);
        $patientId = $cita->paciente_id;
        $newStart = $cita->inicio->addHour();
        $cita = app(ReprogramacionService::class)->reprogramar($actor, $cita->id, [
            'version' => $cita->editVersion(), 'medico_id' => $cita->medico_id,
            'horario_id' => $cita->horario_id, 'fecha' => $newStart->format('Y-m-d'), 'inicio' => $newStart->format('H:i'),
        ])->fresh();
        $cita = app(AsistenciaService::class)->changeState($actor, $cita->id, ['version' => $cita->editVersion(), 'estado' => 'confirmada', 'confirmacion' => '1'])->fresh();
        $cita = app(CancelacionService::class)->cancelar($actor, $cita->id, ['version' => $cita->editVersion(), 'motivo' => 'Cambio de planes', 'confirmar' => '1'])->fresh();
        $this->assertSame($patientId, $cita->paciente_id);
        $this->assertSame('cancelada', $cita->estado);
        $this->assertDatabaseCount('citas_medicas', 1);
        $this->assertSame(['REPROGRAMAR', 'CAMBIAR_ESTADO', 'CANCELAR'], DB::table('cambios_cita')->orderBy('id')->pluck('accion')->all());
        $this->get(route('historial.index', ['paciente_id' => $patientId]))->assertOk()->assertSee($cita->codigo)->assertSee('Cancelada')->assertDontSee($cita->motivo)->assertDontSee('Cambio de planes');
    }

    public function test_operational_list_scopes_doctors_and_keeps_management_actions_for_staff(): void
    {
        $doctor = $this->accountWithRole('medico');
        $own = $this->createAppointment();
        $other = $this->createAppointment();
        DB::table('medicos')->where('id', $own->medico_id)->update(['usuario_id' => $doctor->id]);
        $this->post('/login', ['identificador' => $doctor->correo, 'password' => 'password']);
        $this->get('/citas')->assertOk()->assertSee($own->codigo)->assertDontSee($other->codigo)
            ->assertSee(route('citas.asistencia.edit', $own), false)
            ->assertDontSee(route('citas.cancelar.edit', $own), false)
            ->assertDontSee(route('citas.reprogramar.edit', $own), false);
        $this->post('/logout');
        $this->loginManager();
        $this->get('/citas')->assertOk()->assertSee($own->codigo)->assertSee($other->codigo)
            ->assertSee(route('citas.cancelar.edit', $own), false)->assertSee(route('citas.reprogramar.edit', $own), false);
    }

    public function test_operational_list_denies_guests_and_patient_accounts(): void
    {
        $this->get('/citas')->assertRedirect(route('login'));
        $patient = $this->accountWithRole('paciente');
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password']);
        $this->get('/citas')->assertForbidden();
    }
}
