<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\BitacoraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * CU01 Iniciar sesión y CU02 Cerrar sesión (controlador de Breeze adaptado).
 */
class AuthenticatedSessionController extends Controller
{
    // CU01 paso 1: MOSTRAR el formulario de inicio de sesión.
    public function create(): View
    {
        return view('auth.login');
    }

    // CU01 pasos 3 a 10: el LoginRequest valida el formato (paso 4) y
    // authenticate() hace los pasos 5 a 9; aquí se termina el inicio de sesión.
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // RN-06: nuevo id de sesión al autenticarse (evita la fijación de sesión).
        $request->session()->regenerate();

        // Marca para el control de inactividad (RNF-03).
        $request->session()->put('ultima_actividad', now()->timestamp);

        // CU06, post-condición: con contraseña temporal, primero debe cambiarla.
        if ($request->user()->debe_cambiar_contrasena) {
            return redirect()->route('contrasena.mostrar')
                ->with('aviso', 'Debe reemplazar su contraseña temporal antes de continuar.');
        }

        // CU01 paso 10 (RN-05): "inicio" envía a cada rol a su pantalla.
        return redirect()->route('inicio');
    }

    // CU02 pasos 2 a 4: CERRAR la sesión del usuario.
    public function destroy(Request $request): RedirectResponse
    {
        // Se registra ANTES de cerrar, mientras todavía se conoce al usuario.
        BitacoraService::registrar(BitacoraService::CIERRE_SESION, $request->user()->id);

        // Salir, invalidar la sesión y regenerar el token CSRF.
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('exito', 'Sesión cerrada correctamente.');
    }
}
