<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Material;
use App\Models\Sesion;
use App\Models\SesionMaterial;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;
use App\Services\SesionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU30 Registrar sesión de evolución (RF-43 a RF-50, RF-66) y
 * CU31 Consultar evolución del tratamiento (RF-43, RF-44).
 * Para los dos roles (el administrador también es odontólogo).
 * La numeración y la ampliación de sesiones están en SesionService.
 * Los materiales de una sesión ya registrada se gestionan en SesionMaterialController.
 */
class SesionController extends Controller
{
    public function __construct(private SesionService $sesiones)
    {
    }

    // MOSTRAR el formulario de registrar una sesión del tratamiento aplicado.
    public function create($ta): View
    {
        $tratamiento = $this->cargarTratamiento($ta);

        return view('sesiones.create', $this->datosFormulario($tratamiento));
    }

    // RF-49: MOSTRAR el formulario de sesión a partir de una cita atendida.
    // Si la cita no tiene tratamiento aplicado, primero se elige uno activo del paciente.
    public function desdeCita(Request $request, $cita): View|RedirectResponse
    {
        $cita = Cita::with('estado', 'paciente')->findOrFail($cita);

        if ($cita->estado->nombre !== EstadoCita::ATENDIDA) {
            return redirect()->route('citas.mostrar', $cita->id)
                ->withErrors(['operacion' => 'La cita debe estar en estado atendida para generar la sesión.']);
        }

        // La cita ya indica el tratamiento: se usa ese.
        if ($cita->tratamiento_aplicado_id) {
            return view('sesiones.create', $this->datosFormulario($this->cargarTratamiento($cita->tratamiento_aplicado_id), $cita));
        }

        $activos = TratamientoAplicado::with('tratamiento', 'piezaDental')
            ->whereHas('plan', fn ($q) => $q->where('paciente_id', $cita->paciente_id))
            ->whereIn('estado', ['planificado', 'en proceso'])
            ->orderBy('fecha_inicio')
            ->get();

        // Tratamiento elegido en el paso anterior (?tratamiento_aplicado_id=).
        if ($request->filled('tratamiento_aplicado_id')) {
            $request->validate(
                ['tratamiento_aplicado_id' => ['integer', Rule::in($activos->pluck('id')->all())]],
                $this->mensajes()
            );

            return view('sesiones.create', $this->datosFormulario($this->cargarTratamiento($request->input('tratamiento_aplicado_id')), $cita));
        }

        return view('sesiones.elegir-tratamiento', compact('cita', 'activos'));
    }

    // CREAR una sesión: el número lo calcula el servicio y el odontólogo es el de la sesión iniciada.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $resultado = DB::transaction(function () use ($datos, $request) {
            // Bloquea el tratamiento: dos sesiones simultáneas no pueden tomar el mismo número.
            $ta = TratamientoAplicado::with('plan')->lockForUpdate()->findOrFail($datos['tratamiento_aplicado_id']);

            // Cancelado y concluido son estados terminales (ver estadosDestino de CU29).
            if ($ta->estado === 'cancelado') {
                return ['operacion' => 'No se pueden registrar sesiones en un tratamiento cancelado.'];
            }

            if ($ta->estado === 'concluido') {
                return ['operacion' => 'No se pueden registrar sesiones en un tratamiento concluido.'];
            }

            if ($datos['fecha'] < $ta->fecha_inicio->toDateString()) {
                return ['fecha' => 'La sesión no puede ser anterior al inicio del tratamiento ('.$ta->fecha_inicio->format('d/m/Y').').'];
            }

            $cita = null;
            if (! empty($datos['cita_id'])) {
                $cita = Cita::with('estado')->lockForUpdate()->findOrFail($datos['cita_id']);

                if ($error = $this->validarCita($cita, $ta)) {
                    return ['operacion' => $error];
                }
            }

            // RF-50: superar lo planificado exige marcar la ampliación.
            if ($this->sesiones->excedeSesionesPlanificadas($ta)) {
                if (empty($datos['ampliar'])) {
                    return ['ampliar' => "El tratamiento tiene {$ta->sesiones_planificadas} sesiones planificadas. Marque la ampliación para continuar."];
                }

                $this->sesiones->ampliarSesiones($ta, $this->sesiones->numeroSiguiente($ta));
            }

