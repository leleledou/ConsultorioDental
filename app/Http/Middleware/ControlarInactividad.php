<?php

namespace App\Http\Middleware;

use App\Services\BitacoraService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: inactividad
 * CU02 Cerrar sesión, flujo alternativo: cierre automático por inactividad (RNF-03).
 */
class ControlarInactividad
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ahora = now()->timestamp;
        $limiteSegundos = (int) config('dentalrox.minutos_inactividad') * 60;

        // Marca de la última petición del usuario (segundos desde 1970).
        // Si no existe (sesión anterior a este middleware), se toma la hora actual.
        $ultimaActividad = (int) $request->session()->get('ultima_actividad', $ahora);

        if ($request->user() && ($ahora - $ultimaActividad) > $limiteSegundos) {
            // 1. Registrar en la bitácora ANTES de cerrar la sesión (aún se conoce el usuario).
            BitacoraService::registrar(BitacoraService::CIERRE_SESION_INACTIVIDAD, $request->user()->id);

            // 2. Cerrar la sesión: salir, invalidar la sesión y regenerar el token CSRF.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // 3. Volver al login con el aviso.
            return redirect()->route('login')->with('aviso', 'Su sesión expiró por inactividad.');
        }

        // Hubo actividad dentro del límite: se actualiza la marca y se deja pasar la petición.
        $request->session()->put('ultima_actividad', $ahora);

        return $next($request);
    }
}
