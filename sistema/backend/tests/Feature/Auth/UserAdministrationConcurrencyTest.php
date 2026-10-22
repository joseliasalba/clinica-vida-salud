<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserAdministrationConcurrencyTest extends TestCase
{
    public function test_two_managers_cannot_deactivate_each_other_concurrently(): void
    {
        $roleId = DB::table('roles')->insertGetId(['nombre' => 'concurrent_'.bin2hex(random_bytes(6)), 'activo' => true]);
        $existingPermission = DB::table('permisos')->where('codigo', 'usuarios.gestionar')->first();
        $permissionId = $existingPermission?->id ?? DB::table('permisos')->insertGetId(['codigo' => 'usuarios.gestionar', 'accion' => 'usuarios.gestionar', 'activo' => true]);
        DB::table('rol_permiso')->insert(['rol_id' => $roleId, 'permiso_id' => $permissionId, 'activo' => true]);
        $first = User::factory()->create();
        $second = User::factory()->create();
        $processes = [];
        try {
            foreach ([$first, $second] as $user) {
                DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => $roleId, 'activo' => true, 'fecha_asignacion' => now()->subMinute()]);
            }
            foreach ([[$first, $second], [$second, $first]] as [$actor, $target]) {
                $process = proc_open([PHP_BINARY, base_path('tests/Fixtures/Auth/admin-mutation-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes, $actor->id, $target->id];
            }
            foreach ($processes as [$process, $pipes, $actorId, $targetId]) {
                fwrite($pipes[0], json_encode(['actor_id' => $actorId, 'target_id' => $targetId, 'role_id' => $roleId]));
                fclose($pipes[0]);
            }
            $successes = 0;
            foreach ($processes as [$process, $pipes]) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errors.$output);
                $successes += (int) json_decode($output, true, flags: JSON_THROW_ON_ERROR)['success'];
            }
            $this->assertSame(1, $successes);
            $this->assertSame(1, User::whereIn('id', [$first->id, $second->id])->where('activo', true)->count());
            $survivor = User::whereIn('id', [$first->id, $second->id])->where('activo', true)->firstOrFail();
            $this->assertTrue($survivor->tienePermiso('usuarios.gestionar'));
        } finally {
            foreach ($processes as [$process]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
            DB::table('usuario_rol')->whereIn('usuario_id', [$first->id, $second->id])->delete();
            User::whereIn('id', [$first->id, $second->id])->delete();
            DB::table('rol_permiso')->where('rol_id', $roleId)->delete();
            DB::table('roles')->where('id', $roleId)->delete();
            if (! $existingPermission) {
                DB::table('permisos')->where('id', $permissionId)->delete();
            }
        }
    }
}
