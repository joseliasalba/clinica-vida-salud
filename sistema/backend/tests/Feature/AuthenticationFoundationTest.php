<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationFoundationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_authentication_uses_the_clinical_user_and_hashed_password(): void
    {
        $user = User::factory()->create(['contrasena_hash' => 'clave-segura']);

        $this->assertTrue(Hash::check('clave-segura', $user->contrasena_hash));
        $this->assertArrayNotHasKey('contrasena_hash', $user->toArray());
        $this->assertTrue(Auth::attempt(['correo' => $user->correo, 'password' => 'clave-segura']));
        $this->assertAuthenticatedAs($user);
        Auth::logout();
        $this->assertGuest();
    }

    public function test_wrong_password_and_inactive_users_are_rejected(): void
    {
        $user = User::factory()->create();
        $this->assertFalse(Auth::attempt(['correo' => $user->correo, 'password' => 'incorrecta']));
        $user->forceFill(['activo' => false])->save();
        $this->assertFalse(Auth::attempt(['correo' => $user->correo, 'password' => 'password']));
        $this->assertNull(Auth::getProvider()->retrieveById($user->id));
    }

    public function test_remember_token_and_mail_address_use_the_clinical_user(): void
    {
        $user = User::factory()->create();
        $this->assertTrue(Auth::attempt(['correo' => $user->correo, 'password' => 'password'], true));
        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->assertSame($user->correo, $user->getEmailForPasswordReset());
        $this->assertSame($user->correo, $user->routeNotificationForMail());
    }

    public function test_postgresql_and_application_use_bolivian_time(): void
    {
        $this->assertSame('America/La_Paz', config('app.timezone'));
        $this->assertSame('America/La_Paz', DB::scalar('SHOW TIME ZONE'));
        $this->assertSame('clinica_testing', DB::scalar('SELECT current_schema()'));
    }
}
