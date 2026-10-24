<?php

namespace Tests\Feature\Horarios;

use App\Models\Medico;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleConcurrencyTest extends TestCase
{
    #[DataProvider('modes')]
    public function test_two_processes_cannot_create_overlapping_schedules(string $mode): void
    {
        $roleId = DB::table('roles')->insertGetId(['nombre' => 'schedule_concurrent_'.bin2hex(random_bytes(6)), 'activo' => true]);
        $existingPermission = DB::table('permisos')->where('codigo', 'horarios.gestionar')->first();
        $permissionId = $existingPermission?->id ?? DB::table('permisos')->insertGetId(['codigo' => 'horarios.gestionar', 'accion' => 'horarios.gestionar', 'activo' => true]);
        $actor = User::factory()->create();
        $doctor = Medico::factory()->create();
        $processes = [];
        try {
            DB::table('rol_permiso')->insert(['rol_id' => $roleId, 'permiso_id' => $permissionId, 'activo' => true]);
            DB::table('usuario_rol')->insert(['usuario_id' => $actor->id, 'rol_id' => $roleId, 'fecha_asignacion' => now()->subMinute(), 'activo' => true]);
            $schedule = ['medico_id' => $doctor->id, 'dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '12:00', 'duracion_turno_min' => 30, 'vigente_desde' => '2026-11-02', 'vigente_hasta' => null, 'activo' => true];
            for ($index = 0; $index < 2; $index++) {
                $process = proc_open([PHP_BINARY, base_path('tests/Fixtures/Horarios/create-schedule-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes];
                fwrite($pipes[0], json_encode(['actor_id' => $actor->id, 'schedule' => $schedule, 'mode' => $mode])."\n");
                fflush($pipes[0]);
            }
            foreach ($processes as [$process, $pipes]) {
                $this->assertSame("READY\n", fgets($pipes[1]));
            }
            foreach ($processes as [$process, $pipes]) {
                fwrite($pipes[0], "GO\n");
                fclose($pipes[0]);
            }
            $successes = 0;
            $conflicts = 0;
            foreach ($processes as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errors.$output);
                $this->assertJson($output, $errors.$output);
                $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
                $successes += (int) $result['success'];
                $conflicts += (int) ($result['conflict'] ?? false);
            }
            $this->assertSame(1, $successes);
            $this->assertSame(1, $conflicts);
            $this->assertSame(1, DB::table('horarios_atencion')->where('medico_id', $doctor->id)->count());
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
            DB::table('horarios_atencion')->where('medico_id', $doctor->id)->delete();
            $doctor->delete();
            DB::table('usuario_rol')->where('usuario_id', $actor->id)->delete();
            $actor->delete();
            DB::table('rol_permiso')->where('rol_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
            if (! $existingPermission) {
                DB::table('permisos')->where('id', $permissionId)->delete();
            }
        }
    }

    public static function modes(): array
    {
        return [['service'], ['sql']];
    }
}
