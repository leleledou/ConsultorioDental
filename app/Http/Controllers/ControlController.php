<?php

namespace App\Http\Controllers;

use App\Models\Control;
use App\Models\TratamientoAplicado;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\ControlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU38 Programar control (RF-79, RF-80) y el disparo manual de CU39 Actualizar controles
 * vencidos (flujo alternativo del Temporizador, ver App\Console\Commands\VencerControles).
 * Para los dos roles (el administrador también es odontólogo).
 * El control no guarda paciente_id: se deriva por el tratamiento aplicado (ControlService).
 */
class ControlController extends Controller
{
    public function __construct(private ControlService $controles)
    {
    }

    // MOSTRAR los controles: primero los pendientes por fecha, después el resto. Filtros por estado y odontólogo.
    public function index(Request $request): View
    {
        $filtros = $request->validate(
            [
                'estado' => ['nullable', Rule::in(['pendiente', 'cumplido', 'vencido'])],
                'odontologo_id' => ['nullable', 'integer', 'exists:users,id'],
            ],
            [
                'estado.in' => 'El estado del filtro debe ser pendiente, cumplido o vencido.',
                'odontologo_id.integer' => 'El odontólogo del filtro no es válido.',
                'odontologo_id.exists' => 'El odontólogo del filtro no existe.',
            ]
        );

        $estado = $filtros['estado'] ?? null;
        $odontologoId = $filtros['odontologo_id'] ?? null;

        // El odontólogo es el responsable del tratamiento aplicado.
        $base = fn () => Control::query()
            ->when($odontologoId, fn ($q, $id) => $q->whereHas('tratamientoAplicado', fn ($t) => $t->where('odontologo_id', $id)));

        $controles = $base()
            ->with('tratamientoAplicado.tratamiento', 'tratamientoAplicado.plan.paciente', 'tratamientoAplicado.odontologo')
            ->when($estado, fn ($q, $e) => $q->where('estado', $e))
            ->orderByRaw("CASE estado WHEN 'pendiente' THEN 0 WHEN 'vencido' THEN 1 ELSE 2 END")
            ->orderByRaw("CASE WHEN estado = 'pendiente' THEN fecha_prevista END ASC")
            ->orderByDesc('fecha_prevista')
            ->paginate(15)
            // Conserva los filtros en los enlaces de las páginas.
            ->withQueryString();

        // Contadores del encabezado (respetan el filtro de odontólogo, no el de estado).
        $contadores = $base()->selectRaw('estado, count(*) as total')->groupBy('estado')->pluck('total', 'estado');

        $odontologos = User::where('estado', 'activo')->orderBy('apellidos')->orderBy('nombres')->get();

        return view('controles.index', compact('controles', 'contadores', 'odontologos', 'estado', 'odontologoId'));
    }

    // MOSTRAR el formulario de programar un control para el tratamiento aplicado.
    public function create($ta): View
    {
        $tratamiento = TratamientoAplicado::with('tratamiento', 'plan.paciente', 'controles')->findOrFail($ta);

        return view('controles.create', compact('tratamiento'));
    }

    // CREAR un control pendiente. El tratamiento aplicado viene oculto en el formulario.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $ta = TratamientoAplicado::lockForUpdate()->findOrFail($datos['tratamiento_aplicado_id']);

            if ($ta->estado === 'cancelado') {
                return ['operacion' => 'No se pueden programar controles en un tratamiento cancelado.'];
            }

            // RF-79: el control es posterior al inicio del tratamiento (el mismo día no cuenta).
            if ($datos['fecha_prevista'] <= $ta->fecha_inicio->toDateString()) {
                return ['fecha_prevista' => 'La fecha del control debe ser posterior a la del tratamiento.'];
            }

            $control = Control::create($datos + ['estado' => 'pendiente']);

            BitacoraService::registrar(BitacoraService::PROGRAMAR_CONTROL, $request->user()->id, 'controles', $control->id);

            return null;
        });

        if ($error) {
            return redirect()->back()->withInput()->withErrors($error);
        }

        return redirect()->route('tratamientos-aplicados.mostrar', $datos['tratamiento_aplicado_id'])
            ->with('exito', 'Control programado correctamente.');
    }

    // MARCAR COMO CUMPLIDO un control pendiente o vencido (un vencido se atendió tarde). El id viene en el formulario.
    public function cumplir(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'id' => ['required', 'integer', 'exists:controles,id'],
            'fecha_cumplimiento' => ['bail', 'nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string'],
        ], $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $control = Control::lockForUpdate()->findOrFail($datos['id']);

            if ($control->estado === 'cumplido') {
                return 'Este control ya fue marcado como cumplido.';
            }

            $control->update([
                'estado' => 'cumplido',
                'fecha_cumplimiento' => $datos['fecha_cumplimiento'] ?? today(),
                'observaciones' => $datos['observaciones'] ?? $control->observaciones,
            ]);

            BitacoraService::registrar(BitacoraService::CUMPLIR_CONTROL, $request->user()->id, 'controles', $control->id);

            return null;
        });

        if ($error) {
            return redirect()->back()->withErrors(['operacion' => $error]);
        }

        return redirect()->back()->with('exito', 'Control marcado como cumplido.');
    }

    // CU39, flujo alternativo: el usuario dispara a mano lo que hace el Temporizador cada día.
    public function vencer(Request $request): RedirectResponse
    {
        $cantidad = DB::transaction(function () use ($request) {
            $cantidad = $this->controles->vencerPendientes();

            BitacoraService::registrar(BitacoraService::VENCER_CONTROLES, $request->user()->id, 'controles', null);

            return $cantidad;
        });

        return redirect()->back()->with('exito', $cantidad > 0
            ? "Se actualizaron {$cantidad} controles vencidos."
            : 'No hay controles vencidos por actualizar.');
    }

    // Reglas del control nuevo.
    private function reglas(): array
    {
        return [
            'tratamiento_aplicado_id' => ['required', 'integer', 'exists:tratamientos_aplicados,id'],
            'fecha_prevista' => ['bail', 'required', 'date_format:Y-m-d'],
            'motivo' => ['nullable', 'string', 'max:150'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el control.',
            'id.integer' => 'El control indicado no es válido.',
            'id.exists' => 'El control indicado no existe.',
            'tratamiento_aplicado_id.required' => 'No se indicó el tratamiento aplicado.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento aplicado indicado no existe.',
            'fecha_prevista.required' => 'El campo Fecha prevista es obligatorio.',
            'fecha_prevista.date_format' => 'La fecha prevista debe tener el formato AAAA-MM-DD.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 150 caracteres.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'fecha_cumplimiento.date_format' => 'La fecha de cumplimiento debe tener el formato AAAA-MM-DD.',
            'fecha_cumplimiento.before_or_equal' => 'La fecha de cumplimiento no puede ser futura.',
        ];
    }
}
