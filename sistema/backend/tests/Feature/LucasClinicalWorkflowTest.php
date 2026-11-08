<?php

namespace Tests\Feature;

use App\Models\CitaMedica;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class LucasClinicalWorkflowTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_reports_filter_appointments_and_count_distinct_patients(): void
    {
        $patient = Paciente::factory()->create();
        $first = CitaMedica::factory()->create(['paciente_id' => $patient->id, 'estado' => 'cancelada']);
        CitaMedica::factory()->create(['paciente_id' => $patient->id, 'estado' => 'cancelada']);
        $outside = CitaMedica::factory()->create();

        $this->signIn($this->accountWithRole('recepcion'));
        $this->get('/reportes')->assertForbidden();
        $this->signIn($this->accountWithRole());
        $this->get('/reportes?estado=cancelada')->assertOk()
            ->assertViewHas('totalPacientes', 1)
            ->assertViewHas('citas', fn ($citas) => $citas->total() === 2)
            ->assertSee($first->codigo)->assertDontSee($outside->codigo);
    }

    public function test_patient_saves_only_their_own_declared_health_and_emergency_contact(): void
    {
        $user = $this->accountWithRole('paciente');
        $patient = Paciente::factory()->create(['usuario_id' => $user->id]);
        $foreign = Paciente::factory()->create();
        $this->signIn($user);

        $this->patch('/mi-perfil', [
            'paciente_id' => $foreign->id, 'telefono' => '+591 70000123', 'estado_alergias' => 'declaradas',
            'detalle_alergias' => 'Alergia ficticia', 'contacto_nombres' => 'Ana', 'contacto_apellidos' => 'Prueba',
            'contacto_parentesco' => 'Hermana', 'contacto_telefono' => '+591 70000456',
        ])->assertRedirect(route('perfil.edit'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('perfiles_salud_declarados', ['paciente_id' => $patient->id, 'detalle_alergias' => 'Alergia ficticia']);
        $this->assertDatabaseHas('contactos_emergencia', ['paciente_id' => $patient->id, 'principal' => true, 'parentesco' => 'Hermana']);
        $this->assertDatabaseMissing('perfiles_salud_declarados', ['paciente_id' => $foreign->id]);
        $this->get('/mi-perfil')->assertOk()->assertSee('Alergia ficticia');
        $this->signIn($this->accountWithRole('recepcion'));
        $this->get('/mi-perfil')->assertForbidden();
    }

    public function test_only_assigned_doctor_can_record_treatment_and_request_study_from_consultation(): void
    {
        $doctor = $this->accountWithRole('medico');
        $medico = Medico::factory()->create(['usuario_id' => $doctor->id]);
        $other = $this->accountWithRole('medico');
        Medico::factory()->create(['usuario_id' => $other->id]);
        $start = now()->subHours(2);
        $appointment = CitaMedica::factory()->create([
            'horario_id' => HorarioAtencion::factory()->create(['medico_id' => $medico->id])->id,
            'estado' => 'completada', 'inicio' => $start, 'fin' => $start->copy()->addMinutes(30),
        ]);
        $data = ['evaluacion' => 'Evaluación de prueba', 'indicaciones' => 'Control en una semana'];
        $url = '/consultas/'.$appointment->id;

        $this->signIn($other);
        $this->post($url, $data)->assertNotFound();
        $this->signIn($doctor);
        $this->get($url)->assertOk()->assertSee('Registrar consulta');
        $this->post($url, $data)->assertRedirect($url)->assertSessionHasNoErrors();

        $consultation = DB::table('consultas_medicas')->where('cita_id', $appointment->id)->first();
        $this->assertSame($medico->id, $consultation->medico_id);
        $this->assertDatabaseHas('historias_clinicas', ['id' => $consultation->historia_id, 'paciente_id' => $appointment->paciente_id]);
        $this->assertDatabaseHas('tratamientos', ['consulta_id' => $consultation->id, 'indicaciones' => $data['indicaciones']]);
        $this->get($url)->assertOk()->assertSee(route('estudios.create', ['consulta_id' => $consultation->id]));
        $this->post('/estudios', ['consulta_id' => $consultation->id, 'nombre_estudio' => 'Hemograma'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('solicitudes_estudio', ['consulta_id' => $consultation->id, 'nombre_estudio' => 'Hemograma']);
        $this->post($url, $data)->assertSessionHasErrors('evaluacion');
    }

    private function signIn(User $user): void
    {
        $this->actingAs($user)->withSession([
            'auth_last_activity' => now()->timestamp,
            'auth_password_hash' => $user->getAuthPassword(),
            'auth_session_token' => $user->remember_token ?? '',
        ]);
    }
}
