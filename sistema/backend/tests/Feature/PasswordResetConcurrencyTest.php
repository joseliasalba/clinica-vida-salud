<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetConcurrencyTest extends TestCase
{
    public function test_two_processes_cannot_consume_the_same_token_twice(): void
    {
        $user = User::factory()->create();
        $processes = [];

        try {
            $token = Password::createToken($user);
            foreach (['Primera frase privada de prueba', 'Segunda frase privada de prueba'] as $password) {
                $process = proc_open([PHP_BINARY, base_path('tests/Fixtures/reset-password-worker.php')], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                $processes[] = [$process, $pipes, $password];
            }
            foreach ($processes as [$process, $pipes, $password]) {
                fwrite($pipes[0], json_encode(['correo' => $user->correo, 'token' => $token, 'password' => $password, 'password_confirmation' => $password]));
                fclose($pipes[0]);
            }
            $successes = 0;
            $winningPassword = null;
            foreach ($processes as [$process, $pipes, $password]) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $errors.$output);
                $result = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
                if ($result['success']) {
                    $successes++;
                    $winningPassword = $password;
                }
            }
            $this->assertSame(1, $successes);
            $this->assertTrue(Hash::check($winningPassword, $user->fresh()->contrasena_hash));
            $this->assertFalse(Password::tokenExists($user, $token));
        } finally {
            foreach ($processes as [$process]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
            DB::table('password_reset_tokens')->where('email', $user->correo)->delete();
            $user->delete();
        }
    }
}
