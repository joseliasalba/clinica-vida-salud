<?php

use App\Http\Controllers\Citas\AsistenciaController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/citas/{cita}/asistencia', [AsistenciaController::class, 'edit'])
        ->whereNumber('cita')->name('citas.asistencia.edit');
    Route::patch('/citas/{cita}/asistencia', [AsistenciaController::class, 'update'])
        ->whereNumber('cita')->name('citas.asistencia.update');
});
