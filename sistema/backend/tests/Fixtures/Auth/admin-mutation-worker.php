<?php

use App\Models\User;
use App\Services\Auth\UserAdministration;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing')) {
    throw new RuntimeException('Worker exclusivo de pruebas.');
}
config(['database.default' => 'pgsql_testing']);
if (config('database.connections.pgsql_testing.search_path') !== 'clinica_testing') {
    throw new RuntimeException('El esquema de pruebas no está aislado.');
}
$data = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
try {
    $actor = User::findOrFail($data['actor_id']);
    $target = User::findOrFail($data['target_id']);
    app(UserAdministration::class)->saveUser($actor, [
        'nombre_usuario' => $target->nombre_usuario, 'correo' => $target->correo,
        'activo' => false, 'roles' => [$data['role_id']],
    ], $target->id);
    echo json_encode(['success' => true]);
} catch (ValidationException|HttpException $exception) {
    echo json_encode(['success' => false]);
}
