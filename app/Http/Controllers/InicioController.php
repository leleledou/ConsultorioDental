<?php

namespace App\Http\Controllers;

use App\Services\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pantallas de entrada después de iniciar sesión (RN-05).
 */
class InicioController extends Controller
{
    // CU01 paso 10: REDIRIGIR al panel, la pantalla inicial de los dos roles.
    // El panel muestra a cada rol solo sus opciones (RN-05).
    public function inicio(): RedirectResponse
    {
        return redirect()->route('panel');
    }

    // MOSTRAR el panel (los dos roles): tablero con una tarjeta por paquete.
    public function panel(Request $request): View
    {
        // Las mismas opciones del menú principal, filtradas por rol (RN-05).
        $paquetes = MenuService::paquetes($request->user());

        return view('panel', compact('paquetes'));
    }

    // MOSTRAR la agenda del día (ambos roles; el administrador también es odontólogo).
    public function agenda(): View
    {
        return view('agenda');
    }
}
