<?php

use App\Http\Controllers\Horarios\HorarioController;
use App\Http\Controllers\Horarios\IndisponibilidadController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:horarios.gestionar'])->group(function (): void {
    Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
    Route::get('/horarios/crear', [HorarioController::class, 'create'])->name('horarios.create');
    Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
    Route::get('/horarios/{horario}/editar', [HorarioController::class, 'edit'])->whereNumber('horario')->name('horarios.edit');
    Route::put('/horarios/{horario}', [HorarioController::class, 'update'])->whereNumber('horario')->name('horarios.update');
    Route::get('/horarios/{horario}/estado', [HorarioController::class, 'editState'])->whereNumber('horario')->name('horarios.estado.edit');
    Route::patch('/horarios/{horario}/estado', [HorarioController::class, 'updateState'])->whereNumber('horario')->name('horarios.estado.update');
    Route::get('/horarios/bloqueos', [IndisponibilidadController::class, 'index'])->name('horarios.bloqueos.index');
    Route::get('/horarios/bloqueos/crear', [IndisponibilidadController::class, 'create'])->name('horarios.bloqueos.create');
    Route::post('/horarios/bloqueos', [IndisponibilidadController::class, 'store'])->name('horarios.bloqueos.store');
    Route::get('/horarios/bloqueos/{bloqueo}/editar', [IndisponibilidadController::class, 'edit'])->whereNumber('bloqueo')->name('horarios.bloqueos.edit');
    Route::put('/horarios/bloqueos/{bloqueo}', [IndisponibilidadController::class, 'update'])->whereNumber('bloqueo')->name('horarios.bloqueos.update');
    Route::get('/horarios/bloqueos/{bloqueo}/estado', [IndisponibilidadController::class, 'editState'])->whereNumber('bloqueo')->name('horarios.bloqueos.estado.edit');
    Route::patch('/horarios/bloqueos/{bloqueo}/estado', [IndisponibilidadController::class, 'updateState'])->whereNumber('bloqueo')->name('horarios.bloqueos.estado.update');
});
