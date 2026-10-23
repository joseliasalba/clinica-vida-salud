<?php

namespace Tests\Feature\Citas;

use App\Models\CitaMedica;
use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\IndisponibilidadMedico;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\Citas\AppointmentAvailability;
use App\Services\Citas\AppointmentBooking;
use App\Services\Horarios\ScheduleManagement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    private Medico $doctor;

    private Especialidad $specialty;

    private HorarioAtencion $schedule;

    private Paciente $patient;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(8, 0));
        $this->actor = $this->loginManager();
        $this->doctor = Medico::factory()->create(['nombres' => str_repeat('Nombre ', 18)]);
        $this->specialty = Especialidad::factory()->create();
        $this->doctor->especialidades()->attach($this->specialty->id, ['activo' => true]);
        $this->schedule = HorarioAtencion::factory()->create(['medico_id' => $this->doctor->id]);
        $this->patient = Paciente::factory()->create();
    }

    private function data(array $changes = []): array
    {
        return array_replace(['paciente_id' => $this->patient->id, 'especialidad_id' => $this->specialty->id, 'medico_id' => $this->doctor->id, 'horario_id' => $this->schedule->id, 'fecha' => '2026-11-02', 'inicio' => '2026-11-02 09:00:00'], $changes);
    }

    private function review(): array
    {
        return app(AppointmentBooking::class)->review($this->actor, $this->data());
    }

    private function loginPatient(bool $verified = true, bool $linked = true): User
    {
        $this->post('/logout');
        $user = $this->accountWithRole('paciente');
        $user->forceFill(['email_verified_at' => $verified ? now() : null])->save();
        if ($linked) {
            $this->patient->forceFill(['usuario_id' => $user->id])->save();
        }
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));

        return $user;
    }

    public function test_staff_can_consult_review_reserve_and_print_without_a_patient_account(): void
    {
        $this->get(route('citas.index'))->assertOk()->assertSee('Buscar paciente');
        $this->get(route('citas.index', ['paciente_q' => $this->patient->codigo]))->assertOk()->assertSee($this->patient->nombres);
        $this->get(route('citas.index', ['paciente_q' => $this->patient->codigo, 'especialidad_id' => $this->specialty->id, 'accion' => 'buscar_paciente']))->assertOk()->assertSee($this->patient->nombres)->assertDontSee('Turnos disponibles');
        $this->get(route('citas.index', ['accion' => 'consultar']))->assertSessionHasErrors('especialidad_id');
        $this->get(route('citas.index', $this->data()))->assertOk()->assertSee('6 opciones')->assertSee($this->doctor->nombres);
        $this->get(route('citas.review', $this->data()))->assertOk()->assertSee('Revisa tu reserva.')->assertSee('Reservar cita');
        $this->assertDatabaseCount('citas_medicas', 0);
        $response = $this->post(route('citas.store'), ['token' => $this->review()['token'], 'estado' => 'confirmada', 'fin' => '2099-01-01 00:00:00']);
        $cita = CitaMedica::query()->sole();
        $response->assertRedirect(route('citas.receipt', $cita))->assertSessionHasNoErrors();
        $this->assertSame('programada', $cita->estado);
        $this->assertSame('2026-11-02 09:30:00', $cita->fin->format('Y-m-d H:i:s'));
        $this->assertStringStartsWith('CIT-', $cita->codigo);
        $this->assertSame($this->actor->id, $cita->creada_por);
        $this->assertDatabaseHas('cambios_cita', ['cita_id' => $cita->id, 'accion' => 'creacion', 'usuario_id' => $this->actor->id]);
        $this->get(route('citas.receipt', $cita))->assertOk()->assertSee($cita->codigo)->assertSee('Imprimir / guardar PDF')->assertSee('programada');
    }

    public function test_patient_can_only_book_their_active_linked_file(): void
    {
        $user = $this->loginPatient();
        $this->get(route('citas.index'))->assertOk()->assertDontSee('Buscar paciente');
        $review = app(AppointmentBooking::class)->review($user, $this->data());
        $this->post(route('citas.store'), ['token' => $review['token']])->assertSessionHasNoErrors();
        $other = Paciente::factory()->create();
        $this->get(route('citas.review', $this->data(['paciente_id' => $other->id])))->assertRedirect(route('citas.index'))->assertSessionHasErrors('paciente_id');
        $this->assertDatabaseCount('citas_medicas', 1);
    }

    public function test_unverified_patient_is_directed_to_verification_and_cannot_post_a_reservation(): void
    {
        $user = $this->loginPatient(false);
        $this->get(route('citas.index'))->assertRedirect(route('verification.notice'));
        $this->expectException(HttpException::class);
        app(AppointmentBooking::class)->review($user, $this->data());
    }

    public function test_unlinked_patient_sees_reception_guidance_and_cannot_select_another_file(): void
    {
        $this->loginPatient(linked: false);
        $this->get(route('citas.index'))->assertOk()->assertSee('Solicita ayuda en recepción');
        $this->get(route('citas.review', $this->data()))->assertSessionHasErrors('paciente_id');
    }

    public function test_only_staff_and_the_owner_can_read_a_receipt(): void
    {
        $cita = app(AppointmentBooking::class)->book($this->actor, $this->review()['token']);
        $owner = $this->loginPatient();
        $this->get(route('citas.receipt', $cita))->assertOk();
        $this->post('/logout');
        $stranger = $this->accountWithRole('paciente');
        $this->post('/login', ['identificador' => $stranger->correo, 'password' => 'password']);
        $this->get(route('citas.receipt', $cita))->assertForbidden();
        $this->post('/logout');
        $doctor = $this->accountWithRole('medico');
        $this->post('/login', ['identificador' => $doctor->correo, 'password' => 'password']);
        $this->get(route('citas.index'))->assertForbidden();
        $this->get(route('citas.receipt', $cita))->assertForbidden();
        $this->post(route('citas.store'), [])->assertForbidden();
        $this->post('/logout');
        $this->get(route('citas.index'))->assertRedirect(route('login'));
        $this->get(route('citas.receipt', $cita))->assertRedirect(route('login'));
    }

    public function test_retry_of_the_same_review_returns_one_appointment_and_one_audit(): void
    {
        $token = $this->review()['token'];
        $first = app(AppointmentBooking::class)->book($this->actor, $token);
        $this->travel(31)->minutes();
        $second = app(AppointmentBooking::class)->book($this->actor, $token);
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('citas_medicas', 1);
        $this->assertDatabaseCount('cambios_cita', 1);
    }

    public function test_expired_or_tampered_review_is_rejected_without_writes(): void
    {
        $token = $this->review()['token'];
        $this->post(route('citas.store'), ['token' => $token.'x'])->assertSessionHasErrors('reserva');
        $this->travel(31)->minutes();
        $this->withSession(['auth_last_activity' => now()->timestamp]);
        $this->post(route('citas.store'), ['token' => $token])->assertSessionHasErrors('reserva');
        $this->assertDatabaseCount('citas_medicas', 0);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public function test_review_token_is_bound_to_the_authenticated_actor(): void
    {
        $token = $this->review()['token'];
        $this->loginPatient();
        $this->post(route('citas.store'), ['token' => $token])->assertForbidden();
        $this->assertDatabaseCount('citas_medicas', 0);
    }

    #[DataProvider('changesBeforeBooking')]
    public function test_booking_revalidates_every_dependency(string $change): void
    {
        $token = $this->review()['token'];
        match ($change) {
            'doctor' => $this->doctor->forceFill(['activo' => false])->save(),
            'specialty' => $this->specialty->forceFill(['activo' => false])->save(),
            'assignment' => $this->doctor->especialidades()->updateExistingPivot($this->specialty->id, ['activo' => false]),
            'schedule' => $this->schedule->forceFill(['activo' => false])->save(),
            'patient' => $this->patient->forceFill(['activo' => false])->save(),
            'block' => IndisponibilidadMedico::factory()->create(['medico_id' => $this->doctor->id, 'inicio' => '2026-11-02 09:15:00', 'fin' => '2026-11-02 09:45:00']),
            'duration' => $this->schedule->forceFill(['duracion_turno_min' => 60])->save(),
            'validity' => $this->schedule->forceFill(['vigente_desde' => '2026-11-09'])->save(),
        };
        $this->post(route('citas.store'), ['token' => $token])->assertRedirect(route('citas.index'))->assertSessionHasErrors();
        $this->assertDatabaseCount('citas_medicas', 0);
        $this->assertDatabaseCount('cambios_cita', 0);
    }

    public static function changesBeforeBooking(): array
    {
        return array_map(fn ($value) => [$value], ['doctor', 'specialty', 'assignment', 'schedule', 'patient', 'block', 'duration', 'validity']);
    }

    public function test_revoked_permission_is_rechecked_at_booking(): void
    {
        $token = $this->review()['token'];
        DB::table('usuario_rol')->where('usuario_id', $this->actor->id)->update(['activo' => false]);
        $this->post(route('citas.store'), ['token' => $token])->assertForbidden();
        $this->assertDatabaseCount('citas_medicas', 0);
    }

    public function test_patient_conflicts_are_excluded_even_with_a_different_doctor(): void
    {
        $otherDoctor = Medico::factory()->create();
        $otherSchedule = HorarioAtencion::factory()->create(['medico_id' => $otherDoctor->id]);
        CitaMedica::factory()->create(['paciente_id' => $this->patient->id, 'horario_id' => $otherSchedule->id, 'inicio' => '2026-11-02 09:15:00', 'fin' => '2026-11-02 09:45:00']);
        $slots = app(AppointmentAvailability::class)->slots($this->specialty->id, '2026-11-02', null, $this->patient->id);
        $this->assertCount(4, $slots);
        $this->assertSame('2026-11-02 10:00:00', $slots->first()['inicio']);
    }

    public function test_two_reviews_for_the_same_slot_cannot_both_be_booked(): void
    {
        $first = $this->review()['token'];
        $second = $this->review()['token'];
        app(AppointmentBooking::class)->book($this->actor, $first);
        $this->post(route('citas.store'), ['token' => $second])->assertRedirect(route('citas.index'))->assertSessionHasErrors('reserva');
        $this->assertDatabaseCount('citas_medicas', 1);
        $this->assertDatabaseCount('cambios_cita', 1);
    }

    public function test_blocks_exclude_partial_overlaps_and_allow_touching_boundaries(): void
    {
        IndisponibilidadMedico::factory()->create(['medico_id' => $this->doctor->id, 'inicio' => '2026-11-02 09:30:00', 'fin' => '2026-11-02 10:15:00']);
        $slots = app(AppointmentAvailability::class)->slots($this->specialty->id, '2026-11-02');
        $this->assertCount(4, $slots);
        $this->assertSame('2026-11-02 09:00:00', $slots->first()['inicio']);
        $this->assertSame('2026-11-02 10:30:00', $slots[1]['inicio']);
    }

    public function test_only_cancelled_and_no_show_appointments_release_the_slot(): void
    {
        $cita = app(AppointmentBooking::class)->book($this->actor, $this->review()['token']);
        foreach (['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'inasistencia'] as $state) {
            $cita->forceFill(['estado' => $state])->save();
            $this->assertCount(in_array($state, ['cancelada', 'inasistencia']) ? 6 : 5, app(AppointmentAvailability::class)->slots($this->specialty->id, '2026-11-02'));
        }
    }

    public function test_local_date_past_slots_and_horizon_are_respected(): void
    {
        $this->travelTo(now()->setTime(9, 1));
        $this->withSession(['auth_last_activity' => now()->timestamp]);
        $slots = app(AppointmentAvailability::class)->slots($this->specialty->id, '2026-11-02');
        $this->assertCount(5, $slots);
        $this->assertSame('2026-11-02 09:30:00', $slots->first()['inicio']);
        $this->get(route('citas.index', $this->data(['fecha' => '2026-11-01'])))->assertSessionHasErrors('fecha');
        $this->get(route('citas.index', $this->data(['fecha' => '2026-12-03'])))->assertSessionHasErrors('fecha');
        $this->get(route('citas.index', $this->data(['fecha' => ['2026-11-02']])))->assertSessionHasErrors('fecha');
        $this->get(route('citas.review', $this->data(['inicio' => '2026-11-02 09:17:00'])))->assertSessionHasErrors('reserva');
        $this->get(route('citas.index', $this->data(['fecha' => '2026-11-03'])))->assertOk()->assertSee('No hay turnos disponibles');
    }

    public function test_booking_protects_cu06_from_subsequent_conflicting_changes(): void
    {
        app(AppointmentBooking::class)->book($this->actor, $this->review()['token']);
        $this->expectException(ValidationException::class);
        app(ScheduleManagement::class)->saveBlock($this->actor, ['medico_id' => $this->doctor->id, 'inicio' => '2026-11-02T09:00', 'fin' => '2026-11-02T10:00', 'motivo' => 'Ausencia']);
    }

    public function test_public_entry_continues_to_booking_after_login_without_external_redirects(): void
    {
        $this->post('/logout');
        $this->get(route('citas.entry'))->assertRedirect(route('login'));
        $this->post('/login', ['identificador' => $this->actor->correo, 'password' => 'password', 'redirect' => 'https://example.test'])->assertRedirect(route('citas.index'));
        $this->get('/')->assertOk()->assertSee(route('citas.entry'));
        $this->get('/inicio')->assertOk()->assertSee(route('citas.index'));
    }

    public function test_pages_keep_long_patient_and_doctor_data_and_accessible_actions(): void
    {
        $this->patient->forceFill(['nombres' => str_repeat('Paciente ', 18), 'apellidos' => str_repeat('Apellido ', 18)])->save();
        $pages = [];
        $pages['disponibilidad'] = $this->get(route('citas.index', $this->data()))->assertOk()->assertSee($this->doctor->nombres)->assertSee('Revisar turno');
        $pages['revision'] = $this->get(route('citas.review', $this->data()))->assertOk()->assertSee($this->patient->apellidos)->assertSee('name="token"', false);
        $cita = app(AppointmentBooking::class)->book($this->actor, $this->review()['token']);
        $pages['comprobante'] = $this->get(route('citas.receipt', $cita))->assertOk()->assertSee($this->patient->nombres)->assertSee($this->doctor->nombres)->assertSee('data-print-receipt', false);
        foreach ($pages as $page) {
            $page->assertSee('name="viewport"', false)->assertSee('id="contenido"', false)->assertSee('data-navigation-open', false);
        }
        if ($directory = getenv('CU07_RENDER_DIR')) {
            File::ensureDirectoryExists($directory);
            foreach ($pages as $name => $page) {
                $html = preg_replace('~https?://[^/]+/build/~', '/build/', $page->getContent());
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
    }

    public function test_audit_failure_rolls_back_the_entire_booking(): void
    {
        $token = $this->review()['token'];
        DB::statement("CREATE FUNCTION pg_temp.reject_booking_audit() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'test audit failure'; END $$");
        DB::statement('CREATE TRIGGER reject_booking_audit BEFORE INSERT ON cambios_cita FOR EACH ROW EXECUTE FUNCTION pg_temp.reject_booking_audit()');
        try {
            app(AppointmentBooking::class)->book($this->actor, $token);
            $this->fail('La auditoría debe formar parte de la transacción.');
        } catch (QueryException) {
            $this->assertDatabaseCount('citas_medicas', 0);
        } finally {
            DB::statement('DROP TRIGGER reject_booking_audit ON cambios_cita');
        }
    }
}
