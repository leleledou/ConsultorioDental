<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de entrada después de iniciar sesión (RN-05).
 */
class InicioController extends Controller
{
    // CU01 paso 10: REDIRIGIR a cada rol a su pantalla (RN-05).
    public function inicio(Request $request): RedirectResponse
    {
        if ($request->user()->rol === 'administrador') {
            return redirect()->route('panel');
        }

        return redirect()->route('agenda');
    }

    // MOSTRAR el panel de administración (solo administrador).
    public function panel(): View
    {
        return view('panel');
    }

    // MOSTRAR la agenda del día (ambos roles; el administrador también es odontólogo).
    public function agenda(): View
    {
        return view('agenda');
    }
}
