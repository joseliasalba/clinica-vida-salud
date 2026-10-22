<?php

use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.headers:no_store;private;max_age=0')->group(function (): void {
    require __DIR__.'/auth.php';
    require __DIR__.'/usuarios.php';
    require __DIR__.'/pacientes.php';
    require __DIR__.'/especialidades.php';

    Route::redirect('/', '/inicio');
    Route::view('/inicio', 'inicio')->middleware([ProtectSession::class, 'auth'])->name('inicio');
});
