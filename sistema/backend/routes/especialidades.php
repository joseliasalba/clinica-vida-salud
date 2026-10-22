<?php

use App\Http\Controllers\Especialidades\EspecialidadController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:medicos.gestionar'])->group(function (): void {
    Route::get('/especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
    Route::get('/especialidades/crear', [EspecialidadController::class, 'create'])->name('especialidades.create');
    Route::post('/especialidades', [EspecialidadController::class, 'store'])->name('especialidades.store');
    Route::get('/especialidades/{especialidad}/editar', [EspecialidadController::class, 'edit'])->whereNumber('especialidad')->name('especialidades.edit');
    Route::put('/especialidades/{especialidad}', [EspecialidadController::class, 'update'])->whereNumber('especialidad')->name('especialidades.update');
    Route::get('/especialidades/{especialidad}/estado', [EspecialidadController::class, 'editState'])->whereNumber('especialidad')->name('especialidades.estado.edit');
    Route::patch('/especialidades/{especialidad}/estado', [EspecialidadController::class, 'updateState'])->whereNumber('especialidad')->name('especialidades.estado.update');
});
