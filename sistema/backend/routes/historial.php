<?php

use App\Http\Controllers\Citas\HistorialController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/historial', [HistorialController::class, 'index'])->name('historial.index');
    Route::get('/pacientes/{paciente}/historial', [HistorialController::class, 'index'])
        ->whereNumber('paciente')->name('pacientes.historial');
});
