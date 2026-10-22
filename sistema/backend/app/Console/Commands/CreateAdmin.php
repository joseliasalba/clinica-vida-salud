<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Rules\PasswordPolicy;
use Database\Seeders\RolesPermisosSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'clinica:crear-admin {--usuario=} {--correo=}';

    protected $description = 'Crear una cuenta administradora con contraseña oculta, sin modificar cuentas existentes';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Este comando requiere una terminal interactiva para introducir la contraseña.');

            return self::FAILURE;
        }

        $data = [
            'nombre_usuario' => $this->option('usuario') ?: $this->ask('Nombre de usuario'),
            'correo' => mb_strtolower($this->option('correo') ?: $this->ask('Correo')),
            'password' => $this->secret('Contraseña (15 a 128 caracteres)'),
            'password_confirmation' => $this->secret('Confirma la contraseña'),
        ];
        $validator = Validator::make($data, [
            'nombre_usuario' => ['required', 'string', 'max:255', 'not_regex:/@/', 'unique:usuarios,nombre_usuario'],
            'correo' => ['required', 'email', 'max:255', 'unique:usuarios,correo'],
            'password' => ['required', 'confirmed', new PasswordPolicy],
        ]);
        if ($validator->fails() || User::whereRaw('lower(correo) = ?', [$data['correo']])->exists()) {
            $this->error('Revisa los datos: usuario y correo deben ser únicos y la contraseña debe cumplir la política y coincidir.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($data): void {
            $this->callSilent('db:seed', ['--class' => RolesPermisosSeeder::class, '--force' => true]);
            $role = DB::table('roles')->where('nombre', 'administrador')->where('activo', true)->first();
            if (! $role) {
                throw new \RuntimeException('El rol administrador está desactivado. Revisa sus permisos antes de crear la cuenta.');
            }
            $user = User::create(['nombre_usuario' => $data['nombre_usuario'], 'correo' => $data['correo'], 'contrasena_hash' => $data['password']]);
            DB::table('usuario_rol')->insert(['usuario_id' => $user->id, 'rol_id' => $role->id, 'fecha_asignacion' => now(), 'activo' => true]);
        });
        $this->info('Cuenta administradora creada. Ya puedes iniciar sesión.');

        return self::SUCCESS;
    }
}
