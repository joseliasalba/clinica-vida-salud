<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_short_password_explains_the_policy_without_disclosing_it(): void
    {
        $password = 'corta12';
        $this->artisan('clinica:crear-admin', ['--usuario' => 'nuevo-admin', '--correo' => 'nuevo@example.test'])
            ->expectsQuestion('Contraseña (8 a 128 caracteres)', $password)
            ->expectsQuestion('Confirma la contraseña', $password)
            ->expectsOutput('Usa una frase de entre 8 y 128 caracteres. Evita claves comunes o un solo carácter repetido.')
            ->doesntExpectOutputToContain($password)
            ->assertFailed();
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_mismatched_confirmation_has_a_specific_error(): void
    {
        $password = 'Una frase privada para pruebas';
        $this->artisan('clinica:crear-admin', ['--usuario' => 'nuevo-admin', '--correo' => 'nuevo@example.test'])
            ->expectsQuestion('Contraseña (8 a 128 caracteres)', $password)
            ->expectsQuestion('Confirma la contraseña', 'Otra frase privada para pruebas')
            ->expectsOutput('La confirmación de la contraseña no coincide. Escríbela igual las dos veces.')
            ->doesntExpectOutputToContain($password)
            ->assertFailed();
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_duplicate_email_is_reported_without_overwriting_the_existing_account(): void
    {
        $user = User::factory()->create(['correo' => 'Admin@example.test']);
        $originalHash = $user->contrasena_hash;
        $password = 'Una frase privada para pruebas';
        $this->artisan('clinica:crear-admin', ['--usuario' => 'nuevo-admin', '--correo' => 'admin@example.test'])
            ->expectsQuestion('Contraseña (8 a 128 caracteres)', $password)
            ->expectsQuestion('Confirma la contraseña', $password)
            ->expectsOutput('Ese correo electrónico ya está registrado.')
            ->doesntExpectOutputToContain($password)
            ->assertFailed();
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertSame($originalHash, $user->fresh()->contrasena_hash);
    }
}
