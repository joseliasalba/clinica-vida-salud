<?php

namespace Tests\Support;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Support\Facades\DB;

trait CreatesAdministrativeUsers
{
    protected function accountWithRole(string $name = 'administrador'): User
    {
        $this->seed(RolesPermisosSeeder::class);
        $user = User::factory()->create();
        DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => $this->roleId($name), 'fecha_asignacion' => now()->subMinute(), 'activo' => true]);

        return $user;
    }

    protected function loginManager(): User
    {
        $admin = $this->accountWithRole();
        $this->post('/login', ['identificador' => $admin->correo, 'password' => 'password'])->assertRedirect(route('inicio'));

        return $admin;
    }

    protected function roleId(string $name): int
    {
        return (int) DB::table('roles')->where('nombre', $name)->value('id');
    }

    protected function permissionId(string $code): int
    {
        return (int) DB::table('permisos')->where('codigo', $code)->value('id');
    }

    protected function userData(array $changes = []): array
    {
        return array_replace([
            'nombre_usuario' => 'nuevo_usuario', 'correo' => 'nuevo@example.test',
            'password' => 'Clave#28', 'password_confirmation' => 'Clave#28',
            'activo' => '1', 'roles' => [$this->roleId('recepcion')],
        ], $changes);
    }

    protected function roleData(array $changes = []): array
    {
        return array_replace([
            'nombre' => 'supervisor', 'descripcion' => 'Supervisión de recepción',
            'activo' => '1', 'permisos' => [$this->permissionId('pacientes.gestionar')],
        ], $changes);
    }
}
