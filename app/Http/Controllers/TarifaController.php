<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Models\Tratamiento;
use App\Services\BitacoraService;
use App\Services\TarifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * CU16 Gestionar tarifas. Solo para el administrador.
 * Las reglas de vigencia están en TarifaService.
 * Una tarifa nunca se modifica ni se elimina: un precio nuevo cierra la vigente.
 */
class TarifaController extends Controller
{
    public function __construct(private TarifaService $tarifas)
    {
    }

    // MOSTRAR la lista de tratamientos con su tarifa vigente, con buscador por nombre del tratamiento.
    public function index(Request $request): View
    {
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            [
                'buscar.string' => 'El texto de búsqueda no es válido.',
                'buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.',
            ]
        );

        $buscar = $request->input('buscar');

        $tratamientos = Tratamiento::with('tarifaVigente')
            ->when($buscar, fn ($consulta, $buscar) => $consulta->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            // Conserva ?buscar=... en los enlaces de las páginas.
            ->withQueryString();

        return view('tarifas.index', compact('tratamientos', 'buscar'));
    }

    // CREAR una tarifa vigente; la anterior queda cerrada el día previo.
    public function store(Request $request): RedirectResponse
    {
        // Flujo A1: precio no válido.
        $datos = $request->validate($this->reglas(), $this->mensajes());

        // El cierre y el registro van en la misma transacción, con la vigente bloqueada.
        $error = DB::transaction(function () use ($datos, $request) {
            $error = $this->tarifas->cerrarVigente((int) $datos['tratamiento_id'], $datos['fecha_inicio_vigencia']);

            if ($error) {
                return $error;
            }

            $tarifa = Tarifa::create($datos + ['estado' => 'vigente']);

            BitacoraService::registrar(BitacoraService::CREAR_TARIFA, $request->user()->id, 'tarifas', $tarifa->id);

            return null;
        });

        // Flujo A2: la fecha no es posterior al inicio de la vigente, no se cerró nada.
        if ($error) {
            return redirect()->back()->withErrors(['fecha_inicio_vigencia' => $error])->withInput();
        }

        return redirect()->back()->with('exito', 'Precio registrado. La tarifa anterior quedó cerrada.');
    }

    // MOSTRAR el historial de tarifas de UN tratamiento. El id es el del tratamiento.
    public function show($id): View
    {
        $tratamiento = Tratamiento::findOrFail($id);

        $tarifas = Tarifa::where('tratamiento_id', $id)
            ->orderByDesc('fecha_inicio_vigencia')
            ->get();

        return view('tarifas.show', compact('tratamiento', 'tarifas'));
    }

    // Reglas de los datos. bail: un precio no válido muestra un solo mensaje.
    private function reglas(): array
    {
        return [
            'tratamiento_id' => ['required', 'integer', 'exists:tratamientos,id'],
            // Límite superior: la columna es decimal(10,2).
            'precio' => ['bail', 'required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'fecha_inicio_vigencia' => ['required', 'date_format:Y-m-d'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'tratamiento_id.required' => 'No se indicó el tratamiento.',
            'tratamiento_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_id.exists' => 'El tratamiento indicado no existe.',
            'precio.required' => 'El precio debe ser mayor a 0',
            'precio.numeric' => 'El precio debe ser mayor a 0',
            'precio.decimal' => 'El precio debe tener como máximo 2 decimales.',
            'precio.gt' => 'El precio debe ser mayor a 0',
            'precio.max' => 'El precio no puede superar 99999999.99.',
            'fecha_inicio_vigencia.required' => 'El campo Inicio de vigencia es obligatorio.',
            'fecha_inicio_vigencia.date_format' => 'La fecha de inicio de vigencia no es válida.',
        ];
    }
}
