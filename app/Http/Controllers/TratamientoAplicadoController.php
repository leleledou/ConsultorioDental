<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use App\Models\EstudioDiagnostico;
use App\Models\PiezaDental;
use App\Models\PlanTratamiento;
use App\Models\Sesion;
use App\Models\Tratamiento;
use App\Models\TratamientoAplicado;
use App\Models\TratamientoTipoEstudio;
use App\Services\BitacoraService;
use App\Services\PlanTratamientoService;
use App\Services\TarifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU28 Registrar tratamiento aplicado y CU29 Actualizar estado del tratamiento aplicado.
 * Para los dos roles (el administrador también es odontólogo).
 * El costo acordado queda congelado: nada lo recalcula si cambia la tarifa (RF-37).
 */
class TratamientoAplicadoController extends Controller
{
    public function __construct(
        private TarifaService $tarifas,
        private PlanTratamientoService $planes
    ) {
    }

    // CU28: MOSTRAR el formulario de agregar un tratamiento a un plan activo.
    public function create($plan): View|RedirectResponse
    {
        $plan = PlanTratamiento::with('paciente')->findOrFail($plan);

        if ($plan->estado !== 'activo') {
            return redirect()->route('planes.mostrar', $plan->id)->withErrors(['operacion' => 'El plan no está activo.']);
        }

        $tratamientos = Tratamiento::where('estado', 'disponible')->get();

        // Propuestas de la vista por tratamiento: precio vigente de hoy y sesiones del catálogo.
        $precios = [];
        $sesiones = [];
        foreach ($tratamientos as $tratamiento) {
            $precios[$tratamiento->id] = $this->tarifas->precioVigente($tratamiento->id, today());
            $sesiones[$tratamiento->id] = $tratamiento->numero_sesiones_previstas;
        }

        $requeridos = TratamientoTipoEstudio::with('tipoEstudio')
            ->where('obligatorio', true)
            ->where('momento_clinico', 'previo al tratamiento')
            ->get();

        $cargados = EstudioDiagnostico::where('paciente_id', $plan->paciente_id)->pluck('tipo_estudio_id');

        // RF-39: estudios previos obligatorios que le faltan al paciente, por tratamiento.
        $faltantes = [];
        foreach ($requeridos as $requerido) {
            if (! $cargados->contains($requerido->tipo_estudio_id)) {
                $faltantes[$requerido->tratamiento_id][] = $requerido->tipoEstudio->nombre;
            }
        }

        $diagnosticos = Diagnostico::where('estado', 'activo')->orderBy('nombre')->get();
        $piezas = PiezaDental::orderBy('codigo_fdi')->get();

        return view('tratamientos-aplicados.create', compact(
            'plan', 'tratamientos', 'precios', 'sesiones', 'faltantes', 'diagnosticos', 'piezas'
        ));
    }

    // CU28: GUARDAR el tratamiento en el plan, planificado y a nombre del odontólogo del plan.
    public function store(Request $request): RedirectResponse
    {
        // Flujo A1: mensaje junto al campo; conserva los datos.
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $plan = PlanTratamiento::lockForUpdate()->findOrFail($datos['plan_tratamiento_id']);

            if ($plan->estado !== 'activo') {
                return 'El plan no está activo.';
            }

            $tratamiento = TratamientoAplicado::create($datos + [
                'estado' => 'planificado',
                'odontologo_id' => $plan->odontologo_id,
            ]);

            $this->planes->recalcularCosto($plan);

            BitacoraService::registrar(BitacoraService::REGISTRAR_TRATAMIENTO_APLICADO, $request->user()->id, 'tratamientos_aplicados', $tratamiento->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->route('planes.mostrar', $datos['plan_tratamiento_id'])
            ->with('exito', 'Tratamiento registrado en el plan.');
    }

    // CU29: MOSTRAR UN tratamiento aplicado con su avance y los estados a los que puede pasar.
    public function show($id): View
    {
        $tratamiento = TratamientoAplicado::with('tratamiento', 'diagnostico', 'plan.paciente', 'piezaDental')
            ->findOrFail($id);

        $realizadas = Sesion::where('tratamiento_aplicado_id', $id)->where('estado', 'realizada')->count();

        $destinos = $this->estadosDestino($tratamiento->estado);

        return view('tratamientos-aplicados.show', compact('tratamiento', 'realizadas', 'destinos'));
    }

    // CU29: ACTUALIZAR el estado de un tratamiento aplicado. El id viene en el formulario.
    public function cambiarEstado(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'estado' => ['required', Rule::in(['planificado', 'en proceso', 'concluido', 'cancelado'])],
            'motivo' => ['required_if:estado,cancelado', 'nullable', 'string', 'max:255'],
            'confirmado' => ['boolean'],
        ], $this->mensajes());

