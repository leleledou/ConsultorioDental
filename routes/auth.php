<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas de autenticación (archivos de Breeze adaptados a DentalRox).
 * Solo se usan Route::get (mostrar) y Route::post (acciones).
 */

// Solo visitantes (sin sesión). Con sesión, 'guest' redirige a inicio.
Route::middleware('guest')->controller(AuthenticatedSessionController::class)->group(function () {
    // CU01 Iniciar sesión. Laravel exige que la ruta del formulario se llame 'login'.
    Route::get('/login', 'create')->name('login');
    Route::post('/login', 'store')->name('login.ingresar');
});

// Con sesión: cuenta activa y control de inactividad (RNF-03).
// Estas rutas NO llevan 'cambio.contrasena': quien tiene una contraseña
// temporal debe poder cambiarla y también cerrar sesión.
Route::middleware(['auth', 'cuenta.activa', 'inactividad'])->group(function () {
    // CU02 Cerrar sesión (POST para que esté protegido con el token CSRF).
    Route::controller(AuthenticatedSessionController::class)->group(function () {
        Route::post('/logout', 'destroy')->name('logout');
    });

    // CU03 Cambiar contraseña.
    Route::controller(PasswordController::class)->group(function () {
        Route::get('/contrasena/cambiar', 'edit')->name('contrasena.mostrar');
        Route::post('/contrasena/cambiar', 'update')->name('contrasena.guardar');
    });
});
