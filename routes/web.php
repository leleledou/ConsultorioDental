<?php

use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas de DentalRox - Ciclo 1: Paquete de Administración de Usuarios y Seguridad.
 * Solo se usan Route::get (mostrar) y Route::post (acciones).
 * En update, delete y las demás acciones, el id viaja oculto en el formulario.
 */

// Con sesión, cuenta activa, control de inactividad (RNF-03) y contraseña
// temporal ya reemplazada (CU06, post-condición).
Route::middleware(['auth', 'cuenta.activa', 'inactividad', 'cambio.contrasena'])->group(function () {

    // Entrada según el rol (RN-05).
    Route::controller(InicioController::class)->group(function () {
        Route::get('/', 'inicio')->name('inicio');
        // La agenda la ven los dos roles (el administrador también es odontólogo).
        Route::get('/agenda', 'agenda')->name('agenda');
        Route::get('/panel', 'panel')->middleware('rol:administrador')->name('panel');
    });

    // CU04 Gestionar usuarios, CU05 Asignar rol y CU06 Restablecer acceso.
    Route::prefix('usuarios')->middleware('rol:administrador')
        ->controller(UsuarioController::class)->group(function () {
            Route::get('/', 'index')->name('usuarios.listar');
            Route::post('/', 'store')->name('usuarios.guardar');
            Route::post('/update', 'update')->name('usuarios.modificar');
            Route::post('/delete', 'delete')->name('usuarios.eliminar'); // inhabilitar
            Route::post('/activar', 'activar')->name('usuarios.habilitar');
            Route::post('/rol', 'asignarRol')->name('usuarios.asignarRol');
            Route::post('/restablecer', 'restablecerAcceso')->name('usuarios.restablecer');
            // Va al final: solo acepta números, para no confundirse con las direcciones fijas.
            Route::get('/{id}', 'show')->whereNumber('id')->name('usuarios.mostrar');
        });

    // CU07 Consultar bitácora (solo lectura).
    Route::prefix('bitacora')->middleware('rol:administrador')
        ->controller(BitacoraController::class)->group(function () {
            Route::get('/', 'index')->name('bitacora.listar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('bitacora.mostrar');
        });

    // Catálogo de especialidades (RF-28).
    Route::prefix('especialidades')->middleware('rol:administrador')
        ->controller(EspecialidadController::class)->group(function () {
            Route::get('/', 'index')->name('especialidades.listar');
            Route::post('/', 'store')->name('especialidades.guardar');
            Route::post('/update', 'update')->name('especialidades.modificar');
            Route::post('/delete', 'delete')->name('especialidades.eliminar');
            Route::get('/{id}', 'show')->whereNumber('id')->name('especialidades.mostrar');
        });
});

// Rutas de autenticación (login, logout y cambio de contraseña).
require __DIR__.'/auth.php';
