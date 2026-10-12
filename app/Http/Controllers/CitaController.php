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
 * CU10 Programar cita y CU11 Reprogramar o cancelar cita.
 * Para los dos roles (el administrador también es odontólogo).
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

        return view('citas.show', [
            'cita' => $cita,
            'apertura' => CitaService::HORA_APERTURA,
            'cierre' => CitaService::HORA_CIERRE,
        ]);
    }

    // CU11 REPROGRAMAR (RF-70): crea una cita nueva con el nuevo horario y deja la
    // original cancelada, así se conserva su registro. El id viene en el formulario.
    public function reprogramar(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'fecha' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'motivo_cambio' => ['required', 'string', 'max:255'],
        ], $this->mensajes());

        // Se conserva la misma duración de la cita original.
        $duracion = Cita::findOrFail($datos['id'])->duracion_minutos;

        if ($error = $this->validarHorario($datos['fecha'], $datos['hora_inicio'], $duracion)) {
            return redirect()->back()->withInput()->withErrors(['hora_inicio' => $error]);
        }

        $resultado = DB::transaction(function () use ($datos, $duracion, $request) {
            $original = $this->citaProgramadaBloqueada($datos['id']);

            if (! $original) {
                return ['error' => 'La cita ya tiene un estado final.'];
            }

            // RF-69: la cita original no cuenta como conflicto (se puede mover dentro de su propia franja).
            $conflicto = $this->citas->buscarConflicto($datos['fecha'], $datos['hora_inicio'], $duracion, $original->id);

            if ($conflicto) {
                return ['conflicto' => $conflicto];
            }

            // 1. Nueva cita programada con el mismo paciente, odontólogo y duración.
            $nueva = Cita::create([
                'paciente_id' => $original->paciente_id,
                'odontologo_id' => $original->odontologo_id,
                'tratamiento_aplicado_id' => $original->tratamiento_aplicado_id,
                'fecha' => $datos['fecha'],
                'hora_inicio' => $datos['hora_inicio'],
                'duracion_minutos' => $duracion,
                'motivo' => $original->motivo,
                'estado_cita_id' => EstadoCita::idDe(EstadoCita::PROGRAMADA),
                'fecha_registro' => today(),
            ]);

            // 2. La original queda cancelada, con la referencia a la nueva y el motivo.
            $original->estado_cita_id = EstadoCita::idDe(EstadoCita::CANCELADA);
            $original->agregarObservacion("Reprogramada a la cita N.º {$nueva->id}: {$datos['motivo_cambio']}");
            $original->save();

            // 3. Bitácora (sobre la cita original, que es la que se reprogramó).
            BitacoraService::registrar(BitacoraService::REPROGRAMAR_CITA, $request->user()->id, 'citas', $original->id);

            return ['cita' => $nueva];
        }, 3);

        if (isset($resultado['error'])) {
            return $this->error($resultado['error']);
        }

        if (isset($resultado['conflicto'])) {
            return $this->rechazarPorConflicto($resultado['conflicto'], $datos['fecha'], $datos['hora_inicio'], $duracion, (int) $datos['id']);
        }

        $nueva = $resultado['cita']->refresh();

        return redirect()->route('citas.mostrar', $nueva->id)
            ->with('exito', "Cita reprogramada para el {$nueva->fecha->format('d/m/Y')} de {$nueva->horaInicioCorta()} a {$nueva->horaFinCorta()}. La cita N.º {$datos['id']} quedó cancelada.");
    }

    // CU11 CANCELAR (RF-70): la cita pasa a cancelada con su motivo; la franja queda libre
    // porque la verificación de RF-69 solo cuenta citas programadas. No se borra (RNF-06).
    public function cancelar(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglaId() + [
            'motivo' => ['required', 'string', 'max:255'],
        ], [
            // Van primero: reemplazan los mensajes de "motivo" de CU10.
            'motivo.required' => 'Debe indicar el motivo de la cancelación.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
        ] + $this->mensajes());

        $cancelada = DB::transaction(function () use ($datos, $request) {
            $cita = $this->citaProgramadaBloqueada($datos['id']);

            if (! $cita) {
                return false;
            }

            $cita->estado_cita_id = EstadoCita::idDe(EstadoCita::CANCELADA);
            $cita->agregarObservacion("Cancelada: {$datos['motivo']}");
            $cita->save();

            BitacoraService::registrar(BitacoraService::CANCELAR_CITA, $request->user()->id, 'citas', $cita->id);

            return true;
        });

        if (! $cancelada) {
            return $this->error('La cita ya tiene un estado final.');
        }

        return redirect()->back()->with('exito', 'Cita cancelada. Su horario quedó libre en la agenda.');
    }

    /**
     * CU11 y CU12: lee la cita bloqueándola hasta el final de la transacción (otra
     * petición pudo cambiar su estado). Devuelve null si ya no está programada.
     */
    private function citaProgramadaBloqueada(int|string $id): ?Cita
    {
        $cita = Cita::with('estado')->lockForUpdate()->findOrFail($id);

        return $cita->estaProgramada() ? $cita : null;
    }

    // Vuelve atrás con un error de negocio (clave "operacion").
    private function error(string $mensaje): RedirectResponse
    {
        return redirect()->back()->withErrors(['operacion' => $mensaje]);
    }

    // Regla del id que llega oculto en el formulario.
    private function reglaId(): array
    {
        return ['id' => ['required', 'integer', 'exists:citas,id']];
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
            'id.required' => 'No se indicó la cita.',
            'id.integer' => 'La cita indicada no es válida.',
            'id.exists' => 'La cita indicada no existe.',
            'motivo_cambio.required' => 'Debe indicar el motivo del cambio.',
            'motivo_cambio.string' => 'El motivo del cambio debe ser un texto.',
            'motivo_cambio.max' => 'El motivo del cambio no puede superar los 255 caracteres.',
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
