<?php

use App\Http\Controllers\Citas\ReprogramacionController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:citas.gestionar'])->group(function (): void {
    Route::get('/citas/{cita}/reprogramar/turnos', [ReprogramacionController::class, 'turnos'])->whereNumber('cita')->name('citas.reprogramar.turnos');
    Route::get('/citas/{cita}/reprogramar', [ReprogramacionController::class, 'edit'])->whereNumber('cita')->name('citas.reprogramar.edit');
    Route::put('/citas/{cita}/reprogramar', [ReprogramacionController::class, 'update'])->whereNumber('cita')->name('citas.reprogramar.update');
});
