<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationHttpTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_by_username_and_email_regenerates_session_and_logout_invalidates_it(): void
    {
        $user = User::factory()->create();
        $this->get('/login')->assertOk();
        $previous = session()->getId();
        $this->post('/login', ['identificador' => $user->nombre_usuario, 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->assertNotSame($previous, session()->getId());
        $this->assertAuthenticatedAs($user);
        $this->get('/inicio')->assertOk()->assertSee($user->nombre_usuario)->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->get('/logout')->assertStatus(405);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->get('/inicio')->assertRedirect(route('login'));
        $this->post('/login', ['identificador' => strtoupper($user->correo), 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_unknown_and_inactive_accounts_have_the_same_error(): void
    {
        $user = User::factory()->create();
        $inactive = User::factory()->inactivo()->create();
        foreach ([$user->correo, $inactive->correo, 'missing@example.test'] as $email) {
            $this->post('/login', ['identificador' => $email, 'password' => 'incorrecta'])
                ->assertSessionHasErrors(['identificador' => 'Credenciales incorrectas.']);
            $this->assertGuest();
        }
    }

    public function test_five_failures_limit_the_sixth_attempt_and_expire(): void
    {
        $user = User::factory()->create();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'incorrecta'])->assertSessionHasErrors('identificador');
        }
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])
            ->assertSessionHasErrors(['identificador' => 'Demasiados intentos. Intenta nuevamente en unos minutos.']);
        $this->assertGuest();
        $this->travel(16)->minutes();
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password'])->assertRedirect(route('inicio'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_validates_without_javascript_and_never_flashes_passwords(): void
    {
        $this->post('/login', ['password' => 'secreto'])->assertSessionHasErrors('identificador');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->get('/login')->assertSee('Mostrar')->assertSee('autocomplete="current-password"', false);
    }

    public function test_foreign_intended_url_is_discarded(): void
    {
        $user = User::factory()->create();
        $this->withSession(['url.intended' => 'https://example.test/robar'])->post('/login', ['identificador' => $user->correo, 'password' => 'password'])
            ->assertRedirect(route('inicio'))->assertSessionMissing('url.intended');
    }

    public function test_inactivity_and_deactivation_invalidate_sessions(): void
    {
        $user = User::factory()->create();
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
        $this->travel(31)->minutes();
        $this->get('/inicio')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
        $user->forceFill(['activo' => false])->save();
        Auth::forgetGuards();
        $this->get('/inicio')->assertRedirect(route('login'));
        $this->assertGuest();
        $user->forceFill(['activo' => true])->save();
        Auth::forgetGuards();
        $this->get('/inicio')->assertRedirect(route('login'));
    }

    public function test_permissions_control_menu_and_protected_routes(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        $user = User::factory()->create();
        DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => DB::table('roles')->where('nombre', 'recepcion')->value('id'), 'activo' => true]);
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
        $this->get('/inicio')->assertOk()->assertSee('Gestionar pacientes desde recepción')->assertDontSee('Administrar usuarios, roles y permisos');
        Route::middleware(['web', 'auth', 'can:usuarios.gestionar'])->get('/prueba-permiso', fn () => 'permitido');
        $this->get('/prueba-permiso')->assertForbidden();
        $this->get('/login')->assertRedirect(route('inicio'));
    }

    public function test_csrf_is_enforced_when_test_bypass_is_disabled(): void
    {
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/login', ['identificador' => 'admin', 'password' => 'secreto'])->assertStatus(419);
    }

    public function test_all_four_roles_and_multiple_roles_show_only_effective_permissions(): void
    {
        $this->seed(RolesPermisosSeeder::class);
        foreach (config('permisos.roles') as $name => $permissions) {
            $user = User::factory()->create();
            DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => DB::table('roles')->where('nombre', $name)->value('id'), 'activo' => true]);
            $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
            $response = $this->get('/inicio')->assertOk();
            foreach (config('permisos.catalogo') as $code => $description) {
                if (in_array($code, $permissions, true)) {
                    $response->assertSee($description);
                } else {
                    $response->assertDontSee($description);
                }
            }
            $this->post('/logout');
        }
        DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => DB::table('roles')->where('nombre', 'medico')->value('id'), 'activo' => true]);
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'password']);
        $this->get('/inicio')->assertSee('Consultar la agenda del médico autenticado')->assertSee('Consultar y reservar citas propias')->assertDontSee('Administrar usuarios, roles y permisos');
    }

    public function test_identifier_ambiguity_is_rejected_and_output_is_escaped(): void
    {
        $first = User::factory()->create(['nombre_usuario' => 'otra@example.test']);
        User::factory()->create(['correo' => 'otra@example.test']);
        $this->post('/login', ['identificador' => $first->nombre_usuario, 'password' => 'password'])->assertSessionHasErrors('identificador');
        $this->assertGuest();
        $first->forceFill(['nombre_usuario' => '<script>alert(1)</script>'])->save();
        $this->post('/login', ['identificador' => $first->correo, 'password' => 'password']);
        $this->get('/inicio')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_changed_password_revokes_two_previously_authenticated_sessions(): void
    {
        $user = User::factory()->create();
        $oldHash = $user->contrasena_hash;
        $user->forceFill(['contrasena_hash' => 'una frase nueva y segura'])->save();
        for ($client = 0; $client < 2; $client++) {
            Auth::forgetGuards();
            $this->actingAs($user->fresh())->withSession(['auth_password_hash' => $oldHash, 'auth_last_activity' => now()->timestamp])
                ->get('/inicio')->assertRedirect(route('login'));
            $this->assertGuest();
        }
    }
}
