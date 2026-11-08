<?php

use App\Http\Controllers\AtencionClinica\ConsultaController;
use App\Http\Controllers\Pacientes\PerfilController;
use App\Http\Controllers\Reportes\ReporteController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/reportes', [ReporteController::class, 'index'])
        ->middleware('can:reportes.consultar')->name('reportes.index');

    Route::middleware('can:perfil.propio')->group(function (): void {
        Route::get('/mi-perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
        Route::patch('/mi-perfil', [PerfilController::class, 'update'])->name('perfil.update');
    });

    Route::middleware('can:consultas.registrar')->prefix('consultas')->name('consultas.')->group(function (): void {
        Route::get('/', [ConsultaController::class, 'index'])->name('index');
        Route::get('/{cita}', [ConsultaController::class, 'show'])->whereNumber('cita')->name('show');
        Route::post('/{cita}', [ConsultaController::class, 'store'])->whereNumber('cita')->name('store');
    });
});
