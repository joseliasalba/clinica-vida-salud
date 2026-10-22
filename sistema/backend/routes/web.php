<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.headers:no_store;private;max_age=0')->group(function (): void {
    Route::redirect('/', '/inicio');

    Route::middleware('guest')->group(function (): void {
        Route::view('/login', 'auth.login')->name('login');
        Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:login-ip');
        Route::view('/recuperar-contrasena', 'auth.forgot-password')->name('password.request');
        Route::post('/recuperar-contrasena', [PasswordController::class, 'send'])->middleware('throttle:recovery')->name('password.email');
        Route::get('/restablecer-contrasena/{token}', [PasswordController::class, 'edit'])->name('password.reset');
        Route::post('/restablecer-contrasena', [PasswordController::class, 'reset'])->middleware('throttle:reset')->name('password.update');
    });

    Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
        Route::view('/inicio', 'inicio')->name('inicio');
        Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    });
});
