<?php

namespace Tests\Feature\Citas;

use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Services\Citas\AppointmentBooking;
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

    public function test_booking_can_be_rescheduled_cancelled_and_rebooked_with_history_preserved(): void
    {
        $this->travelTo(now('America/La_Paz')->setDate(2026, 11, 2)->setTime(8, 0));
        $actor = $this->loginManager();
        $doctor = Medico::factory()->create();
        $specialty = Especialidad::factory()->create();
        $doctor->especialidades()->attach($specialty->id, ['activo' => true]);
        $schedule = HorarioAtencion::factory()->create(['medico_id' => $doctor->id]);
        $patient = Paciente::factory()->create();
        $booking = app(AppointmentBooking::class);
        $data = ['paciente_id' => $patient->id, 'especialidad_id' => $specialty->id,
            'medico_id' => $doctor->id, 'horario_id' => $schedule->id,
            'fecha' => '2026-11-02', 'inicio' => '2026-11-02 09:00:00'];
        $cita = $booking->book($actor, $booking->review($actor, $data)['token'])->fresh();
        $token = $cita->reserva_token;
        $this->get(route('citas.gestion'))->assertOk()->assertSee($cita->codigo);
        $cita = app(ReprogramacionService::class)->reprogramar($actor, $cita->id, [
            'version' => $cita->editVersion(), 'medico_id' => $doctor->id,
            'horario_id' => $schedule->id, 'fecha' => '2026-11-02', 'inicio' => '10:00',
        ])->fresh();
        $cita = app(CancelacionService::class)->cancelar($actor, $cita->id, [
            'version' => $cita->editVersion(), 'motivo' => 'Cambio de planes', 'confirmar' => '1',
        ])->fresh();
        $this->assertSame($token, $cita->reserva_token);
        $data['inicio'] = '2026-11-02 10:00:00';
        $replacement = $booking->book($actor, $booking->review($actor, $data)['token']);
        $this->assertNotSame($cita->id, $replacement->id);
        $this->assertSame(['creacion', 'REPROGRAMAR', 'CANCELAR'], DB::table('cambios_cita')->where('cita_id', $cita->id)->orderBy('id')->pluck('accion')->all());
        $this->get(route('pacientes.historial', $patient))->assertOk()->assertSee($cita->codigo)->assertSee($replacement->codigo)->assertSee('Cancelada')->assertSee('Programada');
    }

    public function test_operational_list_scopes_doctors_and_keeps_management_actions_for_staff(): void
    {
        $doctor = $this->accountWithRole('medico');
        $own = $this->createAppointment();
        $other = $this->createAppointment();
        DB::table('medicos')->where('id', $own->medico_id)->update(['usuario_id' => $doctor->id]);
        $this->post('/login', ['identificador' => $doctor->correo, 'password' => 'password']);
        $this->get('/citas/gestion')->assertOk()->assertSee($own->codigo)->assertDontSee($other->codigo)
            ->assertSee(route('citas.asistencia.edit', $own), false)
            ->assertDontSee(route('citas.cancelar.edit', $own), false)
            ->assertDontSee(route('citas.reprogramar.edit', $own), false);
        $this->post('/logout');
        $this->loginManager();
        $this->get('/citas/gestion')->assertOk()->assertSee($own->codigo)->assertSee($other->codigo)
            ->assertSee(route('citas.cancelar.edit', $own), false)->assertSee(route('citas.reprogramar.edit', $own), false);
    }

    public function test_operational_list_denies_guests_and_patient_accounts(): void
    {
        $this->get('/citas/gestion')->assertRedirect(route('login'));
        $patient = $this->accountWithRole('paciente');
        $this->post('/login', ['identificador' => $patient->correo, 'password' => 'password']);
        $this->get('/citas/gestion')->assertForbidden();
    }
}
