<?php

use App\Http\Controllers\Citas\CitaController;
use App\Http\Controllers\Citas\GestionCitaController;
use App\Http\Controllers\Citas\ListaEsperaController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::get('/reservar', [CitaController::class, 'entry'])->name('citas.entry');

Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/lista-espera', [ListaEsperaController::class, 'index'])->name('lista-espera.index');
    Route::post('/lista-espera', [ListaEsperaController::class, 'store'])->middleware('throttle:20,1')->name('lista-espera.store');
    Route::post('/lista-espera/{solicitud}', [ListaEsperaController::class, 'update'])->whereNumber('solicitud')->name('lista-espera.update');
    Route::get('/citas/gestion', [GestionCitaController::class, 'index'])->name('citas.gestion');
    Route::get('/citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('/citas/revisar', [CitaController::class, 'review'])->name('citas.review');
    Route::post('/citas', [CitaController::class, 'store'])->middleware('throttle:20,1')->name('citas.store');
    Route::get('/citas/{cita}/comprobante', [CitaController::class, 'receipt'])->whereNumber('cita')->name('citas.receipt');
});