            $sesion = Sesion::create([
                'tratamiento_aplicado_id' => $ta->id,
                'odontologo_id' => $request->user()->id,
                'cita_id' => $cita?->id,
                'numero_sesion' => $this->sesiones->numeroSiguiente($ta),
                'fecha' => $datos['fecha'],
                'avance_tecnico' => $datos['avance_tecnico'] ?? null,
                'duracion_real_minutos' => $datos['duracion_real_minutos'],
                'observaciones' => $datos['observaciones'] ?? null,
                'fecha_proxima_sesion' => $datos['fecha_proxima_sesion'] ?? null,
                'estado' => 'realizada',
            ]);

            // RF-45: materiales cargados junto con la sesión (opcional).
            foreach ($datos['materiales'] ?? [] as $material) {
                SesionMaterial::create([
                    'sesion_id' => $sesion->id,
                    'material_id' => $material['material_id'],
                    'cantidad' => $material['cantidad'],
                    'observacion' => $material['observacion'] ?? null,
                ]);
            }

            // RF-66: la cita queda vinculada al tratamiento atendido (trazabilidad agenda <-> registro clínico).
            if ($cita && $cita->tratamiento_aplicado_id === null) {
                $cita->tratamiento_aplicado_id = $ta->id;
                $cita->save();
            }

            // La primera sesión pone en marcha un tratamiento planificado.
            if ($ta->estado === 'planificado') {
                $ta->estado = 'en proceso';
                $ta->save();

                BitacoraService::registrar(BitacoraService::CAMBIO_ESTADO_TRATAMIENTO, $request->user()->id, 'tratamientos_aplicados', $ta->id);
            }

            BitacoraService::registrar(BitacoraService::REGISTRAR_SESION, $request->user()->id, 'sesiones', $sesion->id);

