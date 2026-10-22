<?php

use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UserController;
use App\Http\Middleware\ProtectSession;
use Illuminate\Support\Facades\Route;

Route::middleware([ProtectSession::class, 'auth', 'can:usuarios.gestionar'])->group(function (): void {
    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/crear', [UserController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::get('/usuarios/{usuario}/editar', [UserController::class, 'edit'])->whereNumber('usuario')->name('usuarios.edit');
    Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->whereNumber('usuario')->name('usuarios.update');

    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/crear', [RoleController::class, 'create'])->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('/roles/{rol}/editar', [RoleController::class, 'edit'])->whereNumber('rol')->name('roles.edit');
    Route::put('/roles/{rol}', [RoleController::class, 'update'])->whereNumber('rol')->name('roles.update');

    Route::get('/permisos', [PermissionController::class, 'index'])->name('permisos.index');
    Route::patch('/permisos/{permiso}', [PermissionController::class, 'update'])->whereNumber('permiso')->name('permisos.update');
});
