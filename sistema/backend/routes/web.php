<?php

use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware('cache.headers:no_store;private;max_age=0')->group(function (): void {
    require __DIR__.'/auth.php';
    require __DIR__.'/usuarios.php';

    Route::redirect('/', '/inicio');
    Route::view('/inicio', 'inicio')->middleware([ProtectSession::class, 'auth'])->name('inicio');
});
