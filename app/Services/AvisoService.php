<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\Cita;
use App\Models\Control;
use App\Models\EstadoCita;
use App\Models\TratamientoAplicado;
use App\Models\TratamientoTipoEstudio;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Clase de control "CTR AvisoService" del análisis.
 *
 * CU40 Generar avisos internos (RF-81): arma los cinco tipos de aviso para el odontólogo
 * responsable de cada caso. Lo usan el comando avisos:generar (actor Temporizador) y su
 * disparo manual desde la lista de avisos.
 *
 * Sin duplicados: si ya hay un aviso NO LEÍDO del mismo tipo y la misma referencia, no se
 * crea otro. El comando corre todos los días; un aviso leído sí se vuelve a generar si el
 * caso sigue abierto.
 */
class AvisoService
{
    /**
     * Horas hacia adelante en las que una cita programada cuenta como próxima.
     */
    public const HORAS_CITA_PROXIMA = 48;

    /**
     * Claves "tipo|referencia_id" de los avisos no leídos, para no duplicar.
     *
     * @var array<string, true>
     */
    private array $existentes = [];

    public function __construct(
        private PagoService $pagos,
        private EstudioDiagnosticoService $estudios
    ) {
    }

    /**
     * Genera los avisos de los cinco tipos y devuelve cuántos creó de cada uno.
     *
     * @return array<string, int> tipo => cantidad creada (los cinco tipos, en orden)
     */
    public function generar(): array
    {
        return DB::transaction(function () {
            $this->existentes = Aviso::where('leido', false)
                ->get(['tipo', 'referencia_id'])
                ->mapWithKeys(fn ($a) => [$a->tipo.'|'.$a->referencia_id => true])
                ->all();

            return [
                Aviso::CITA_PROXIMA => $this->citasProximas(),
                Aviso::CONTROL_PENDIENTE => $this->controlesPendientes(),
                Aviso::SALDO_PENDIENTE => $this->saldosPendientes(),
                Aviso::ESTUDIO_PENDIENTE => $this->estudiosPendientes(),
                Aviso::SESION_SIN_CITA => $this->sesionesSinCita(),
            ];
        });
    }

    // 1. Citas programadas que empiezan dentro de las próximas 48 horas. Destinatario: el odontólogo de la cita.
    private function citasProximas(): int
    {
        $programada = EstadoCita::where('nombre', EstadoCita::PROGRAMADA)->value('id');

        if (! $programada) {
            return 0;
        }

        $desde = now();
        $hasta = now()->addHours(self::HORAS_CITA_PROXIMA);

        $citas = Cita::with('paciente')
            ->where('estado_cita_id', $programada)
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->get()
            ->filter(function (Cita $cita) use ($desde, $hasta) {
                $inicio = Carbon::parse($cita->fecha->toDateString().' '.$cita->hora_inicio);

                return $inicio->between($desde, $hasta);
            });

        $creados = 0;
        foreach ($citas as $cita) {
            $creados += $this->crear(Aviso::CITA_PROXIMA, $cita->id, $cita->odontologo_id,
                "Cita con {$cita->paciente->nombreCompleto()} el {$cita->fecha->format('d/m/Y')} a las {$cita->horaInicioCorta()}.");
        }

        return $creados;
    }

    // 2. Controles pendientes o vencidos con fecha prevista de hoy o anterior. Destinatario: el odontólogo del tratamiento.
    private function controlesPendientes(): int
    {
        $controles = Control::with('tratamientoAplicado.plan.paciente', 'tratamientoAplicado.tratamiento')
            ->whereIn('estado', ['pendiente', 'vencido'])
            ->whereDate('fecha_prevista', '<=', today())
            ->get();

        $creados = 0;
        foreach ($controles as $control) {
            $ta = $control->tratamientoAplicado;
            $situacion = $control->estado === 'vencido' ? 'vencido desde el' : 'previsto para el';
            $motivo = $control->motivo ? " ({$control->motivo})" : '';

            $creados += $this->crear(Aviso::CONTROL_PENDIENTE, $control->id, $ta->odontologo_id,
                "Control de {$ta->plan->paciente->nombreCompleto()} por {$ta->tratamiento->nombre}{$motivo}, {$situacion} {$control->fecha_prevista->format('d/m/Y')}.");
        }

        return $creados;
    }