            return $sesion;
        });

        if (is_array($resultado)) {
            return redirect()->back()->withInput()->withErrors($resultado);
        }

        return redirect()->route('sesiones.mostrar', $resultado->id)
            ->with('exito', 'Sesión registrada correctamente.');
    }

    // MOSTRAR UNA sesión con sus materiales y el catálogo para agregar otros.
    public function show($id): View
    {
        $sesion = Sesion::with('tratamientoAplicado.tratamiento', 'tratamientoAplicado.plan.paciente', 'odontologo', 'cita', 'materiales.material')
            ->findOrFail($id);

        $materiales = Material::where('estado', 'activo')
            ->whereNotIn('id', $sesion->materiales->pluck('material_id'))
            ->orderBy('nombre')
            ->get();

        return view('sesiones.show', compact('sesion', 'materiales'));
    }

    // CU31: MOSTRAR la evolución del tratamiento, sesión por sesión en orden cronológico.
    public function evolucion($ta): View
    {
        $tratamiento = $this->cargarTratamiento($ta);

        $sesiones = Sesion::with('odontologo', 'materiales.material')
            ->where('tratamiento_aplicado_id', $tratamiento->id)
            ->orderBy('fecha')
            ->orderBy('numero_sesion')
            ->get();

        $realizadas = $sesiones->where('estado', 'realizada')->count();

        return view('sesiones.evolucion', compact('tratamiento', 'sesiones', 'realizadas'));
    }

    // Tratamiento aplicado con lo que muestran las vistas de sesiones.
    private function cargarTratamiento($id): TratamientoAplicado
    {
        return TratamientoAplicado::with('tratamiento', 'piezaDental', 'plan.paciente')->findOrFail($id);
    }

    // Datos del formulario de sesión; con cita, precarga su fecha y su duración (RF-49).
    private function datosFormulario(TratamientoAplicado $tratamiento, ?Cita $cita = null): array
    {
        return [
            'tratamiento' => $tratamiento,
            'cita' => $cita,
            'realizadas' => Sesion::where('tratamiento_aplicado_id', $tratamiento->id)->where('estado', 'realizada')->count(),
            'numero' => $this->sesiones->numeroSiguiente($tratamiento),
            'excede' => $this->sesiones->excedeSesionesPlanificadas($tratamiento),
            'materiales' => Material::where('estado', 'activo')->orderBy('nombre')->get(),
            'fechaPropuesta' => $cita ? $cita->fecha->toDateString() : today()->toDateString(),
            'duracionPropuesta' => $cita?->duracion_minutos,
        ];
    }

    // Reglas de la cita de origen: atendida, del mismo paciente y sin otro tratamiento ni otra sesión.
    private function validarCita(Cita $cita, TratamientoAplicado $ta): ?string
    {
        if ($cita->estado->nombre !== EstadoCita::ATENDIDA) {
            return 'La cita debe estar en estado atendida para generar la sesión.';
        }

        if ((int) $cita->paciente_id !== (int) $ta->plan->paciente_id) {
            return 'La cita no pertenece al paciente del tratamiento.';
        }

        if ($cita->tratamiento_aplicado_id !== null && (int) $cita->tratamiento_aplicado_id !== (int) $ta->id) {
            return 'La cita está vinculada a otro tratamiento aplicado.';
        }

        if (Sesion::where('cita_id', $cita->id)->exists()) {
            return 'La cita ya tiene una sesión registrada.';
        }

        return null;
    }

    // Reglas de los datos de la sesión. El número y el odontólogo no vienen del formulario.
    private function reglas(): array
    {
        return [
            'tratamiento_aplicado_id' => ['required', 'integer', 'exists:tratamientos_aplicados,id'],
            'cita_id' => ['nullable', 'integer', 'exists:citas,id'],
            'fecha' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'duracion_real_minutos' => ['bail', 'required', 'integer', 'between:1,600'],
            'avance_tecnico' => ['nullable', 'string'],
            'observaciones' => ['nullable', 'string'],
            'fecha_proxima_sesion' => ['nullable', 'date_format:Y-m-d', 'after:fecha'],
            'ampliar' => ['nullable', 'boolean'],
            'materiales' => ['nullable', 'array'],
            'materiales.*.material_id' => ['required', 'integer', 'distinct', Rule::exists('materiales', 'id')->where('estado', 'activo')],
            'materiales.*.cantidad' => ['bail', 'required', 'numeric', 'gt:0', 'max:999999.99'],
            'materiales.*.observacion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'tratamiento_aplicado_id.required' => 'No se indicó el tratamiento aplicado.',
            'tratamiento_aplicado_id.integer' => 'El tratamiento aplicado indicado no es válido.',
            'tratamiento_aplicado_id.exists' => 'El tratamiento aplicado indicado no existe.',
            'tratamiento_aplicado_id.in' => 'Seleccione un tratamiento activo del paciente de la cita.',
            'cita_id.integer' => 'La cita indicada no es válida.',
            'cita_id.exists' => 'La cita indicada no existe.',
            'fecha.required' => 'El campo Fecha es obligatorio.',
            'fecha.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha.before_or_equal' => 'La fecha de la sesión no puede ser futura.',
            'duracion_real_minutos.required' => 'El campo Duración es obligatorio.',
            'duracion_real_minutos.integer' => 'La duración debe estar entre 1 y 600 minutos.',
            'duracion_real_minutos.between' => 'La duración debe estar entre 1 y 600 minutos.',
            'avance_tecnico.string' => 'El avance técnico debe ser un texto.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'fecha_proxima_sesion.date_format' => 'La próxima sesión debe tener el formato AAAA-MM-DD.',
            'fecha_proxima_sesion.after' => 'La próxima sesión debe ser posterior a la fecha de esta sesión.',
            'ampliar.boolean' => 'La ampliación indicada no es válida.',
            'materiales.array' => 'Los materiales indicados no son válidos.',
            'materiales.*.material_id.required' => 'Seleccione el material.',
            'materiales.*.material_id.integer' => 'El material indicado no es válido.',
            'materiales.*.material_id.distinct' => 'No repita un material en la misma sesión.',
            'materiales.*.material_id.exists' => 'El material indicado no existe o está dado de baja.',
            'materiales.*.cantidad.required' => 'Indique la cantidad del material.',
            'materiales.*.cantidad.numeric' => 'La cantidad debe ser un número.',
            'materiales.*.cantidad.gt' => 'La cantidad debe ser mayor a 0.',
            'materiales.*.cantidad.max' => 'La cantidad no puede superar 999999,99.',
            'materiales.*.observacion.string' => 'La observación del material debe ser un texto.',
        ];
    }
}
