<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Pago;
use App\Models\PlanTratamiento;
use App\Models\TratamientoAplicado;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\PlanTratamientoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU27 Gestionar plan de tratamiento.
 * Para los dos roles (el administrador también es odontólogo).
 * El costo total lo mantiene PlanTratamientoService.
 * Nunca se borran planes: un plan cancelado conserva sus tratamientos y pagos.
 */
class PlanTratamientoController extends Controller
{
    public function __construct(private PlanTratamientoService $planes)
    {
    }

    // MOSTRAR los planes de un paciente; sin paciente_id, solo el buscador de pacientes (CU09).
    public function index(Request $request): View
    {
        $request->validate(
            ['buscar' => ['nullable', 'string', 'max:100']],
            [
                'buscar.string' => 'El texto de búsqueda no es válido.',
                'buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.',
            ]
        );

        if (! $request->filled('paciente_id')) {
            $buscar = $request->input('buscar');

            // Mismo criterio de búsqueda que PacienteController::index.
            $pacientes = Paciente::buscar($buscar)
                ->orderBy('apellidos')
                ->orderBy('nombres')
                ->paginate(10)
                // Conserva ?buscar=... en los enlaces de las páginas.
                ->withQueryString();

            return view('planes.index', ['paciente' => null, 'pacientes' => $pacientes, 'buscar' => $buscar]);
        }

        $paciente = Paciente::findOrFail($request->input('paciente_id'));

        $planes = PlanTratamiento::with('odontologo')
            ->where('paciente_id', $paciente->id)
            ->latest('fecha_creacion')
            ->get();

        $odontologos = User::where('estado', 'activo')->orderBy('apellidos')->orderBy('nombres')->get();

        return view('planes.index', compact('paciente', 'planes', 'odontologos'));
    }

    // CREAR un plan para el paciente: activo, con la fecha de hoy y costo 0.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        DB::transaction(function () use ($datos, $request) {
            $plan = PlanTratamiento::create($datos + [
                'fecha_creacion' => today(),
                'costo_total' => 0,
                'estado' => 'activo',
            ]);

            BitacoraService::registrar(BitacoraService::CREAR_PLAN, $request->user()->id, 'planes_tratamiento', $plan->id);
        });

        return redirect()->back()->with('exito', 'Plan de tratamiento creado.');
    }

    // MOSTRAR UN plan con sus tratamientos aplicados.
    public function show($id): View
    {
        $plan = PlanTratamiento::with('paciente', 'odontologo')->findOrFail($id);

        $tratamientos = TratamientoAplicado::with('tratamiento', 'diagnostico', 'sesiones')
            ->where('plan_tratamiento_id', $id)
            ->get();

        return view('planes.show', compact('plan', 'tratamientos'));
    }

    // MODIFICAR la descripción de un plan activo. El id viene en el formulario.
    public function update(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'descripcion' => ['nullable', 'string'],
        ], $this->mensajes());

        $plan = PlanTratamiento::findOrFail($datos['id']);

        if ($plan->estado !== 'activo') {
            return $this->error('El plan no está activo.');
        }

        DB::transaction(function () use ($plan, $datos, $request) {
            $plan->update(['descripcion' => $datos['descripcion'] ?? null]);

            BitacoraService::registrar(BitacoraService::MODIFICAR_PLAN, $request->user()->id, 'planes_tratamiento', $plan->id);
        });

        return redirect()->back()->with('exito', 'Plan de tratamiento actualizado.');
    }

    // CANCELAR un plan sin tratamientos en proceso ni abonos. El id viene en el formulario.
    public function cancelar(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'motivo' => ['required', 'string', 'max:255'],
        ], $this->mensajes());

        $error = DB::transaction(function () use ($datos, $request) {
            $plan = PlanTratamiento::lockForUpdate()->findOrFail($datos['id']);

            if ($plan->estado !== 'activo') {
                return 'El plan no está activo.';
            }

            $n = TratamientoAplicado::where('plan_tratamiento_id', $plan->id)->where('estado', 'en proceso')->count()
                + Pago::where('estado', 'vigente')
                    ->whereHas('tratamientoAplicado', fn ($q) => $q->where('plan_tratamiento_id', $plan->id))
                    ->count();

            // Flujo A1: el plan conserva su estado.
            if ($n > 0) {
                return 'No se puede cancelar: el plan tiene tratamientos en proceso o abonos registrados';
            }

            $plan->estado = 'cancelado';
            // La tabla no tiene columna de motivo: queda al final de la descripción.
            $plan->descripcion = trim(($plan->descripcion ?? '')."\n[Cancelado el ".today()->format('d/m/Y')."] {$datos['motivo']}");
            $plan->save();

            $this->planes->recalcularCosto($plan);

            BitacoraService::registrar(BitacoraService::CANCELAR_PLAN, $request->user()->id, 'planes_tratamiento', $plan->id);

            return null;
        });

        if ($error) {
            return $this->error($error);
        }

        return redirect()->back()->with('exito', 'Plan cancelado.');
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:planes_tratamiento,id']];
    }

    // Reglas de los datos del plan nuevo: paciente y odontólogo activos.
    private function reglas(): array
    {
        return [
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('estado', 'activo')],
            'odontologo_id' => ['required', 'integer', Rule::exists('users', 'id')->where('estado', 'activo')],
            'descripcion' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'id.required' => 'No se indicó el plan.',
            'id.integer' => 'El plan indicado no es válido.',
            'id.exists' => 'El plan indicado no existe.',
            'paciente_id.required' => 'No se indicó el paciente.',
            'paciente_id.integer' => 'El paciente indicado no es válido.',
            'paciente_id.exists' => 'El paciente indicado no existe o está inactivo.',
            'odontologo_id.required' => 'Debe seleccionar el odontólogo responsable.',
            'odontologo_id.integer' => 'El odontólogo indicado no es válido.',
            'odontologo_id.exists' => 'El odontólogo indicado no existe o está inactivo.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'motivo.required' => 'Debe indicar el motivo de la cancelación.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
        ];
    }
}
