<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing')) {
    throw new RuntimeException('Worker exclusivo de pruebas.');
}
config(['database.default' => 'pgsql_testing', 'session.driver' => 'array', 'cache.default' => 'array']);
Notification::fake();
$data = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle(
    Request::create('http://localhost/restablecer-contrasena', 'POST', $data)
);
echo json_encode(['success' => ! session('errors') && session('status') !== null, 'status' => $response->getStatusCode()]);
