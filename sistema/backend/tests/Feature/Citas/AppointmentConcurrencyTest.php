<?php

namespace Tests\Feature\Citas;

use App\Models\Especialidad;
use App\Models\HorarioAtencion;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\User;
use App\Services\Citas\AppointmentBooking;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AppointmentConcurrencyTest extends TestCase
{
    #[DataProvider('races')]
    public function test_concurrent_booking_and_agenda_changes_remain_consistent(string $mode): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(8, 0));
        $role = DB::table('roles')->insertGetId(['nombre' => 'booking_race_'.bin2hex(random_bytes(5)), 'activo' => true]);
        $createdPermissions = [];
        foreach (['citas.gestionar', 'horarios.gestionar'] as $code) {
            $permission = DB::table('permisos')->where('codigo', $code)->value('id');
            if (! $permission) {
                $permission = DB::table('permisos')->insertGetId(['codigo' => $code, 'accion' => $code, 'activo' => true]);
                $createdPermissions[] = $permission;
            }
            DB::table('rol_permiso')->insert(['rol_id' => $role, 'permiso_id' => $permission, 'activo' => true]);
        }
        $actor = User::factory()->create();
        DB::table('usuario_rol')->insert(['usuario_id' => $actor->id, 'rol_id' => $role, 'fecha_asignacion' => now()->subMinute(), 'activo' => true]);
        $specialty = Especialidad::factory()->create();
        $doctors = Medico::factory()->count(2)->create();
        $patients = Paciente::factory()->count(2)->create();
        $schedules = collect();
        $processes = [];
        try {
            foreach ($doctors as $doctor) {
                $doctor->especialidades()->attach($specialty->id, ['activo' => true]);
                $schedules->push(HorarioAtencion::factory()->create(['medico_id' => $doctor->id]));
            }
            $reviewData = ['especialidad_id' => $specialty->id, 'fecha' => '2026-11-02', 'inicio' => '2026-11-02 09:00:00'];
            $tokens = [];
            for ($index = 0; $index < 2; $index++) {
                $doctorIndex = in_array($mode, ['patient', 'sql_patient']) ? $index : 0;
                $patientIndex = $mode === 'doctor' ? $index : 0;
                $tokens[] = app(AppointmentBooking::class)->review($actor, $reviewData + ['paciente_id' => $patients[$patientIndex]->id, 'medico_id' => $doctors[$doctorIndex]->id, 'horario_id' => $schedules[$doctorIndex]->id])['token'];
            }
            for ($index = 0; $index < 2; $index++) {
                $payload = ['actor_id' => $actor->id, 'action' => 'book', 'token' => $tokens[$mode === 'retry' ? 0 : $index]];
                if ($index === 1 && $mode === 'block') {
                    $payload = ['actor_id' => $actor->id, 'action' => 'block', 'data' => ['medico_id' => $doctors[0]->id, 'inicio' => '2026-11-02T09:00', 'fin' => '2026-11-02T10:00', 'motivo' => 'Bloqueo concurrente']];
                }
                if ($index === 1 && $mode === 'schedule') {
                    $payload = ['actor_id' => $actor->id, 'action' => 'schedule', 'schedule_id' => $schedules[0]->id, 'data' => ['version' => $schedules[0]->editVersion(), 'activo' => false]];
                }
                if ($mode === 'sql_patient') {
                    $payload = ['actor_id' => $actor->id, 'action' => 'sql', 'data' => ['codigo' => 'RACE-'.bin2hex(random_bytes(8)), 'paciente_id' => $patients[0]->id, 'medico_id' => $doctors[$index]->id, 'especialidad_id' => $specialty->id, 'horario_id' => $schedules[$index]->id, 'inicio' => '2026-11-02 09:00:00', 'fin' => '2026-11-02 09:30:00', 'estado' => 'programada', 'creada_por' => $actor->id]];
                }
                $process = proc_open([PHP_BINARY, base_path('tests/Fixtures/Citas/book-appointment-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes];
                fwrite($pipes[0], json_encode($payload)."\n");
                fflush($pipes[0]);
            }
            foreach ($processes as [$process, $pipes]) {
                $this->assertSame("READY\n", fgets($pipes[1]));
            }
            foreach ($processes as [$process, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fclose($pipes[0]);
            }
            $results = [];
            foreach ($processes as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errors.$output);
                $this->assertJson($output, $errors.$output);
                $results[] = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
            }
            $this->assertSame($mode === 'retry' ? 2 : 1, array_sum(array_column($results, 'success')));
            $appointments = DB::table('citas_medicas')->whereIn('medico_id', $doctors->modelKeys())->get();
            if ($mode === 'retry') {
                $this->assertSame($results[0]['id'], $results[1]['id']);
            }
            if (in_array($mode, ['block', 'schedule'])) {
                $this->assertSame($results[0]['success'] ? 1 : 0, $appointments->count());
                if ($mode === 'block') {
                    $this->assertSame($appointments->isEmpty() ? 1 : 0, DB::table('indisponibilidades_medico')->where('medico_id', $doctors[0]->id)->where('activo', true)->count());
                } else {
                    $this->assertSame($appointments->isNotEmpty(), (bool) $schedules[0]->fresh()->activo);
                }
            } else {
                $this->assertCount(1, $appointments);
            }
            $this->assertSame($mode === 'sql_patient' ? 0 : $appointments->count(), DB::table('cambios_cita')->whereIn('cita_id', $appointments->pluck('id'))->count());
        } finally {
            foreach ($processes as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }
            $ids = DB::table('citas_medicas')->whereIn('medico_id', $doctors->modelKeys())->pluck('id');
            DB::table('cambios_cita')->whereIn('cita_id', $ids)->delete();
            DB::table('citas_medicas')->whereIn('id', $ids)->delete();
            DB::table('indisponibilidades_medico')->whereIn('medico_id', $doctors->modelKeys())->delete();
            DB::table('horarios_atencion')->whereIn('medico_id', $doctors->modelKeys())->delete();
            DB::table('medico_especialidad')->whereIn('medico_id', $doctors->modelKeys())->delete();
            Medico::query()->whereKey($doctors->modelKeys())->delete();
            Paciente::query()->whereKey($patients->modelKeys())->delete();
            $specialty->delete();
            DB::table('usuario_rol')->where('usuario_id', $actor->id)->delete();
            $actor->delete();
            DB::table('rol_permiso')->where('rol_id', $role)->delete();
            DB::table('roles')->where('id', $role)->delete();
            DB::table('permisos')->whereIn('id', $createdPermissions)->delete();
        }
    }

    public static function races(): array
    {
        return [['doctor'], ['patient'], ['retry'], ['block'], ['schedule'], ['sql_patient']];
    }
}