    // 3. Tratamientos no cancelados con saldo mayor a cero (PagoService, el mismo cálculo de CU36).
    private function saldosPendientes(): int
    {
        $tratamientos = TratamientoAplicado::with('plan.paciente', 'tratamiento')
            ->where('estado', '!=', 'cancelado')
            ->get();

        $creados = 0;
        foreach ($tratamientos as $ta) {
            $saldo = $this->pagos->saldoPendiente($ta);

            if ($saldo <= 0) {
                continue;
            }

            $creados += $this->crear(Aviso::SALDO_PENDIENTE, $ta->id, $ta->odontologo_id,
                "{$ta->plan->paciente->nombreCompleto()} tiene un saldo pendiente de Bs ".number_format($saldo, 2, ',', '.')." por {$ta->tratamiento->nombre}.");
        }

        return $creados;
    }

    // 4. Tratamientos abiertos (ni cancelados ni concluidos) a los que les faltan estudios obligatorios.
    private function estudiosPendientes(): int
    {
        $obligatorios = TratamientoTipoEstudio::with('tipoEstudio')
            ->where('obligatorio', true)
            ->get()
            ->groupBy('tratamiento_id');

        if ($obligatorios->isEmpty()) {
            return 0;
        }

        $tratamientos = TratamientoAplicado::with('plan.paciente', 'tratamiento')
            ->whereNotIn('estado', ['cancelado', 'concluido'])
            ->whereIn('tratamiento_id', $obligatorios->keys())
            ->get();

        $creados = 0;
        foreach ($tratamientos as $ta) {
            // Mismo criterio de "estudio cargado" que CU28 y RF-59.
            $cargados = $this->estudios->tiposCargados($ta->plan->paciente_id);
            $faltantes = $obligatorios[$ta->tratamiento_id]
                ->reject(fn ($r) => $cargados->contains($r->tipo_estudio_id))
                ->map(fn ($r) => $r->tipoEstudio->nombre);

            if ($faltantes->isEmpty()) {
                continue;
            }

            $creados += $this->crear(Aviso::ESTUDIO_PENDIENTE, $ta->id, $ta->odontologo_id,
                "Faltan estudios obligatorios de {$ta->plan->paciente->nombreCompleto()} para {$ta->tratamiento->nombre}: {$faltantes->implode(', ')}.");
        }

        return $creados;
    }

    // 5. Tratamientos en proceso con sesiones por hacer y sin ninguna cita programada a futuro.
    private function sesionesSinCita(): int
    {
        $programada = EstadoCita::where('nombre', EstadoCita::PROGRAMADA)->value('id');

        $tratamientos = TratamientoAplicado::with('plan.paciente', 'tratamiento')
            ->withCount(['sesiones as realizadas' => fn ($q) => $q->where('estado', 'realizada')])
            ->where('estado', 'en proceso')
            ->get()
            ->filter(fn ($ta) => $ta->realizadas < $ta->sesiones_planificadas);

        $creados = 0;
        foreach ($tratamientos as $ta) {
            // Cuenta una cita futura del tratamiento, o del paciente si la cita aún no indica tratamiento (RF-66).
            $tieneCita = $programada && Cita::where('estado_cita_id', $programada)
                ->whereDate('fecha', '>=', today())
                ->where(fn ($q) => $q->where('tratamiento_aplicado_id', $ta->id)
                    ->orWhere(fn ($s) => $s->whereNull('tratamiento_aplicado_id')->where('paciente_id', $ta->plan->paciente_id)))
                ->exists();

            if ($tieneCita) {
                continue;
            }

            $faltan = $ta->sesiones_planificadas - $ta->realizadas;
            $creados += $this->crear(Aviso::SESION_SIN_CITA, $ta->id, $ta->odontologo_id,
                "{$ta->plan->paciente->nombreCompleto()} tiene {$faltan} ".($faltan === 1 ? 'sesión pendiente' : 'sesiones pendientes')." de {$ta->tratamiento->nombre} sin cita programada.");
        }

        return $creados;
    }

    // Crea el aviso si no hay otro no leído igual. Devuelve 1 si lo creó, 0 si ya existía.
    private function crear(string $tipo, int $referenciaId, int $odontologoId, string $mensaje): int
    {
        $clave = $tipo.'|'.$referenciaId;

        if (isset($this->existentes[$clave])) {
            return 0;
        }

        Aviso::create([
            'odontologo_id' => $odontologoId,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'mensaje' => $mensaje,
            'fecha_generacion' => now(),
            'leido' => false,
        ]);

        $this->existentes[$clave] = true;

        return 1;
    }
}
