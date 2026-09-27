<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Alias: cuenta.activa
 * Expulsa al usuario si su cuenta fue inhabilitada mientras tenía la sesión abierta.
 */
class VerificarCuentaActiva
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se lee el usuario en cada petición, así un cambio de estado se aplica de inmediato.
        if ($request->user() && $request->user()->estado === 'inactivo') {
            // Cerrar la sesión: salir, invalidar la sesión y regenerar el token CSRF.
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('aviso', 'Su cuenta fue inhabilitada. Comuníquese con el administrador.');
        }

        return $next($request);
    }
}
