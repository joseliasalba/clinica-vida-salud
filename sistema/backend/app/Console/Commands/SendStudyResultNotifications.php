<?php

namespace App\Console\Commands;

use App\Services\Estudios\ResultNotificationManagement;
use Illuminate\Console\Command;

class SendStudyResultNotifications extends Command
{
    protected $signature = 'clinica:enviar-avisos-resultados {--limit=1 : Máximo de avisos por ejecución}';

    protected $description = 'Procesa avisos de resultados publicados para pacientes que los activaron';

    public function handle(ResultNotificationManagement $notices): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT);
        if (! $limit || $limit < 1 || $limit > 10) {
            $this->error('El límite debe estar entre 1 y 10.');

            return self::FAILURE;
        }
        $counts = $notices->run($limit);
        $this->info("Procesados: {$counts['procesados']}. Sin confirmar: {$counts['fallidos']}.");

        return $counts['fallidos'] ? self::FAILURE : self::SUCCESS;
    }
}
