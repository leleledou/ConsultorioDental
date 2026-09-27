<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias de nuestros middleware, para usarlos en routes/web.php por su nombre corto.
        $middleware->alias([
            'cuenta.activa' => \App\Http\Middleware\VerificarCuentaActiva::class,
            'inactividad' => \App\Http\Middleware\ControlarInactividad::class,
            'rol' => \App\Http\Middleware\VerificarRol::class,
            'cambio.contrasena' => \App\Http\Middleware\ExigirCambioContrasena::class,
        ]);

        // Visitante sin sesión que entra a una ruta protegida: va al login.
        $middleware->redirectGuestsTo(fn () => route('login'));

        // Usuario con sesión que entra al login: va a inicio (que lo envía según su rol).
        $middleware->redirectUsersTo(fn () => route('inicio'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Campos que nunca se devuelven al formulario cuando falla una validación.
        // Laravel ya excluye password y password_confirmation; se agrega la
        // contraseña actual de CU03 para que no quede guardada en la sesión.
        $exceptions->dontFlash([
            'contrasena_actual',
        ]);
    })->create();
