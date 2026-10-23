<?php

use App\Http\Controllers\Citas\CitaController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::get('/citas', [CitaController::class, 'index'])->middleware([ProtectSession::class, 'auth'])->name('citas.index');
