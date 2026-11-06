<?php

namespace App\Console\Commands;

use App\Services\Recordatorios\ReminderManagement;
use Illuminate\Console\Command;

class SendAppointmentReminders extends Command
{
    protected $signature = 'clinica:enviar-recordatorios {--simular : Mostrar cuántos recordatorios serían programados sin guardar ni enviar}';

    protected $description = 'Programa y envía recordatorios de citas según las preferencias vigentes';

    public function handle(ReminderManagement $reminders): int
    {
        $counts = $reminders->run((bool) $this->option('simular'));
        $this->info(($this->option('simular') ? 'Simulación. ' : '').'Programados: '.$counts['programados'].'. Procesados: '.$counts['procesados'].'. Sin confirmar: '.$counts['fallidos'].'.');

        return $counts['fallidos'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