        $resultado = DB::transaction(function () use ($datos, $request) {
            $tratamiento = TratamientoAplicado::lockForUpdate()->findOrFail($datos['id']);
            $plan = PlanTratamiento::lockForUpdate()->findOrFail($tratamiento->plan_tratamiento_id);
            $actual = $tratamiento->estado;
            $nuevo = $datos['estado'];

            if ($plan->estado !== 'activo') {
                return ['operacion' => 'El plan no está activo.'];
            }

            // Flujo A1: transición no permitida; el estado no cambia.
            if (! in_array($nuevo, $this->estadosDestino($actual), true)) {
                return ['error' => "No se puede pasar de {$actual} a {$nuevo}"];
            }

            // fecha_fin (hoy) no puede ser anterior a fecha_inicio: lo exige ck_tratamientos_aplicados_fechas.
            if ($nuevo === 'concluido' && today()->lt($tratamiento->fecha_inicio)) {
                return ['error' => "No se puede concluir antes de la fecha de inicio ({$tratamiento->fecha_inicio->format('d/m/Y')})"];
            }

            // Flujo A2: concluir con sesiones pendientes pide confirmación.
            if ($nuevo === 'concluido') {
                $realizadas = Sesion::where('tratamiento_aplicado_id', $tratamiento->id)->where('estado', 'realizada')->count();

                if ($realizadas < $tratamiento->sesiones_planificadas && empty($datos['confirmado'])) {
                    return ['confirmar' => $tratamiento->sesiones_planificadas - $realizadas];
                }

                $tratamiento->fecha_fin = today();
            }

            $tratamiento->estado = $nuevo;

            if ($nuevo === 'cancelado') {
                $tratamiento->observaciones = trim(($tratamiento->observaciones ?? '')."\n[Cancelado el ".today()->format('d/m/Y')."] {$datos['motivo']}");
            }

            $tratamiento->save();

            if ($nuevo === 'cancelado') {
                $this->planes->recalcularCosto($plan);
            }

            BitacoraService::registrar(BitacoraService::CAMBIO_ESTADO_TRATAMIENTO, $request->user()->id, 'tratamientos_aplicados', $tratamiento->id);

            return [];
        });

        if (isset($resultado['operacion'])) {
            return $this->error($resultado['operacion']);
        }

        if (isset($resultado['error'])) {
            return redirect()->back()->withErrors(['estado' => $resultado['error']]);
        }

        if (isset($resultado['confirmar'])) {
            return redirect()->back()->with('confirmar', $resultado['confirmar']);
        }

        return redirect()->back()->with('exito', 'Estado del tratamiento actualizado.');
    }

    // CU29: estados a los que puede pasar un tratamiento desde el estado indicado.
    private function estadosDestino(string $estado): array
    {
        return match ($estado) {
            'planificado' => ['en proceso', 'cancelado'],
            'en proceso' => ['concluido', 'cancelado'],
            default => [],
        };
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:tratamientos_aplicados,id']];
    }

    // Reglas de los datos del tratamiento aplicado. Límites superiores según las columnas.
    private function reglas(): array
    {
        return [
            'plan_tratamiento_id' => ['required', 'integer', 'exists:planes_tratamiento,id'],
            'tratamiento_id' => ['required', 'integer', Rule::exists('tratamientos', 'id')->where('estado', 'disponible')],
            'pieza_dental_id' => ['nullable', 'integer', 'exists:piezas_dentales,id'],
            'diagnostico_id' => ['required', 'integer', Rule::exists('diagnosticos', 'id')->where('estado', 'activo')],
            'observacion_diagnostica' => ['nullable', 'string'],
            'costo_acordado' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'sesiones_planificadas' => ['required', 'integer', 'min:1', 'max:65535'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el tratamiento aplicado.',
            'id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'id.exists' => 'El tratamiento aplicado indicado no existe.',
            'plan_tratamiento_id.required' => 'No se indicó el plan.',
            'plan_tratamiento_id.integer' => 'El plan indicado no es válido.',
            'plan_tratamiento_id.exists' => 'El plan indicado no existe.',
            'tratamiento_id.required' => 'Debe seleccionar el tratamiento.',
            'tratamiento_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_id.exists' => 'El tratamiento indicado no existe o no está disponible.',
            'pieza_dental_id.integer' => 'La pieza dental indicada no es válida.',
            'pieza_dental_id.exists' => 'La pieza dental indicada no existe.',
            'diagnostico_id.required' => 'Debe seleccionar el diagnóstico.',
            'diagnostico_id.integer' => 'El diagnóstico indicado no es válido.',
            'diagnostico_id.exists' => 'El diagnóstico indicado no existe o está inactivo.',
            'observacion_diagnostica.string' => 'La observación diagnóstica debe ser un texto.',
            'costo_acordado.required' => 'El campo Costo acordado es obligatorio.',
            'costo_acordado.numeric' => 'El costo acordado debe ser un número.',
            'costo_acordado.min' => 'El costo acordado no puede ser negativo.',
            'costo_acordado.max' => 'El costo acordado no puede superar 99999999.99.',
            'sesiones_planificadas.required' => 'El campo Sesiones planificadas es obligatorio.',
            'sesiones_planificadas.integer' => 'Las sesiones planificadas deben ser un número entero.',
            'sesiones_planificadas.min' => 'Debe haber al menos 1 sesión planificada.',
            'sesiones_planificadas.max' => 'Las sesiones planificadas no pueden superar 65535.',
            'fecha_inicio.required' => 'El campo Fecha de inicio es obligatorio.',
            'fecha_inicio.date_format' => 'La fecha de inicio no es válida.',
            'estado.required' => 'Debe seleccionar el nuevo estado.',
            'estado.in' => 'El estado indicado no es válido.',
            'motivo.required_if' => 'Debe indicar el motivo de la cancelación.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'confirmado.boolean' => 'La confirmación indicada no es válida.',
        ];
    }
}
