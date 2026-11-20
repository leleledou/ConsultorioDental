<?php

namespace App\Http\Controllers;

use App\Services\MenuService;
use App\Services\PanelService;
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

    // MOSTRAR el panel (los dos roles): Dashboard con indicadores y gráficos, y una tarjeta por paquete.
    public function panel(Request $request, PanelService $panel): View
    {
        // Las mismas opciones del menú principal, filtradas por rol (RN-05).
        $paquetes = MenuService::paquetes($request->user());

        // Dashboard: cifras y gráficos con el alcance del rol (ver PanelService).
        $indicadores = $panel->indicadores($request->user());
        $graficos = $panel->graficos($request->user());

        return view('panel', compact('paquetes', 'indicadores', 'graficos'));
    }
}
