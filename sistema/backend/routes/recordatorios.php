<?php

use App\Http\Controllers\Recordatorios\PreferenciasController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:recordatorios.propios'])->group(function (): void {
    Route::get('/mi-cuenta/recordatorios', [PreferenciasController::class, 'edit'])->name('recordatorios.edit');
    Route::put('/mi-cuenta/recordatorios', [PreferenciasController::class, 'update'])->name('recordatorios.update');
});
