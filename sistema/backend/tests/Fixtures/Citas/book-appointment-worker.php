<?php

use App\Models\User;
use App\Services\Citas\AppointmentBooking;
use App\Services\Horarios\ScheduleManagement;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
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
CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-02 08:00:00', config('app.timezone')));
Carbon::setTestNow(CarbonImmutable::now());
$data = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
echo "READY\n";
fflush(STDOUT);
if (trim(fgets(STDIN)) !== 'GO') {
    throw new RuntimeException('Falta la señal de inicio.');
}
try {
    $actor = User::query()->findOrFail($data['actor_id']);
    $result = match ($data['action']) {
        'block' => app(ScheduleManagement::class)->saveBlock($actor, $data['data']),
        'schedule' => app(ScheduleManagement::class)->changeScheduleState($actor, $data['schedule_id'], $data['data']),
        'sql' => DB::transaction(fn () => DB::table('citas_medicas')->insertGetId($data['data']), 3),
        default => app(AppointmentBooking::class)->book($actor, $data['token']),
    };
    echo json_encode(['success' => true, 'id' => is_int($result) ? $result : $result->id]);
} catch (ValidationException $exception) {
    echo json_encode(['success' => false, 'conflict' => isset($exception->errors()['intervalo']) || isset($exception->errors()['reserva'])]);
} catch (QueryException $exception) {
    if ($exception->getCode() !== '23P01') {
        throw $exception;
    }
    echo json_encode(['success' => false, 'conflict' => true]);
}
