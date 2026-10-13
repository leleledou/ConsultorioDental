<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\User;
use App\Services\CitaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Clase de control "CTR Agenda". CU13 Consultar agenda (RF-72). Para los dos roles.
 *
 * El horario de la vista diaria (apertura, cierre y tamaño de franja) está en las
 * constantes de CitaService, porque CU10 y CU11 usan el mismo horario para validar
 * y para calcular las franjas libres.
 */
class AgendaController extends Controller
{
    // Días de la vista semanal: de lunes (0) a sábado (5).
    private const DIAS_SEMANA = 6;

    public function __construct(private CitaService $citas)
    {
    }

    // MOSTRAR la agenda del día o de la semana, con filtros por odontólogo y estado.
    public function index(Request $request): View
    {
        $request->validate([
            'vista' => ['nullable', Rule::in(['dia', 'semana'])],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'odontologo_id' => ['nullable', 'integer', 'exists:users,id'],
            'estado_cita_id' => ['nullable', 'integer', 'exists:estados_cita,id'],
        ], [
            'vista.in' => 'La vista debe ser «dia» o «semana».',
            'fecha.date_format' => 'La fecha indicada no es válida.',
            'odontologo_id.integer' => 'El odontólogo indicado no es válido.',
            'odontologo_id.exists' => 'El odontólogo indicado no existe.',
            'estado_cita_id.integer' => 'El estado indicado no es válido.',
            'estado_cita_id.exists' => 'El estado indicado no existe.',
        ]);

        $vista = $request->input('vista', 'dia');
        $fecha = Carbon::parse($request->input('fecha', today()->toDateString()))->startOfDay();
        $filtros = array_filter($request->only(['odontologo_id', 'estado_cita_id']));

        $datos = [
            'vista' => $vista,
            'fecha' => $fecha,
            'filtros' => $filtros,
            'odontologos' => User::orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']),
            'estados' => EstadoCita::orderBy('id')->get(),
        ];

        return view('agenda', $datos + ($vista === 'semana'
            ? $this->vistaSemanal($fecha, $filtros)
            : $this->vistaDiaria($fecha, $filtros)));
    }

    /**
     * Vista diaria: franjas de MINUTOS_FRANJA minutos entre la apertura y el cierre.
     * Cada cita ocupa las filas de su duración; las franjas sin cita programada se
     * muestran como disponibles (con enlace a CU10) si todavía no pasaron.
     */
    private function vistaDiaria(Carbon $fecha, array $filtros): array
    {
        $apertura = $this->citas->aMinutos(CitaService::HORA_APERTURA);
        $cierre = $this->citas->aMinutos(CitaService::HORA_CIERRE);
        $paso = CitaService::MINUTOS_FRANJA;
        $totalFilas = intdiv($cierre - $apertura, $paso);

        $citas = $this->consultarCitas($fecha, $fecha, $filtros);

        // Ubicación de cada cita en la grilla: fila de inicio y cantidad de filas.
        $bloques = $citas->map(function (Cita $cita) use ($apertura, $paso, $totalFilas) {
            $inicio = $this->citas->aMinutos($cita->hora_inicio);
            $fin = $this->citas->aMinutos($cita->hora_fin);
            $fila = min(max(intdiv($inicio - $apertura, $paso) + 1, 1), $totalFilas);
            $filaFin = min(max((int) ceil(($fin - $apertura) / $paso) + 1, $fila + 1), $totalFilas + 1);

            return ['cita' => $cita, 'fila' => $fila, 'filas' => $filaFin - $fila];
        });

        // Las canceladas van al final: no ocupan el sillón y se dibujan al costado.
        $cancelada = EstadoCita::CANCELADA;
        $bloques = $bloques->sortBy(fn ($b) => $b['cita']->estado->nombre === $cancelada ? 1 : 0)->values();

        // Filas cubiertas por las citas que se muestran (salvo canceladas).
        $cubiertas = [];
        foreach ($bloques as $b) {
            if ($b['cita']->estado->nombre !== $cancelada) {
                foreach (range($b['fila'], $b['fila'] + $b['filas'] - 1) as $f) {
                    $cubiertas[$f] = true;
                }
            }
        }

        // Estado de cada franja según el sillón (sin filtros: hay un solo sillón).
        $libres = $this->citas->franjasLibres($fecha->toDateString());
        $programadas = Cita::where('fecha', $fecha->toDateString())
            ->where('estado_cita_id', EstadoCita::idDe(EstadoCita::PROGRAMADA))
            ->get(['hora_inicio', 'hora_fin']);

        $franjas = [];
        for ($fila = 1; $fila <= $totalFilas; $fila++) {
            $desde = $apertura + ($fila - 1) * $paso;
            $hasta = $desde + $paso;

            $libre = collect($libres)->contains(fn ($l) => $this->citas->aMinutos($l['inicio']) <= $desde
                && $this->citas->aMinutos($l['fin']) >= $hasta);
            $ocupada = $programadas->contains(fn ($c) => $this->citas->aMinutos($c->hora_inicio) < $hasta
                && $this->citas->aMinutos($c->hora_fin) > $desde);

            $franjas[] = [
                'fila' => $fila,
                'hora' => substr($this->citas->aHora($desde), 0, 5),
                // libre: se puede programar; ocupada: la ocupa una cita oculta por los filtros;
                // null: ya pasó, o la cita que la ocupa ya se ve en la grilla.
                'estado' => isset($cubiertas[$fila]) ? null : ($libre ? 'libre' : ($ocupada ? 'ocupada' : null)),
            ];
        }

        return compact('citas', 'bloques', 'franjas', 'totalFilas');
    }

    /**
     * Vista semanal: de lunes a sábado de la semana de la fecha, una columna por día
     * con sus citas en orden de hora.
     */
    private function vistaSemanal(Carbon $fecha, array $filtros): array
    {
        $lunes = $fecha->copy()->startOfWeek(Carbon::MONDAY);
        $sabado = $lunes->copy()->addDays(self::DIAS_SEMANA - 1);

        $citas = $this->consultarCitas($lunes, $sabado, $filtros);
        $porDia = $citas->groupBy(fn (Cita $cita) => $cita->fecha->toDateString());

        $dias = [];
        for ($i = 0; $i < self::DIAS_SEMANA; $i++) {
            $dia = $lunes->copy()->addDays($i);
            $dias[] = ['fecha' => $dia, 'citas' => $porDia->get($dia->toDateString(), collect())];
        }

        return compact('citas', 'dias', 'lunes', 'sabado');
    }

    // Citas del rango de fechas, con los filtros opcionales, en orden de fecha y hora.
    private function consultarCitas(Carbon $desde, Carbon $hasta, array $filtros): Collection
    {
        return Cita::with(['paciente', 'odontologo', 'estado'])
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->when($filtros['odontologo_id'] ?? null, fn ($c, $id) => $c->where('odontologo_id', $id))
            ->when($filtros['estado_cita_id'] ?? null, fn ($c, $id) => $c->where('estado_cita_id', $id))
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
    }
}
