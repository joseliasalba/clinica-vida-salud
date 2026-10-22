<?php

namespace Tests\Feature\Auth;

use App\Jobs\SendPasswordResetLink;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use DatabaseTransactions;

    private const NEW_PASSWORD = 'Clave#28';

    public function test_recovery_queues_the_same_job_for_existing_unknown_and_inactive_accounts(): void
    {
        Queue::fake();
        $active = User::factory()->create();
        $inactive = User::factory()->inactivo()->create();
        foreach ([$active->correo, $inactive->correo, 'missing@example.test'] as $email) {
            $this->post('/recuperar-contrasena', ['correo' => $email])->assertSessionHas('status', 'Si el correo corresponde a una cuenta activa, recibirás un enlace para restablecer tu contraseña.');
        }
        Queue::assertPushed(SendPasswordResetLink::class, 3);
    }

    public function test_recovery_worker_sends_only_to_active_account_and_throttles_repeated_requests(): void
    {
        Notification::fake();
        $active = User::factory()->create();
        $inactive = User::factory()->inactivo()->create();
        (new SendPasswordResetLink($active->correo))->handle();
        (new SendPasswordResetLink($active->correo))->handle();
        (new SendPasswordResetLink($inactive->correo))->handle();
        (new SendPasswordResetLink('missing@example.test'))->handle();
        Notification::assertSentToTimes($active, ResetPassword::class, 1);
        Notification::assertNotSentTo($inactive, ResetPassword::class);
        $stored = DB::table('password_reset_tokens')->where('email', $active->correo)->value('token');
        Notification::assertSentTo($active, ResetPassword::class, function (ResetPassword $notification) use ($stored, $active): bool {
            $this->assertTrue(Hash::check($notification->token, $stored));
            $mail = $notification->toMail($active);
            $this->assertStringContainsString('correo=', $mail->actionUrl);
            $this->assertStringContainsString('/restablecer-contrasena/', $mail->actionUrl);

            return true;
        });
    }

    public function test_reset_changes_password_consumes_token_and_does_not_authenticate(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->get('/restablecer-contrasena/'.$token.'?correo='.urlencode($user->correo))->assertOk();
        $this->assertTrue(Password::tokenExists($user, $token));
        $this->post('/restablecer-contrasena', $this->payload($user, $token))->assertRedirect(route('login'));
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->fresh()->contrasena_hash));
        $this->assertFalse(Hash::check('password', $user->fresh()->contrasena_hash));
        $this->assertNotEmpty($user->fresh()->remember_token);
        $this->assertFalse(Password::tokenExists($user, $token));
        $this->assertGuest();
        $this->post('/restablecer-contrasena', $this->payload($user, $token))->assertSessionHasErrors('correo');
    }

    public function test_expired_tampered_and_superseded_tokens_are_rejected(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $replacement = Password::createToken($user);
        $this->post('/restablecer-contrasena', $this->payload($user, $token))->assertSessionHasErrors('correo');
        $this->post('/restablecer-contrasena', $this->payload($user, 'alterado'))->assertSessionHasErrors('correo');
        $this->travel(16)->minutes();
        $this->get('/restablecer-contrasena/'.$replacement.'?correo='.urlencode($user->correo))->assertRedirect(route('password.request'));
        $this->post('/restablecer-contrasena', $this->payload($user, $replacement))->assertSessionHasErrors('correo');
        $this->assertTrue(Hash::check('password', $user->fresh()->contrasena_hash));
    }

    public function test_reset_cannot_reactivate_a_disabled_account(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $user->forceFill(['activo' => false])->save();
        $this->post('/restablecer-contrasena', $this->payload($user, $token))->assertSessionHasErrors('correo');
        $this->assertFalse($user->fresh()->activo);
        $this->assertTrue(Hash::check('password', $user->fresh()->contrasena_hash));
    }

    public function test_password_policy_and_confirmation_are_checked_by_the_server(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        foreach (['corta', str_repeat('a', 20), 'passwordpassword', str_repeat('larga', 30)] as $password) {
            $this->post('/restablecer-contrasena', array_replace($this->payload($user, $token), ['password' => $password, 'password_confirmation' => $password]))->assertSessionHasErrors('password');
        }
        $this->post('/restablecer-contrasena', array_replace($this->payload($user, $token), ['password_confirmation' => 'no coincide']))->assertSessionHasErrors('password');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertTrue(Password::tokenExists($user, $token));
    }

    public function test_recovery_and_reset_submission_limits_are_enforced(): void
    {
        Queue::fake();
        $this->post('/recuperar-contrasena', ['correo' => 'missing@example.test'])->assertRedirect();
        $this->post('/recuperar-contrasena', ['correo' => 'missing@example.test'])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->post('/recuperar-contrasena', ['correo' => 'missing@example.test'])->assertRedirect();
    }

    public function test_admin_command_creates_an_account_without_default_credentials(): void
    {
        $this->artisan('clinica:crear-admin', ['--usuario' => 'admin-prueba', '--correo' => 'admin@example.test'])
            ->expectsQuestion('Contraseña (8 a 128 caracteres)', self::NEW_PASSWORD)
            ->expectsQuestion('Confirma la contraseña', self::NEW_PASSWORD)
            ->assertSuccessful();
        $user = User::where('nombre_usuario', 'admin-prueba')->firstOrFail();
        $this->assertTrue(Hash::check(self::NEW_PASSWORD, $user->contrasena_hash));
        $this->assertTrue($user->tienePermiso('usuarios.gestionar'));
    }

    private function payload(User $user, string $token): array
    {
        return ['correo' => $user->correo, 'token' => $token, 'password' => self::NEW_PASSWORD, 'password_confirmation' => self::NEW_PASSWORD];
    }
}
