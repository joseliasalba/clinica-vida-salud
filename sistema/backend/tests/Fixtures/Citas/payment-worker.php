<?php

use App\Models\PagoConsulta;
use App\Services\Pagos\PaymentManagement;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 3).'/vendor/autoload.php';
$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('testing')) {
    throw new RuntimeException('Solo pruebas.');
}
config(['database.default' => 'pgsql_testing']);
if (config('database.connections.pgsql_testing.search_path') !== 'clinica_testing' || DB::scalar('SELECT current_schema()') !== 'clinica_testing') {
    throw new RuntimeException('Se requiere esquema aislado.');
}
$data = json_decode(fgets(STDIN), true, flags: JSON_THROW_ON_ERROR);
echo "READY\n";
fflush(STDOUT);
if (trim(fgets(STDIN)) !== 'GO') {
    throw new RuntimeException('Falta señal.');
}
$payment = PagoConsulta::findOrFail($data['payment_id']);
$result = app(PaymentManagement::class)->apply($payment, $data['session'], 'evt_concurrent_'.$payment->codigo);
echo json_encode(['estado' => $result->estado]);
