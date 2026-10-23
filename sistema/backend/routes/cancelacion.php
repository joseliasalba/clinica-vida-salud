<?php

use App\Http\Controllers\Citas\CancelacionController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:citas.gestionar'])->group(function (): void {
    Route::get('/citas/{cita}/cancelar', [CancelacionController::class, 'edit'])->whereNumber('cita')->name('citas.cancelar.edit');
    Route::patch('/citas/{cita}/cancelar', [CancelacionController::class, 'update'])->whereNumber('cita')->name('citas.cancelar.update');
});
