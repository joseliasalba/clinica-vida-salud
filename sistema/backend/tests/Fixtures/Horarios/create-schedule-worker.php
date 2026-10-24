<?php

use App\Models\User;
use App\Services\Horarios\ScheduleManagement;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing')) {
    throw new RuntimeException('Worker exclusivo de pruebas.');
}
config(['database.default' => 'pgsql_testing']);
if (config('database.connections.pgsql_testing.search_path') !== 'clinica_testing' || DB::scalar('SELECT current_schema()') !== 'clinica_testing') {
    throw new RuntimeException('La conexión de pruebas no está aislada.');
}
$data = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
echo "READY\n";
fflush(STDOUT);
if (trim(fgets(STDIN)) !== 'GO') {
    throw new RuntimeException('Falta la señal de inicio.');
}
try {
    if ($data['mode'] === 'service') {
        app(ScheduleManagement::class)->saveSchedule(User::query()->findOrFail($data['actor_id']), $data['schedule']);
    } else {
        DB::transaction(fn () => DB::table('horarios_atencion')->insert($data['schedule']), 3);
    }
    echo json_encode(['success' => true]);
} catch (ValidationException $exception) {
    echo json_encode(['success' => false, 'conflict' => isset($exception->errors()['intervalo'])]);
} catch (QueryException $exception) {
    if ($exception->getCode() !== '23P01') {
        throw $exception;
    }
    echo json_encode(['success' => false, 'conflict' => true]);
}
