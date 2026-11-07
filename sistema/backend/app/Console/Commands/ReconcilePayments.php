<?php

namespace App\Console\Commands;

use App\Models\PagoConsulta;
use App\Services\Pagos\PaymentManagement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class ReconcilePayments extends Command
{
    protected $signature = 'clinica:conciliar-pagos {--limit=20 : Máximo de pagos por ejecución}';

    protected $description = 'Consulta pagos pendientes en Stripe sandbox y libera retenciones vencidas verificadas';

    public function handle(PaymentManagement $payments): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (! $limit || $limit < 1 || $limit > 100) {
            $this->error('El límite debe estar entre 1 y 100.');

            return self::FAILURE;
        }
        $processed = 0;
        $failed = 0;
        $rows = PagoConsulta::query()->whereIn('estado', ['nuevo', 'incierto', 'pendiente', 'procesando', 'qr_pendiente'])
            ->orderBy('updated_at')->limit($limit)->get();
        foreach ($rows as $payment) {
            try {
                $payments->refresh($payment);
                $payment->touch();
                $processed++;
            } catch (Throwable $error) {
                $payment->touch();
                Log::warning('pago.conciliacion_pendiente', ['pago_id' => $payment->id, 'tipo' => $error::class]);
                $failed++;
            }
        }
        $this->info("Consultados: {$processed}. Pendientes de reintento/revisión: {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
