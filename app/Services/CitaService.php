<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Tratamiento;
use Carbon\Carbon;

/**
 * Clase de control "CTR Cita" del análisis.
 *
 * Reúne las reglas de horario que comparten CU10 Programar cita, CU11 Reprogramar
 * o cancelar cita y CU13 Consultar agenda, para que todos usen la misma función.
 */
class CitaService
{
    // Horario de atención del consultorio y tamaño de las franjas de la agenda (CU13).
    public const HORA_APERTURA = '08:00';
    public const HORA_CIERRE = '20:00';
    public const MINUTOS_FRANJA = 30;

    /**
     * RF-67 / RN-08: duración propuesta para la cita = duración estimada del tratamiento.
     * El usuario puede cambiarla en el formulario.
     */
    public function duracionPropuesta(Tratamiento $tratamiento): int
    {
        return $tratamiento->duracion_estimada_minutos;
    }

    /**
     * RF-69 / RN-07: busca una cita programada del mismo día cuya franja se cruce con la
     * nueva. Hay un solo sillón, así que no importa el odontólogo.
     *
     * Hay cruce si: nuevoInicio < finExistente Y nuevoFin > inicioExistente.
     * (Una cita que empieza justo cuando termina otra no se cruza.)
     * Las citas canceladas, atendidas o no asistidas no ocupan el sillón.
     *
     * Debe llamarse dentro de DB::transaction: bloquea (lockForUpdate) las citas de esa
     * fecha hasta el final de la transacción, para que dos reservas simultáneas no
     * ocupen la misma franja.
     *
     * @param  int|null  $idExcluir  Cita que no se cuenta (la original, al reprogramar).
     * @return Cita|null La cita en conflicto, o null si la franja está libre.
     */
    public function buscarConflicto(string $fecha, string $horaInicio, int $duracion, ?int $idExcluir = null): ?Cita
    {
        // Bloqueo de las citas del día (y de los huecos del índice de esa fecha).
        Cita::where('fecha', $fecha)->lockForUpdate()->get(['id']);

        $inicio = $this->aMinutos($horaInicio);
        $fin = $inicio + $duracion;

        return Cita::with('paciente')
            ->where('fecha', $fecha)
            ->where('estado_cita_id', EstadoCita::idDe(EstadoCita::PROGRAMADA))
            ->when($idExcluir, fn ($consulta) => $consulta->where('id', '!=', $idExcluir))
            ->where('hora_inicio', '<', $this->aHora($fin))
            ->where('hora_fin', '>', $this->aHora($inicio))
            ->orderBy('hora_inicio')
            ->first();
    }

    /**
     * CU13 y CU10 (flujo A2): franjas libres de un día dentro del horario de atención.
     * Un tramo está libre si ninguna cita programada lo ocupa. En el día de hoy solo
     * cuenta desde la próxima franja; los días pasados no tienen franjas libres.
     *
     * @return list<array{inicio: string, fin: string, minutos: int}>
     */
    public function franjasLibres(string $fecha, ?int $idExcluir = null): array
    {
        $dia = Carbon::parse($fecha)->startOfDay();

        if ($dia->lt(today())) {
            return [];
        }

        $cursor = $this->aMinutos(self::HORA_APERTURA);
        $cierre = $this->aMinutos(self::HORA_CIERRE);

        // Hoy: desde la siguiente franja (no se ofrece una hora que ya pasó).
        if ($dia->isToday()) {
            $ahora = now()->hour * 60 + now()->minute;
            $cursor = max($cursor, (int) ceil($ahora / self::MINUTOS_FRANJA) * self::MINUTOS_FRANJA);
        }

        $ocupadas = Cita::where('fecha', $dia->toDateString())
            ->where('estado_cita_id', EstadoCita::idDe(EstadoCita::PROGRAMADA))
            ->when($idExcluir, fn ($consulta) => $consulta->where('id', '!=', $idExcluir))
            ->orderBy('hora_inicio')
            ->get(['hora_inicio', 'hora_fin']);

        $libres = [];

        // Se recorre el día: cada hueco entre el cursor y la siguiente cita es una franja libre.
        foreach ($ocupadas as $cita) {
            $inicioCita = $this->aMinutos($cita->hora_inicio);

            if ($inicioCita > $cursor) {
                $libres[] = $this->franja($cursor, min($inicioCita, $cierre));
            }

            $cursor = max($cursor, $this->aMinutos($cita->hora_fin));
        }

        if ($cursor < $cierre) {
            $libres[] = $this->franja($cursor, $cierre);
        }

        return array_values(array_filter($libres, fn ($f) => $f['minutos'] > 0));
    }

    /**
     * CU10 / CU11 flujo A2: franjas libres del día donde cabe la duración pedida,
     * ordenadas por cercanía a la hora que se quería. Cada una trae la hora sugerida
     * (la más cercana a la pedida dentro de esa franja).
     *
     * @return list<array{inicio: string, fin: string, minutos: int, sugerida: string}>
     */
    public function franjasLibresCercanas(string $fecha, string $horaInicio, int $duracion, ?int $idExcluir = null, int $limite = 3): array
    {
        $pedida = $this->aMinutos($horaInicio);
        $opciones = [];

        foreach ($this->franjasLibres($fecha, $idExcluir) as $franja) {
            if ($franja['minutos'] < $duracion) {
                continue;
            }

            // Primera y última hora de inicio posibles dentro de la franja.
            $primera = $this->aMinutos($franja['inicio']);
            $ultima = $this->aMinutos($franja['fin']) - $duracion;
            $sugerida = min(max($pedida, $primera), $ultima);

            $opciones[] = $franja + [
                'sugerida' => substr($this->aHora($sugerida), 0, 5),
                'distancia' => abs($sugerida - $pedida),
            ];
        }

        usort($opciones, fn ($a, $b) => $a['distancia'] <=> $b['distancia']);

        return array_map(function ($opcion) {
            unset($opcion['distancia']);

            return $opcion;
        }, array_slice($opciones, 0, $limite));
    }

    /**
     * CU10 / CU11 flujo A1: la cita debe empezar y terminar dentro del horario de atención.
     */
    public function dentroDelHorario(string $horaInicio, int $duracion): bool
    {
        $inicio = $this->aMinutos($horaInicio);

        return $inicio >= $this->aMinutos(self::HORA_APERTURA)
            && $inicio + $duracion <= $this->aMinutos(self::HORA_CIERRE);
    }

    /**
     * Convierte "HH:MM" o "HH:MM:SS" en minutos desde las 00:00.
     */
    public function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    /**
     * Convierte minutos desde las 00:00 en "HH:MM:SS" (formato de la columna TIME).
     */
    public function aHora(int $minutos): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutos, 60), $minutos % 60);
    }

    // Arma una franja con sus horas "HH:MM" y su duración en minutos.
    private function franja(int $inicio, int $fin): array
    {
        return [
            'inicio' => substr($this->aHora($inicio), 0, 5),
            'fin' => substr($this->aHora($fin), 0, 5),
            'minutos' => $fin - $inicio,
        ];
    }
}
