<?php

use App\Http\Controllers\Pacientes\PacienteController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:pacientes.gestionar'])->group(function (): void {
    Route::get('/pacientes', [PacienteController::class, 'index'])->name('pacientes.index');
    Route::get('/pacientes/crear', [PacienteController::class, 'create'])->name('pacientes.create');
    Route::post('/pacientes', [PacienteController::class, 'store'])->name('pacientes.store');
    Route::get('/pacientes/{paciente}', [PacienteController::class, 'show'])->whereNumber('paciente')->name('pacientes.show');
    Route::get('/pacientes/{paciente}/editar', [PacienteController::class, 'edit'])->whereNumber('paciente')->name('pacientes.edit');
    Route::put('/pacientes/{paciente}', [PacienteController::class, 'update'])->whereNumber('paciente')->name('pacientes.update');
});
