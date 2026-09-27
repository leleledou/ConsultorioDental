<?php

namespace App\Http\Controllers;

use App\Models\Bitacora;
use App\Services\BitacoraService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CU07 Consultar bitácora. Solo lectura (RN-02): no hay funciones para
 * crear, modificar ni eliminar registros; la bitácora se llena solo
 * mediante BitacoraService.
 */
class BitacoraController extends Controller
{
    // MOSTRAR la lista de registros con filtros opcionales (CU07 pasos 2 a 4).
    public function index(Request $request)
    {
        // CU07 paso 3: validar los filtros.
        $reglas = [
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'accion' => ['nullable', Rule::in(BitacoraService::acciones())],
            'ip' => ['nullable', 'string', 'max:45'],
        ];

        // El rango solo se compara cuando llegan las dos fechas.
        if ($request->filled('fecha_desde')) {
            $reglas['fecha_hasta'][] = 'after_or_equal:fecha_desde';
        }

        $request->validate($reglas, [
            'fecha_desde.date' => 'La fecha inicial no es válida.',
            'fecha_hasta.date' => 'La fecha final no es válida.',
            'fecha_hasta.after_or_equal' => 'El rango de fechas es inválido: la fecha final debe ser igual o posterior a la inicial.',
            'usuario_id.integer' => 'El usuario seleccionado no es válido.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            'accion.in' => 'La acción seleccionada no es válida.',
            'ip.string' => 'La IP no es válida.',
            'ip.max' => 'La IP no puede superar los 45 caracteres.',
        ]);

        // CU07 paso 4: consultar aplicando solo los filtros que llegaron.
        $registros = Bitacora::with('usuario')
            // Desde el inicio del día de fecha_desde.
            ->when($request->filled('fecha_desde'), function ($consulta) use ($request) {
                $consulta->where('fecha_hora', '>=', $request->date('fecha_desde')->startOfDay());
            })
            // Hasta el final del día de fecha_hasta (incluye todo ese día).
            ->when($request->filled('fecha_hasta'), function ($consulta) use ($request) {
                $consulta->where('fecha_hora', '<=', $request->date('fecha_hasta')->endOfDay());
            })
            ->when($request->filled('usuario_id'), function ($consulta) use ($request) {
                $consulta->where('usuario_id', $request->input('usuario_id'));
            })
            ->when($request->filled('accion'), function ($consulta) use ($request) {
                $consulta->where('accion', $request->input('accion'));
            })
            ->when($request->filled('ip'), function ($consulta) use ($request) {
                $consulta->where('ip', $request->input('ip'));
            })
            // Lo más reciente primero; el id desempata registros del mismo segundo.
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->paginate(20)
            // Conserva los filtros en los enlaces de las páginas.
            ->withQueryString();

        // TEMPORAL: reemplazar por la vista Blade en la tarea de vistas
        return response()->json([
            'mensaje' => $registros->total() === 0 ? 'No existen registros para los filtros aplicados.' : null,
            'registros' => $registros,
        ]);
    }

    // MOSTRAR UN registro de la bitácora con su usuario (CU07 paso 5).
    public function show($id)
    {
        $registro = Bitacora::with('usuario')->findOrFail($id);

        // TEMPORAL: reemplazar por la vista Blade en la tarea de vistas
        return response()->json($registro);
    }
}
