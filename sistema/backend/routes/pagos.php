<?php

use App\Http\Controllers\Pagos\PagoController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::post('/pagos/stripe/webhook', [PagoController::class, 'webhook'])->name('pagos.webhook');
Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/mis-pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('/mis-pagos/{pago}', [PagoController::class, 'show'])->whereUuid('pago')->name('pagos.show');
    Route::post('/mis-pagos/{pago}/iniciar', [PagoController::class, 'start'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.start');
    Route::post('/mis-pagos/{pago}/actualizar', [PagoController::class, 'refresh'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.refresh');
    Route::get('/mis-pagos/{pago}/comprobante', [PagoController::class, 'invoice'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.invoice');
});
