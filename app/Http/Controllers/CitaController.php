<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use App\Services\BitacoraService;
use App\Services\CitaService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CU10 Programar cita. Para los dos roles (el administrador también es odontólogo).
 * Las reglas de horario están en CitaService (CTR Cita).
 * Nunca se borran citas (RNF-06): se cambia su estado.
 */
class CitaController extends Controller
{
    public function __construct(private CitaService $citas)
    {
    }

    // CU10 pasos 1 y 2: MOSTRAR el formulario de programar cita.
    // Admite ?buscar= (CU09, solo pacientes activos) y ?fecha=&hora_inicio= (desde la agenda).
    public function create(Request $request): View
    {
        $request->validate([
            'buscar' => ['nullable', 'string', 'max:100'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'hora_inicio' => ['nullable', 'date_format:H:i'],
        ], [
            'buscar.max' => 'El texto de búsqueda no puede superar los 100 caracteres.',
            'fecha.date_format' => 'La fecha indicada no es válida.',
            'hora_inicio.date_format' => 'La hora indicada no es válida.',
        ]);

        $buscar = $request->input('buscar');

        // Misma consulta de CU09 (Paciente::buscar), solo con pacientes activos.
        $pacientes = Paciente::buscar($buscar)
            ->where('estado', 'activo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit(50)
            ->get();

        // Si el formulario vuelve con errores, el paciente elegido sigue en la lista.
        $elegido = $request->old('paciente_id');
        if ($elegido && ! $pacientes->contains('id', (int) $elegido)) {
            $paciente = Paciente::where('estado', 'activo')->find($elegido);
            if ($paciente) {
                $pacientes->prepend($paciente);
            }
        }

        $odontologos = User::where('estado', 'activo')->orderBy('apellidos')->orderBy('nombres')->get();

        // RF-67: solo tratamientos disponibles del catálogo, con su duración estimada.
        $tratamientos = Tratamiento::with('especialidad')
            ->where('estado', 'disponible')
            ->orderBy('nombre')
            ->get();

        return view('citas.create', [
            'pacientes' => $pacientes,
            'odontologos' => $odontologos,
            'tratamientos' => $tratamientos,
            'buscar' => $buscar,
            'fecha' => $request->input('fecha', today()->toDateString()),
            'horaInicio' => $request->input('hora_inicio', ''),
            'apertura' => CitaService::HORA_APERTURA,
            'cierre' => CitaService::HORA_CIERRE,
        ]);
    }

    // CU10 pasos 3 a 7: GUARDAR la cita si el sillón está libre (RF-66 a RF-69).
    public function store(Request $request): RedirectResponse
    {
        // Flujo A1: datos obligatorios, paciente y odontólogo activos, tratamiento disponible.
        $datos = $request->validate($this->reglasProgramar(), $this->mensajes());
        $duracion = (int) $datos['duracion_minutos'];

        // Flujo A1: no en el pasado y dentro del horario de atención.
        if ($error = $this->validarHorario($datos['fecha'], $datos['hora_inicio'], $duracion)) {
            return redirect()->back()->withInput()->withErrors(['hora_inicio' => $error]);
        }

        $tratamiento = Tratamiento::findOrFail($datos['tratamiento_id']);

        // RF-69: verificación e insert en la misma transacción, con las citas del día
        // bloqueadas. Si dos reservas chocan a la vez, MySQL aborta una y se reintenta (3).
        $resultado = DB::transaction(function () use ($datos, $duracion, $tratamiento, $request) {
            $conflicto = $this->citas->buscarConflicto($datos['fecha'], $datos['hora_inicio'], $duracion);

            if ($conflicto) {
                return ['conflicto' => $conflicto];
            }

            // hora_fin no se envía: la calcula la base (RN-09).
            $cita = Cita::create([
                'paciente_id' => $datos['paciente_id'],
                'odontologo_id' => $datos['odontologo_id'],
                // Ciclo 2: la relación con el tratamiento aplicado llega en el Ciclo 4.
                'tratamiento_aplicado_id' => null,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'duracion_minutos' => $duracion,
                // Sin motivo escrito, queda el nombre del tratamiento elegido.
                'motivo' => $datos['motivo'] ?? $tratamiento->nombre,
                'estado_cita_id' => EstadoCita::idDe(EstadoCita::PROGRAMADA),
                'fecha_registro' => today(),
            ]);

            BitacoraService::registrar(BitacoraService::PROGRAMAR_CITA, $request->user()->id, 'citas', $cita->id);

            return ['cita' => $cita];
        }, 3);

        // Flujo A2: franja ocupada, no se guardó nada.
        if (isset($resultado['conflicto'])) {
            return $this->rechazarPorConflicto($resultado['conflicto'], $datos['fecha'], $datos['hora_inicio'], $duracion);
        }

        $cita = $resultado['cita']->refresh();

        return redirect()->route('agenda', ['fecha' => $datos['fecha']])
            ->with('exito', "Cita programada para el {$cita->fecha->format('d/m/Y')} de {$cita->horaInicioCorta()} a {$cita->horaFinCorta()}.");
    }

    // MOSTRAR la ficha de UNA cita; desde aquí se reprograma, cancela o cambia el estado.
    public function show($id): View
    {
        $cita = Cita::with(['paciente', 'odontologo', 'estado'])->findOrFail($id);

        return view('citas.show', compact('cita'));
    }

    /**
     * Flujo A1 de CU10 y CU11: la cita no puede empezar antes del momento actual y debe
     * caber en el horario de atención. Devuelve el mensaje de error, o null si es válida.
     */
    private function validarHorario(string $fecha, string $horaInicio, int $duracion): ?string
    {
        if (Carbon::parse("{$fecha} {$horaInicio}")->lt(now())) {
            return 'La fecha y hora de la cita no pueden ser anteriores al momento actual.';
        }

        if (! $this->citas->dentroDelHorario($horaInicio, $duracion)) {
            return 'La cita debe empezar y terminar dentro del horario de atención ('
                .CitaService::HORA_APERTURA.' a '.CitaService::HORA_CIERRE.').';
        }

        return null;
    }

    /**
     * Flujo A2 de CU10 y CU11 (RF-69): vuelve al formulario con sus datos, la cita en
     * conflicto y las franjas libres más cercanas de ese día.
     */
    private function rechazarPorConflicto(Cita $conflicto, string $fecha, string $horaInicio, int $duracion, ?int $idExcluir = null): RedirectResponse
    {
        $franjas = $this->citas->franjasLibresCercanas($fecha, $horaInicio, $duracion, $idExcluir);

        $mensaje = "El horario se cruza con la cita de {$conflicto->paciente->nombreCompleto()}"
            ." de {$conflicto->horaInicioCorta()} a {$conflicto->horaFinCorta()} (hay un solo sillón).";

        if ($franjas === []) {
            $mensaje .= ' Ese día no quedan franjas libres para esa duración.';
        }

        return redirect()->back()
            ->withInput()
            ->withErrors(['conflicto' => $mensaje])
            ->with('franjas_libres', $franjas);
    }

    // Reglas de CU10 (flujo A1).
    private function reglasProgramar(): array
    {
        return [
            'paciente_id' => ['required', 'integer', Rule::exists('pacientes', 'id')->where('estado', 'activo')],
            'odontologo_id' => ['required', 'integer', Rule::exists('users', 'id')->where('estado', 'activo')],
            'tratamiento_id' => ['required', 'integer', Rule::exists('tratamientos', 'id')->where('estado', 'disponible')],
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            // Límite superior: la columna es SMALLINT sin signo.
            'duracion_minutos' => ['required', 'integer', 'min:1', 'max:600'],
            'motivo' => ['nullable', 'string', 'max:150'],
        ];
    }

    // Mensajes en español de todas las reglas de este controlador.
    private function mensajes(): array
    {
        return [
            'paciente_id.required' => 'Debe seleccionar el paciente.',
            'paciente_id.integer' => 'El paciente indicado no es válido.',
            'paciente_id.exists' => 'El paciente no existe o está inhabilitado.',
            'odontologo_id.required' => 'Debe seleccionar el odontólogo responsable.',
            'odontologo_id.integer' => 'El odontólogo indicado no es válido.',
            'odontologo_id.exists' => 'El odontólogo no existe o su cuenta está inactiva.',
            'tratamiento_id.required' => 'Debe seleccionar el tratamiento.',
            'tratamiento_id.integer' => 'El tratamiento indicado no es válido.',
            'tratamiento_id.exists' => 'El tratamiento no existe o no está disponible.',
            'fecha.required' => 'El campo Fecha es obligatorio.',
            'fecha.date_format' => 'La fecha no es válida.',
            'fecha.after_or_equal' => 'La fecha de la cita no puede ser anterior a hoy.',
            'hora_inicio.required' => 'El campo Hora de inicio es obligatorio.',
            'hora_inicio.date_format' => 'La hora de inicio debe tener el formato HH:MM.',
            'duracion_minutos.required' => 'El campo Duración es obligatorio.',
            'duracion_minutos.integer' => 'La duración debe ser un número entero de minutos.',
            'duracion_minutos.min' => 'La duración debe ser mayor a 0 minutos.',
            'duracion_minutos.max' => 'La duración no puede superar los 600 minutos.',
            'motivo.string' => 'El motivo debe ser un texto.',
            'motivo.max' => 'El motivo no puede superar los 150 caracteres.',
        ];
    }
}
