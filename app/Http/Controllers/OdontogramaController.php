<?php

namespace App\Http\Controllers;

use App\Models\EstadoPieza;
use App\Models\HistoriaClinica;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\PiezaDental;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;
use App\Services\OdontogramaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU25 Registrar odontograma (RF-15 carga automática de piezas, RF-20 vínculo con el
 * tratamiento aplicado, RF-23 odontograma gráfico) y CU26 Comparar odontogramas.
 * Para los dos roles (el administrador también es odontólogo).
 * Las piezas del odontograma se gestionan en DetalleOdontogramaController.
 */
class OdontogramaController extends Controller
{
    public function __construct(private OdontogramaService $odontogramas)
    {
    }

    // MOSTRAR los odontogramas de UN paciente, del más reciente al más antiguo.
    public function index($paciente): View
    {
        $paciente = Paciente::findOrFail($paciente);
        $historia = HistoriaClinica::where('paciente_id', $paciente->id)->firstOrFail();

        $odontogramas = Odontograma::with('odontologo')
            ->withCount('detalles')
            ->where('historia_clinica_id', $historia->id)
            ->orderByDesc('fecha_registro')
            ->get();

        return view('odontogramas.index', compact('paciente', 'historia', 'odontogramas'));
    }

    // MOSTRAR el formulario de registrar un odontograma. El paciente llega por query string.
    public function create(Request $request): View
    {
        $request->validate(
            ['paciente' => ['required', 'integer', 'exists:pacientes,id']],
            $this->mensajes()
        );

        $paciente = Paciente::findOrFail($request->input('paciente'));
        $historia = HistoriaClinica::where('paciente_id', $paciente->id)->firstOrFail();

        // Si ya hay un odontograma inicial, se propone uno de evolución.
        $tieneInicial = $this->tieneInicial($historia->id);
        $tipoPropuesto = $tieneInicial ? 'evolución' : 'inicial';

        // Dentición propuesta según la edad: temporal hasta los 5 años, mixta de 6 a 12.
        $edad = $paciente->edad();
        $denticionPropuesta = match (true) {
            $edad < 6 => 'temporal',
            $edad <= 12 => 'mixta',
            default => 'permanente',
        };

        return view('odontogramas.create', compact('paciente', 'historia', 'tieneInicial', 'tipoPropuesto', 'denticionPropuesta'));
    }

    // CREAR un odontograma y cargar sus piezas en estado SANO (RF-15), en una sola transacción.
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $resultado = DB::transaction(function () use ($datos, $request) {
            // Bloquea la historia para que dos registros simultáneos no salteen las reglas.
            $historia = HistoriaClinica::lockForUpdate()->findOrFail($datos['historia_clinica_id']);

            if ($datos['tipo'] === 'inicial' && $this->tieneInicial($historia->id)) {
                return 'El paciente ya tiene un odontograma inicial. Registre uno de evolución.';
            }

            $mismaFecha = Odontograma::where('historia_clinica_id', $historia->id)
                ->whereDate('fecha_registro', $datos['fecha_registro'])
                ->exists();

            if ($mismaFecha) {
                return 'Ya existe un odontograma registrado para esta fecha.';
            }

            // El odontólogo es siempre el usuario de la sesión: no se elige en el formulario.
            $odontograma = Odontograma::create($datos + [
                'odontologo_id' => $request->user()->id,
                'estado' => 'activo',
            ]);

            $this->odontogramas->cargarPiezas($odontograma);

            BitacoraService::registrar(BitacoraService::CREAR_ODONTOGRAMA, $request->user()->id, 'odontogramas', $odontograma->id);

            return $odontograma;
        });

        if (is_string($resultado)) {
            return redirect()->back()->withInput()->withErrors(['operacion' => $resultado]);
        }

