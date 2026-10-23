<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Pacientes\RegistrationController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [RegistrationController::class, 'create'])->name('registro');
    Route::post('/registro', [RegistrationController::class, 'store'])->middleware('throttle:registration')->name('registro.store');
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login-ip');
    Route::view('/recuperar-contrasena', 'auth.forgot-password')->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordController::class, 'send'])->middleware('throttle:recovery')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordController::class, 'reset'])->middleware('throttle:reset')->name('password.update');
});

Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/verificar-correo', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verificar-correo/{id}/{hash}', [VerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/verificar-correo', [VerificationController::class, 'send'])->name('verification.send');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware([ProtectSession::class, 'auth'])->name('logout');
