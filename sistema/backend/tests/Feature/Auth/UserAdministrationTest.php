<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\UserAdministration;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class UserAdministrationTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    public function test_guests_and_non_managers_cannot_access_any_administrative_action(): void
    {
        $this->get('/usuarios')->assertRedirect(route('login'));
        $this->post('/usuarios', [])->assertRedirect(route('login'));
        $user = $this->accountWithRole('paciente');
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
        foreach (['/usuarios', '/usuarios/crear', '/usuarios/'.$user->id.'/editar', '/roles', '/roles/crear', '/roles/1/editar', '/permisos'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/usuarios', [])->assertForbidden();
        $this->put('/usuarios/'.$user->id, [])->assertForbidden();
        $this->post('/roles', [])->assertForbidden();
        $this->put('/roles/1', [])->assertForbidden();
        $this->patch('/permisos/1', ['activo' => 0])->assertForbidden();
        $this->delete('/usuarios/'.$user->id)->assertStatus(405);
    }

    public function test_manager_can_open_all_screens_and_the_home_has_a_working_link(): void
    {
        $admin = $this->loginManager();
        $this->get('/inicio')->assertOk()->assertSee(route('usuarios.index'));
        foreach (['/usuarios', '/usuarios/crear', '/usuarios/'.$admin->id.'/editar', '/roles', '/roles/crear', '/roles/'.$this->roleId('administrador').'/editar', '/permisos'] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/usuarios/99999999/editar')->assertNotFound();
        $this->get('/roles/99999999/editar')->assertNotFound();
    }

    public function test_creation_normalizes_identity_hashes_password_and_assigns_multiple_roles(): void
    {
        $this->loginManager();
        $this->post('/usuarios', $this->userData(['nombre_usuario' => ' NUEVO_usuario ', 'correo' => ' NUEVO@EXAMPLE.TEST ', 'roles' => [$this->roleId('recepcion'), $this->roleId('medico')], 'id' => 9999, 'contrasena_hash' => 'sin-hash']))
            ->assertRedirect(route('usuarios.index'))->assertSessionHasNoErrors();
        $user = User::where('nombre_usuario', 'nuevo_usuario')->firstOrFail();
        $this->assertSame('nuevo@example.test', $user->correo);
        $this->assertNotSame(9999, $user->id);
        $this->assertTrue(Hash::check('Clave#28', $user->contrasena_hash));
        $this->assertTrue($user->tienePermiso('pacientes.gestionar'));
        $this->assertTrue($user->tienePermiso('agenda.propia'));
        $this->assertFalse($user->tienePermiso('usuarios.gestionar'));
        $this->get('/usuarios')->assertSee('nuevo_usuario')->assertDontSee($user->contrasena_hash, false);
    }

    public function test_duplicate_identity_is_rejected_case_insensitively_and_password_is_never_flashed(): void
    {
        $this->loginManager();
        User::factory()->create(['nombre_usuario' => 'Duplicado', 'correo' => 'Duplicado@example.test']);
        $this->post('/usuarios', $this->userData(['nombre_usuario' => 'duplicado', 'correo' => 'duplicado@example.test']))->assertSessionHasErrors(['nombre_usuario', 'correo']);
        $this->assertDatabaseCount('usuarios', 2);
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
    }

    public function test_invalid_roles_password_confirmation_identity_and_status_are_rejected(): void
    {
        $this->loginManager();
        $invalid = [
            [['roles' => []], 'roles'], [['roles' => [99999999]], 'roles.0'],
            [['roles' => [$this->roleId('recepcion'), $this->roleId('recepcion')]], 'roles.0'],
            [['password' => 'corta12', 'password_confirmation' => 'corta12'], 'password'],
            [['password_confirmation' => 'diferente'], 'password'],
            [['nombre_usuario' => 'nombre@correo'], 'nombre_usuario'], [['correo' => 'invalido'], 'correo'], [['activo' => 'otro'], 'activo'],
        ];
        foreach ($invalid as [$changes, $field]) {
            $this->post('/usuarios', $this->userData($changes))->assertSessionHasErrors($field);
        }
        DB::table('roles')->where('nombre', 'recepcion')->update(['activo' => false]);
        $this->post('/usuarios', $this->userData())->assertSessionHasErrors('roles.0');
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_malformed_text_fields_do_not_break_the_form_after_validation(): void
    {
        $this->loginManager();
        $this->from('/usuarios/crear')->post('/usuarios', $this->userData(['nombre_usuario' => ['invalido'], 'correo' => ['invalido']]))
            ->assertRedirect('/usuarios/crear')->assertSessionHasErrors(['nombre_usuario', 'correo']);
        $this->get('/usuarios/crear')->assertOk()->assertSee('Crear usuario');
    }

    public function test_update_preserves_password_and_assignment_history_and_reactivates_a_removed_role(): void
    {
        $this->loginManager();
        $user = $this->accountWithRole('recepcion');
        $oldHash = $user->contrasena_hash;
        $data = $this->userData(['nombre_usuario' => $user->nombre_usuario, 'correo' => $user->correo, 'password' => '', 'password_confirmation' => '', 'roles' => [$this->roleId('medico')]]);
        $this->put('/usuarios/'.$user->id, $data)->assertRedirect(route('usuarios.index'));
        $this->assertSame($oldHash, $user->fresh()->contrasena_hash);
        $this->assertDatabaseHas('usuario_rol', ['usuario_id' => $user->id, 'rol_id' => $this->roleId('recepcion'), 'activo' => false]);
        $this->assertTrue($user->fresh()->tienePermiso('agenda.propia'));
        $this->assertFalse($user->fresh()->tienePermiso('pacientes.gestionar'));
        $this->put('/usuarios/'.$user->id, array_replace($data, ['roles' => [$this->roleId('recepcion')]]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('usuario_rol', ['usuario_id' => $user->id, 'rol_id' => $this->roleId('recepcion'), 'activo' => true]);
    }

    public function test_password_or_email_change_revokes_reset_tokens_and_password_change_rotates_session_token(): void
    {
        $this->loginManager();
        $user = $this->accountWithRole('recepcion');
        Password::createToken($user);
        $oldToken = $user->remember_token;
        $this->put('/usuarios/'.$user->id, $this->userData(['nombre_usuario' => $user->nombre_usuario, 'correo' => 'actualizado@example.test']))->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Clave#28', $user->fresh()->contrasena_hash));
        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->correo]);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'actualizado@example.test']);
    }

    public function test_deactivation_and_reactivation_cannot_restore_an_old_session(): void
    {
        $admin = $this->loginManager();
        $user = $this->accountWithRole('recepcion');
        $oldHash = $user->contrasena_hash;
        $data = $this->userData(['nombre_usuario' => $user->nombre_usuario, 'correo' => $user->correo, 'password' => '', 'password_confirmation' => '', 'activo' => 0]);
        $this->put('/usuarios/'.$user->id, $data)->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->activo);
        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->put('/usuarios/'.$user->id, array_replace($data, ['activo' => 1]))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->activo);
        Auth::forgetGuards();
        $this->actingAs($user->fresh())->withSession(['auth_password_hash' => $oldHash, 'auth_session_token' => '', 'auth_last_activity' => now()->timestamp])
            ->get('/inicio')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertTrue($admin->fresh()->activo);
    }

    public function test_self_deactivation_or_removal_of_own_management_permission_is_rolled_back(): void
    {
        $admin = $this->loginManager();
        $data = $this->userData(['nombre_usuario' => $admin->nombre_usuario, 'correo' => $admin->correo, 'password' => '', 'password_confirmation' => '', 'roles' => [$this->roleId('administrador')]]);
        $this->put('/usuarios/'.$admin->id, array_replace($data, ['activo' => 0]))->assertSessionHasErrors('activo');
        $this->put('/usuarios/'.$admin->id, array_replace($data, ['roles' => [$this->roleId('paciente')]]))->assertSessionHasErrors('operacion');
        $this->assertTrue($admin->fresh()->activo);
        $this->assertTrue($admin->fresh()->tienePermiso('usuarios.gestionar'));
    }

    public function test_search_state_filter_pagination_and_output_escaping(): void
    {
        $this->loginManager();
        User::factory()->count(16)->create();
        $target = User::factory()->inactivo()->create(['nombre_usuario' => '<script>alert(1)</script>', 'correo' => 'unico@example.test']);
        $this->get('/usuarios?q=unico&estado=inactivo')->assertOk()->assertSee($target->correo)->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get('/usuarios?estado=activo')->assertOk()->assertDontSee($target->correo)->assertSee('page=2', false);
        $this->get('/usuarios?q=sin_coincidencia')->assertSee('No encontramos usuarios');
        $this->get('/usuarios?q=%25')->assertSee('No encontramos usuarios');
    }

    public function test_csrf_and_authorization_are_enforced_before_writes(): void
    {
        $admin = $this->loginManager();
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/usuarios', $this->userData())->assertStatus(419);
        DB::table('usuario_rol')->where('usuario_id', $admin->id)->update(['activo' => false]);
        $this->expectException(HttpException::class);
        app(UserAdministration::class)->saveUser($admin, $this->userData());
    }
}