        return redirect()->route('odontogramas.mostrar', $resultado->id)
            ->with('exito', 'Odontograma registrado correctamente.');
    }

    // MOSTRAR UN odontograma con sus piezas (RF-23), el catálogo de estados y los tratamientos del paciente.
    public function show($id): View
    {
        $odontograma = Odontograma::with('historiaClinica.paciente', 'odontologo', 'detalles.pieza', 'detalles.estadoPieza', 'detalles.tratamientoAplicado.tratamiento')
            ->findOrFail($id);

        $estados = EstadoPieza::where('estado', 'activo')->orderBy('id')->get();

        // RF-20: tratamientos aplicados del paciente, para vincularlos (opcional) a una pieza.
        $tratamientos = TratamientoAplicado::with('tratamiento')
            ->whereHas('plan', fn ($q) => $q->where('paciente_id', $odontograma->historiaClinica->paciente_id))
            ->orderByDesc('fecha_inicio')
            ->get();

        // Piezas que todavía no están en el odontograma, para agregarlas.
        $piezasDisponibles = PiezaDental::whereNotIn('id', $odontograma->detalles->pluck('pieza_dental_id'))
            ->orderBy('id')
            ->get();

        return view('odontogramas.show', compact('odontograma', 'estados', 'tratamientos', 'piezasDisponibles'));
    }

    // CU26: MOSTRAR los odontogramas del paciente para elegir dos (GET a y b) y compararlos.
    public function comparar(Request $request, $paciente): View|RedirectResponse
    {
        $paciente = Paciente::findOrFail($paciente);
        $historia = HistoriaClinica::where('paciente_id', $paciente->id)->firstOrFail();

        $odontogramas = Odontograma::where('historia_clinica_id', $historia->id)
            ->orderByDesc('fecha_registro')
            ->get();

        // Flujo A2: sin al menos dos odontogramas no hay comparación posible.
        if ($odontogramas->count() < 2) {
            $aviso = 'El paciente tiene un solo odontograma registrado; no hay con qué compararlo.';

            return view('odontogramas.comparar', compact('paciente', 'historia', 'odontogramas', 'aviso'));
        }

        $filas = null;
        $a = null;
        $b = null;

        // Se compara solo si se eligió algo; la primera vez solo se muestran los selectores.
        if ($request->hasAny(['a', 'b'])) {
            $datos = $request->validate([
                'a' => ['nullable', 'integer', Rule::exists('odontogramas', 'id')->where('historia_clinica_id', $historia->id)],
                'b' => ['nullable', 'integer', Rule::exists('odontogramas', 'id')->where('historia_clinica_id', $historia->id)],
            ], $this->mensajes());

            // Flujo A1: hacen falta dos odontogramas distintos.
            if (empty($datos['a']) || empty($datos['b']) || (int) $datos['a'] === (int) $datos['b']) {
                return redirect()->route('odontogramas.comparar', $paciente->id)
                    ->withErrors(['operacion' => 'Seleccione dos odontogramas diferentes para comparar.']);
            }

            $a = $odontogramas->firstWhere('id', (int) $datos['a']);
            $b = $odontogramas->firstWhere('id', (int) $datos['b']);
            $filas = $this->odontogramas->comparar($a->id, $b->id);
        }

        return view('odontogramas.comparar', compact('paciente', 'historia', 'odontogramas', 'filas', 'a', 'b'));
    }

    // Solo cuenta el odontograma inicial activo: uno anulado puede reemplazarse.
    private function tieneInicial(int $historiaId): bool
    {
        return Odontograma::where('historia_clinica_id', $historiaId)
            ->where('tipo', 'inicial')
            ->where('estado', 'activo')
            ->exists();
    }

    // Reglas de los datos del odontograma nuevo. El odontólogo no viene del formulario.
    private function reglas(): array
    {
        return [
            'historia_clinica_id' => ['required', 'integer', 'exists:historias_clinicas,id'],
            'fecha_registro' => ['bail', 'required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tipo' => ['required', Rule::in(['inicial', 'evolución'])],
            'tipo_denticion' => ['required', Rule::in(['permanente', 'temporal', 'mixta'])],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    // Mensajes en español.
    private function mensajes(): array
    {
        return [
            'paciente.required' => 'No se indicó el paciente.',
            'paciente.integer' => 'El paciente indicado no es válido.',
            'paciente.exists' => 'El paciente indicado no existe.',
            'historia_clinica_id.required' => 'No se indicó la historia clínica.',
            'historia_clinica_id.integer' => 'La historia clínica indicada no es válida.',
            'historia_clinica_id.exists' => 'La historia clínica indicada no existe.',
            'fecha_registro.required' => 'El campo Fecha es obligatorio.',
            'fecha_registro.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha_registro.before_or_equal' => 'La fecha del odontograma no puede ser futura.',
            'tipo.required' => 'Debe seleccionar el tipo de odontograma.',
            'tipo.in' => 'El tipo de odontograma debe ser inicial o de evolución.',
            'tipo_denticion.required' => 'Debe seleccionar el tipo de dentición.',
            'tipo_denticion.in' => 'El tipo de dentición debe ser permanente, temporal o mixta.',
            'observaciones.string' => 'Las observaciones deben ser un texto.',
            'a.integer' => 'El odontograma A indicado no es válido.',
            'a.exists' => 'El odontograma A no pertenece al paciente.',
            'b.integer' => 'El odontograma B indicado no es válido.',
            'b.exists' => 'El odontograma B no pertenece al paciente.',
        ];
    }
}
