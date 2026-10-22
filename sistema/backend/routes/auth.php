<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login-ip');
    Route::view('/recuperar-contrasena', 'auth.forgot-password')->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordController::class, 'send'])->middleware('throttle:recovery')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordController::class, 'reset'])->middleware('throttle:reset')->name('password.update');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware([ProtectSession::class, 'auth'])->name('logout');
