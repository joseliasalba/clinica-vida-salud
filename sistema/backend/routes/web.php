<?php

use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.headers:no_store;private;max_age=0')->group(function (): void {
    require __DIR__.'/auth.php';
    require __DIR__.'/usuarios.php';
    require __DIR__.'/pacientes.php';
    require __DIR__.'/especialidades.php';
    require __DIR__.'/citas.php';
    require __DIR__.'/reprogramacion.php';
    require __DIR__.'/cancelacion.php';
    require __DIR__.'/asistencia.php';
    require __DIR__.'/historial.php';

    Route::view('/', 'public.home')->name('portada');
    Route::view('/inicio', 'inicio')->middleware([ProtectSession::class, 'auth'])->name('inicio');
});
