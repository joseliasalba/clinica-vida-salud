<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RolesPermisosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seed_is_repeatable_and_does_not_reactivate_disabled_permissions(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        DB::table('permisos')->where('codigo', 'usuarios.gestionar')->update(['activo' => false]);
        $this->seed(RolesPermisosSeeder::class);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permisos', 8);
        $this->assertDatabaseCount('rol_permiso', 10);
        $this->assertDatabaseHas('permisos', ['codigo' => 'usuarios.gestionar', 'activo' => false]);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_role_permissions_are_enforced_by_gates(): void
    {
        $user = $this->usuarioConRol('recepcion');
        $this->assertTrue(Gate::forUser($user)->allows('pacientes.gestionar'));
        $this->assertFalse(Gate::forUser($user)->allows('usuarios.gestionar'));
        $this->assertFalse(Gate::forUser($user)->allows('agenda.propia'));
        $this->assertFalse(Gate::forUser($user)->allows('permiso.inexistente'));
    }

    public function test_expired_future_or_disabled_role_assignments_deny_access(): void
    {
        $user = $this->usuarioConRol('recepcion');
        foreach ([
            ['activo' => false],
            ['activo' => true, 'fecha_expiracion' => now()->subMinute()],
            ['fecha_expiracion' => null, 'fecha_asignacion' => now()->addDay()],
        ] as $changes) {
            DB::table('usuario_rol')->where('usuario_id', $user->id)->update($changes);
            $this->assertFalse(Gate::forUser($user)->allows('pacientes.gestionar'));
        }
    }

    public function test_disabling_any_permission_link_denies_access(): void
    {
        $user = $this->usuarioConRol('recepcion');
        foreach (['roles', 'rol_permiso', 'permisos'] as $table) {
            DB::table($table)->update(['activo' => false]);
            $this->assertFalse(Gate::forUser($user)->allows('pacientes.gestionar'));
            DB::table($table)->update(['activo' => true]);
        }
        $user->forceFill(['activo' => false])->save();
        $this->assertFalse(Gate::forUser($user)->allows('pacientes.gestionar'));
    }

    private function usuarioConRol(string $nombre): User
    {
        $this->seed(RolesPermisosSeeder::class);
        $user = User::factory()->create();
        DB::table('usuario_rol')->insert([
            'usuario_id' => $user->id,
            'rol_id' => DB::table('roles')->where('nombre', $nombre)->value('id'),
            'fecha_asignacion' => now()->subMinute(),
            'activo' => true,
        ]);

        return $user;
    }
}
