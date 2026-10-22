<?php

namespace Tests\Feature\Pacientes;

use App\Models\Paciente;
use App\Models\User;
use App\Services\Auth\UserAdministration;
use App\Services\Pacientes\PatientSelfRegistration;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\CreatesAdministrativeUsers;
use Tests\TestCase;

class PatientSelfRegistrationTest extends TestCase
{
    use CreatesAdministrativeUsers, DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermisosSeeder::class);
        Notification::fake();
    }

    public function test_registration_creates_only_a_patient_account_and_linked_administrative_record(): void
    {
        $this->post('/registro', $this->data(['nombre_usuario' => ' PACIENTE.UNO ', 'correo' => ' NUEVO@EXAMPLE.TEST ', 'ci' => ' 001234-ab ', 'roles' => [$this->roleId('administrador')], 'activo' => false, 'usuario_id' => 999, 'codigo' => 'INJECTED', 'email_verified_at' => now()]))
            ->assertRedirect(route('login'))->assertSessionHasNoErrors();
        $user = User::query()->sole();
        $patient = Paciente::query()->sole();
        $this->assertSame('paciente.uno', $user->nombre_usuario);
        $this->assertSame('nuevo@example.test', $user->correo);
        $this->assertTrue(Hash::check('Abcd#123', $user->contrasena_hash));
        $this->assertTrue($user->activo);
        $this->assertNull($user->email_verified_at);
        $this->assertSame($user->id, $patient->usuario_id);
        $this->assertSame('001234-AB', $patient->ci);
        $this->assertSame('PAC-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT), $patient->codigo);
        $this->assertTrue($patient->activo);
        $this->assertDatabaseCount('usuario_rol', 1);
        $this->assertDatabaseHas('usuario_rol', ['usuario_id' => $user->id, 'rol_id' => $this->roleId('paciente'), 'activo' => true]);
        $this->assertFalse($user->tienePermiso('usuarios.gestionar'));
        $this->assertDatabaseCount('historias_clinicas', 0);
        $this->assertDatabaseCount('citas_medicas', 0);
        $this->assertGuest();
        Notification::assertSentTo($user, VerifyEmail::class, function (VerifyEmail $notification, array $channels) use ($user): bool {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/verificar-correo/'.$user->id.'/'.sha1($user->correo)) && $channels === ['mail'];
        });
        $this->loginPatient($user);
        $this->get('/inicio')->assertOk()->assertSee(route('verification.notice'));
        $this->get('/pacientes')->assertForbidden();
        $this->get('/usuarios')->assertForbidden();
    }

    #[DataProvider('invalidInput')]
    public function test_invalid_input_does_not_create_partial_accounts(array $changes, string $field): void
    {
        $this->from('/registro')->post('/registro', $this->data($changes))->assertRedirect('/registro')->assertSessionHasErrors($field);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('pacientes', 0);
        $this->assertDatabaseCount('usuario_rol', 0);
        Notification::assertNothingSent();
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertFalse(session()->hasOldInput('password_confirmation'));
    }

    public static function invalidInput(): array
    {
        return [
            'clave corta' => [['password' => 'Clave#1', 'password_confirmation' => 'Clave#1'], 'password'],
            'clave común' => [['password' => 'password', 'password_confirmation' => 'password'], 'password'],
            'confirmación' => [['password_confirmation' => 'Distinta123'], 'password'],
            'correo inválido' => [['correo' => 'incorrecto'], 'correo'],
            'usuario inválido' => [['nombre_usuario' => 'usuario@correo'], 'nombre_usuario'],
            'fecha futura' => [['fecha_nacimiento' => '2999-01-01'], 'fecha_nacimiento'],
            'fecha imposible' => [['fecha_nacimiento' => '2000-02-30'], 'fecha_nacimiento'],
            'teléfono inválido' => [['telefono' => 'texto'], 'telefono'],
            'nombre como array' => [['nombres' => ['inyectado']], 'nombres'],
            'CI como array' => [['ci' => ['123']], 'ci'],
        ];
    }

    public function test_existing_ci_is_never_automatically_linked_even_if_the_contact_email_matches(): void
    {
        $existing = Paciente::factory()->create(['ci' => '001234-AB', 'correo' => 'nuevo@example.test', 'activo' => false]);
        $this->post('/registro', $this->data(['ci' => ' 001234-ab ']))->assertSessionHasErrors('ci');
        $this->assertNull($existing->fresh()->usuario_id);
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('pacientes', 1);
        Notification::assertNothingSent();
    }

    public function test_existing_account_identities_are_rejected_case_insensitively(): void
    {
        User::factory()->create(['nombre_usuario' => 'Paciente.Uno', 'correo' => 'NUEVO@example.test']);
        $this->post('/registro', $this->data())->assertSessionHasErrors(['nombre_usuario', 'correo']);
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_optional_ci_is_saved_as_null(): void
    {
        $this->post('/registro', $this->data(['ci' => '  ']))->assertSessionHasNoErrors();
        $this->assertNull(Paciente::query()->sole()->ci);
    }

    public function test_unavailable_patient_role_prevents_registration(): void
    {
        DB::table('roles')->where('nombre', 'paciente')->update(['activo' => false]);
        $this->post('/registro', $this->data())->assertSessionHasErrors('registro');
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('pacientes', 0);
    }

    public function test_database_collision_rolls_back_user_role_and_patient_together(): void
    {
        Paciente::factory()->create(['ci' => '001234-AB']);
        try {
            app(PatientSelfRegistration::class)->register($this->data());
            $this->fail('El índice único debe rechazar el CI incluso al omitir el Form Request.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('registro', $exception->errors());
        }
        $this->assertDatabaseCount('usuarios', 0);
        $this->assertDatabaseCount('usuario_rol', 0);
        $this->assertDatabaseCount('pacientes', 1);
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/registro', [])->assertSessionHasErrors();
        }
        $this->post('/registro', $this->data())->assertStatus(429);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_missing_csrf_token_prevents_registration(): void
    {
        $this->app->instance(PreventRequestForgery::class, new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected function runningUnitTests(): bool
            {
                return false;
            }
        });
        $this->post('/registro', $this->data())->assertStatus(419);
        $this->assertDatabaseCount('usuarios', 0);
    }

    public function test_verification_checks_signature_expiry_account_and_email_and_can_only_verify_its_owner(): void
    {
        $this->post('/registro', $this->data())->assertSessionHasNoErrors();
        $user = User::query()->sole();
        $valid = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->correo)]);
        $this->get($valid)->assertRedirect(route('login'));
        $this->loginPatient($user);
        $this->get('/verificar-correo')->assertOk();
        $this->get($valid.'tampered')->assertForbidden();
        foreach ([
            [now()->subMinute(), $user->id, sha1($user->correo)],
            [now()->addHour(), $user->id + 1, sha1($user->correo)],
            [now()->addHour(), $user->id, sha1('otro@example.test')],
        ] as [$expiry, $id, $hash]) {
            $this->get(URL::temporarySignedRoute('verification.verify', $expiry, compact('id', 'hash')))->assertForbidden();
        }
        $this->assertNull($user->fresh()->email_verified_at);
        $this->get($valid)->assertRedirect(route('inicio'))->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get('/verificar-correo')->assertRedirect(route('inicio'));
        $this->get($valid)->assertRedirect(route('inicio'));
    }

    public function test_resend_is_authenticated_and_rate_limited(): void
    {
        $this->post('/verificar-correo')->assertRedirect(route('login'));
        $this->post('/registro', $this->data())->assertSessionHasNoErrors();
        $user = User::query()->sole();
        $this->loginPatient($user);
        $this->from('/verificar-correo')->post('/verificar-correo')->assertRedirect('/verificar-correo')->assertSessionHasNoErrors();
        Notification::assertSentToTimes($user, VerifyEmail::class, 2);
        $this->post('/verificar-correo')->assertStatus(429);
    }

    public function test_email_delivery_failure_keeps_the_created_account_and_explains_how_to_retry(): void
    {
        Notification::swap($dispatcher = \Mockery::mock(Dispatcher::class));
        $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('Simulated delivery failure'));
        $this->post('/registro', $this->data())->assertRedirect(route('login'))->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'Tu cuenta fue creada, pero'));
        $this->assertDatabaseCount('usuarios', 1);
        $this->assertDatabaseCount('pacientes', 1);
    }

    public function test_changing_an_account_email_requires_new_verification(): void
    {
        $actor = $this->loginManager();
        $patientUser = User::factory()->create();
        $patientUser->forceFill(['email_verified_at' => now()])->save();
        app(UserAdministration::class)->saveUser($actor, [
            'nombre_usuario' => $patientUser->nombre_usuario, 'correo' => 'cambiado@example.test',
            'activo' => true, 'roles' => [$this->roleId('paciente')],
        ], $patientUser->id);
        $this->assertNull($patientUser->fresh()->email_verified_at);
    }

    private function loginPatient(User $user): void
    {
        $this->post('/login', ['identificador' => $user->correo, 'password' => 'Abcd#123'])->assertRedirect(route('inicio'));
    }

    private function data(array $changes = []): array
    {
        return array_replace([
            'nombre_usuario' => 'paciente.uno', 'correo' => 'nuevo@example.test',
            'password' => 'Abcd#123', 'password_confirmation' => 'Abcd#123',
            'ci' => '001234-AB', 'nombres' => 'María', 'apellidos' => 'Prueba',
            'fecha_nacimiento' => '2000-04-03', 'telefono' => '+591 70000000',
        ], $changes);

    }
}
