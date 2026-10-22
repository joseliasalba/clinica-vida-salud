<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppointmentConstraintTest extends TestCase
{
    use DatabaseTransactions;

    public function test_postgresql_rejects_overlaps_for_the_same_doctor(): void
    {
        $cita = $this->datosCita();
        DB::table('citas_medicas')->insert($cita);
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23P01');
        DB::table('citas_medicas')->insert(array_replace($cita, [
            'codigo' => 'CITA-2', 'inicio' => '2026-10-22 09:15:00', 'fin' => '2026-10-22 09:45:00',
        ]));
    }

    public function test_adjacent_appointments_and_cancelled_slots_are_allowed(): void
    {
        $cita = $this->datosCita();
        DB::table('citas_medicas')->insert($cita);
        DB::table('citas_medicas')->insert(array_replace($cita, [
            'codigo' => 'CITA-2', 'inicio' => $cita['fin'], 'fin' => '2026-10-22 10:00:00',
        ]));
        DB::table('citas_medicas')->where('codigo', 'CITA-1')->update(['estado' => 'cancelada']);
        DB::table('citas_medicas')->insert(array_replace($cita, ['codigo' => 'CITA-3']));
        $this->assertDatabaseCount('citas_medicas', 3);
    }

    public function test_failed_rescheduling_rolls_back_the_original_appointment(): void
    {
        $cita = $this->datosCita();
        DB::table('citas_medicas')->insert($cita);
        DB::table('citas_medicas')->insert(array_replace($cita, [
            'codigo' => 'CITA-2', 'inicio' => '2026-10-22 10:00:00', 'fin' => '2026-10-22 10:30:00',
        ]));

        try {
            DB::transaction(fn () => DB::table('citas_medicas')->where('codigo', 'CITA-1')->update([
                'inicio' => '2026-10-22 10:15:00', 'fin' => '2026-10-22 10:45:00',
            ]));
            $this->fail('PostgreSQL debe rechazar la reprogramación superpuesta.');
        } catch (QueryException $exception) {
            $this->assertSame('23P01', $exception->getCode());
        }

        $this->assertDatabaseHas('citas_medicas', ['codigo' => 'CITA-1', 'inicio' => $cita['inicio'], 'fin' => $cita['fin']]);
    }

    /** @return array<string, int|string> */
    private function datosCita(): array
    {
        $pacienteId = DB::table('pacientes')->insertGetId([
            'codigo' => 'PAC-1', 'nombres' => 'Ana', 'apellidos' => 'Prueba',
            'fecha_nacimiento' => '2000-01-01', 'telefono' => '70000000',
        ]);
        $medicoId = DB::table('medicos')->insertGetId([
            'codigo' => 'MED-1', 'registro_profesional' => 'REG-1', 'nombres' => 'Luis',
            'apellidos' => 'Prueba', 'telefono' => '70000001', 'correo' => 'medico@example.test',
        ]);
        $especialidadId = DB::table('especialidades')->insertGetId([
            'nombre' => 'General', 'nombre_normalizado' => 'general',
        ]);
        $horarioId = DB::table('horarios_atencion')->insertGetId([
            'medico_id' => $medicoId, 'dia_semana' => 4, 'hora_inicio' => '09:00',
            'hora_fin' => '12:00', 'duracion_turno_min' => 30, 'vigente_desde' => '2026-01-01',
        ]);

        return [
            'codigo' => 'CITA-1', 'paciente_id' => $pacienteId, 'medico_id' => $medicoId,
            'especialidad_id' => $especialidadId, 'horario_id' => $horarioId,
            'inicio' => '2026-10-22 09:00:00', 'fin' => '2026-10-22 09:30:00',
            'estado' => 'programada', 'creada_por' => User::factory()->create()->id,
        ];
    }
}
