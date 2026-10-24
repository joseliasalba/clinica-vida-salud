<?php

use App\Http\Controllers\Medicos\MedicoController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:medicos.gestionar'])->group(function (): void {
    Route::get('/medicos', [MedicoController::class, 'index'])->name('medicos.index');
    Route::get('/medicos/crear', [MedicoController::class, 'create'])->name('medicos.create');
    Route::post('/medicos', [MedicoController::class, 'store'])->name('medicos.store');
    Route::get('/medicos/{medico}/editar', [MedicoController::class, 'edit'])->whereNumber('medico')->name('medicos.edit');
    Route::put('/medicos/{medico}', [MedicoController::class, 'update'])->whereNumber('medico')->name('medicos.update');
    Route::get('/medicos/{medico}/estado', [MedicoController::class, 'editState'])->whereNumber('medico')->name('medicos.estado.edit');
    Route::patch('/medicos/{medico}/estado', [MedicoController::class, 'updateState'])->whereNumber('medico')->name('medicos.estado.update');
});
