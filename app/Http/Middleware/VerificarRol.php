<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: rol
 * RN-05: solo deja pasar a los usuarios con el rol indicado.
 * Uso en las rutas: ->middleware('rol:administrador')
 */
class VerificarRol
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  string  $rol  Rol exigido, recibido después de los dos puntos en la ruta.
     */
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        // El rol se lee de la base en cada petición, así un cambio de rol (CU05, paso 6)
        // se aplica desde la siguiente solicitud.
        if (! $request->user() || $request->user()->rol !== $rol) {
            abort(403, 'No tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
