<?php

use App\Http\Controllers\Pagos\ConciliacionController;
use App\Http\Controllers\Pagos\PagoController;
use App\Http\Controllers\Pagos\QrPagoController;
use App\Http\Controllers\Pagos\ReembolsoController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::post('/pagos/stripe/webhook', [PagoController::class, 'webhook'])->name('pagos.webhook');
Route::post('/pagos/conciliar', ConciliacionController::class)->name('pagos.conciliar');
Route::middleware([ProtectSession::class, 'auth'])->group(function (): void {
    Route::get('/recepcion/reembolsos', [ReembolsoController::class, 'index'])->name('reembolsos.index');
    Route::post('/pagos/{pago}/solicitar-reembolso', [ReembolsoController::class, 'store'])->whereUuid('pago')->name('reembolsos.store');
    Route::post('/recepcion/reembolsos/{reembolso}/decision', [ReembolsoController::class, 'decide'])->whereUuid('reembolso')->name('reembolsos.decide');
    Route::post('/recepcion/reembolsos/{reembolso}/manual', [ReembolsoController::class, 'manual'])->whereUuid('reembolso')->name('reembolsos.manual');
    Route::post('/recepcion/reembolsos/{reembolso}/stripe', [ReembolsoController::class, 'stripe'])->whereUuid('reembolso')->name('reembolsos.stripe');
    Route::get('/recepcion/pagos-qr', [QrPagoController::class, 'index'])->name('pagos.qr.index');
    Route::post('/recepcion/pagos-qr/imagen', [QrPagoController::class, 'upload'])->name('pagos.qr.upload');
    Route::post('/recepcion/pagos-qr/{pago}/decision', [QrPagoController::class, 'decide'])->whereUuid('pago')->name('pagos.qr.decide');
    Route::post('/mis-pagos/{pago}/qr', [QrPagoController::class, 'select'])->whereUuid('pago')->name('pagos.qr.select');
    Route::get('/mis-pagos/{pago}/qr/imagen', [QrPagoController::class, 'image'])->whereUuid('pago')->name('pagos.qr.image');
    Route::post('/mis-pagos/{pago}/qr/reporte', [QrPagoController::class, 'report'])->whereUuid('pago')->name('pagos.qr.report');
    Route::get('/mis-pagos', [PagoController::class, 'index'])->name('pagos.index');
    Route::get('/mis-pagos/{pago}', [PagoController::class, 'show'])->whereUuid('pago')->name('pagos.show');
    Route::post('/mis-pagos/{pago}/iniciar', [PagoController::class, 'start'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.start');
    Route::post('/mis-pagos/{pago}/actualizar', [PagoController::class, 'refresh'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.refresh');
    Route::get('/mis-pagos/{pago}/comprobante', [PagoController::class, 'invoice'])->whereUuid('pago')->middleware('throttle:10,1')->name('pagos.invoice');
});
