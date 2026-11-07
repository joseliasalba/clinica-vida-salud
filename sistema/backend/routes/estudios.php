<?php

use App\Http\Controllers\Estudios\EstudioController;
use App\Http\Controllers\Estudios\ProcesarAvisosController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::post('/interno/avisos-resultados', ProcesarAvisosController::class)->name('estudios.avisos.procesar');

Route::middleware([ProtectSession::class, 'auth'])->prefix('estudios')->name('estudios.')->group(function (): void {
    Route::get('/', [EstudioController::class, 'index'])->name('index');
    Route::get('/solicitar', [EstudioController::class, 'create'])->middleware('can:estudios.solicitar')->name('create');
    Route::post('/', [EstudioController::class, 'store'])->middleware('can:estudios.solicitar')->name('store');
    Route::get('/{estudio}', [EstudioController::class, 'show'])->whereNumber('estudio')->name('show');
    Route::put('/{estudio}/resultado', [EstudioController::class, 'saveResult'])->whereNumber('estudio')->middleware('can:estudios.publicar')->name('resultado');
    Route::patch('/{estudio}/estado', [EstudioController::class, 'changeState'])->whereNumber('estudio')->middleware('can:estudios.solicitar')->name('estado');
    Route::get('/{estudio}/archivo', [EstudioController::class, 'download'])->whereNumber('estudio')->name('archivo');
});
