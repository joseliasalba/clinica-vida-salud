<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class RoleAdministrationTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_create_and_edit_a_role_updates_effective_permissions_without_deleting_links(): void
    {
        $this->loginManager();
        $this->post('/roles', $this->roleData())->assertRedirect(route('roles.index'))->assertSessionHasNoErrors();
        $id = $this->roleId('supervisor');
        $user = $this->accountWithRole('supervisor');
        $this->assertTrue($user->tienePermiso('pacientes.gestionar'));
        $this->put('/roles/'.$id, $this->roleData(['permisos' => [$this->permissionId('citas.gestionar')]]))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->tienePermiso('citas.gestionar'));
        $this->assertFalse($user->fresh()->tienePermiso('pacientes.gestionar'));
        $this->assertDatabaseHas('rol_permiso', ['rol_id' => $id, 'permiso_id' => $this->permissionId('pacientes.gestionar'), 'activo' => false]);
        $this->get('/roles/'.$id.'/editar')->assertOk()->assertSee('supervisor');
    }

    public function test_role_deactivation_and_reactivation_apply_to_existing_users(): void
    {
        $this->loginManager();
        $user = $this->accountWithRole('recepcion');
        $data = $this->roleData(['nombre' => 'recepcion', 'permisos' => [$this->permissionId('pacientes.gestionar')], 'activo' => 0]);
        $this->put('/roles/'.$this->roleId('recepcion'), $data)->assertSessionHasNoErrors();
        $this->assertFalse($user->tienePermiso('pacientes.gestionar'));
        $this->put('/roles/'.$this->roleId('recepcion'), array_replace($data, ['activo' => 1]))->assertSessionHasNoErrors();
        $this->assertTrue($user->tienePermiso('pacientes.gestionar'));
    }

    public function test_base_role_names_and_duplicate_names_are_protected(): void
    {
        $this->loginManager();
        $this->put('/roles/'.$this->roleId('recepcion'), $this->roleData())->assertSessionHasErrors('nombre');
        $this->post('/roles', $this->roleData(['nombre' => 'recepcion']))->assertSessionHasErrors('nombre');
        $this->assertDatabaseHas('roles', ['nombre' => 'recepcion']);
        $this->assertDatabaseMissing('roles', ['nombre' => 'supervisor']);
    }

    public function test_unknown_inactive_or_duplicate_permissions_and_empty_selections_are_rejected(): void
    {
        $this->loginManager();
        foreach ([[[], 'permisos'], [[99999999], 'permisos.0'], [[$this->permissionId('citas.gestionar'), $this->permissionId('citas.gestionar')], 'permisos.0']] as [$ids, $field]) {
            $this->post('/roles', $this->roleData(['permisos' => $ids]))->assertSessionHasErrors($field);
        }
        DB::table('permisos')->where('codigo', 'citas.gestionar')->update(['activo' => false]);
        $this->post('/roles', $this->roleData(['permisos' => [$this->permissionId('citas.gestionar')]]))->assertSessionHasErrors('permisos.0');
        $this->assertDatabaseMissing('roles', ['nombre' => 'supervisor']);
    }

    public function test_permission_deactivation_affects_all_roles_and_reactivation_restores_links(): void
    {
        $this->loginManager();
        $user = $this->accountWithRole('recepcion');
        $permission = $this->permissionId('pacientes.gestionar');
        $this->patch('/permisos/'.$permission, ['activo' => 0])->assertRedirect(route('permisos.index'));
        $this->assertFalse($user->tienePermiso('pacientes.gestionar'));
        $this->assertDatabaseHas('rol_permiso', ['rol_id' => $this->roleId('recepcion'), 'permiso_id' => $permission, 'activo' => true]);
        $this->patch('/permisos/'.$permission, ['activo' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($user->tienePermiso('pacientes.gestionar'));
        $this->patch('/permisos/99999999', ['activo' => 1])->assertNotFound();
        $this->patch('/permisos/'.$permission, ['activo' => 'incorrecto'])->assertSessionHasErrors('activo');
    }

    public function test_last_manager_is_protected_when_changing_roles_or_permissions(): void
    {
        $admin = $this->loginManager();
        $role = $this->roleId('administrador');
        $this->put('/roles/'.$role, $this->roleData(['nombre' => 'administrador', 'activo' => 0]))->assertSessionHasErrors('operacion');
        $this->assertDatabaseHas('roles', ['id' => $role, 'activo' => true]);
        $this->put('/roles/'.$role, $this->roleData(['nombre' => 'administrador']))->assertSessionHasErrors('operacion');
        $this->assertTrue($admin->fresh()->tienePermiso('usuarios.gestionar'));
        $this->patch('/permisos/'.$this->permissionId('usuarios.gestionar'), ['activo' => 0])->assertSessionHasErrors('operacion');
        $this->assertDatabaseHas('permisos', ['codigo' => 'usuarios.gestionar', 'activo' => true]);
    }

    public function test_a_manager_cannot_remove_own_permission_even_if_another_manager_exists(): void
    {
        $admin = $this->loginManager();
        $this->post('/roles', $this->roleData(['nombre' => 'gestor', 'permisos' => [$this->permissionId('usuarios.gestionar')]]));
        $other = $this->accountWithRole('gestor');
        $this->put('/roles/'.$this->roleId('administrador'), $this->roleData(['nombre' => 'administrador']))->assertSessionHasErrors('operacion');
        $this->assertTrue($admin->fresh()->tienePermiso('usuarios.gestionar'));
        $this->assertTrue($other->fresh()->tienePermiso('usuarios.gestionar'));
    }

    public function test_disabling_a_permission_then_editing_a_role_preserves_its_inactive_assignment(): void
    {
        $this->loginManager();
        $id = $this->permissionId('pacientes.gestionar');
        $this->patch('/permisos/'.$id, ['activo' => 0]);
        $this->put('/roles/'.$this->roleId('recepcion'), $this->roleData(['nombre' => 'recepcion', 'permisos' => [$this->permissionId('citas.gestionar')]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rol_permiso', ['rol_id' => $this->roleId('recepcion'), 'permiso_id' => $id, 'activo' => true]);
        $this->patch('/permisos/'.$id, ['activo' => 1]);
        $user = $this->accountWithRole('recepcion');
        $this->assertTrue($user->tienePermiso('pacientes.gestionar'));
    }
}
