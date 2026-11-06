<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('clinica:enviar-recordatorios')->everyMinute()->withoutOverlapping(10);
Schedule::command('clinica:conciliar-pagos --limit=20')->everyMinute()->withoutOverlapping(10);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
