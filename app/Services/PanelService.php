<?php

namespace App\Services;

use App\Models\EstadoCita;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard del Panel (no es un caso de uso nuevo: se muestra dentro del Panel existente).
 *
 * indicadores() devuelve las seis cifras de las tarjetas y graficos() los datos de los dos
 * gráficos. Solo leen datos: no se registran en la bitácora.
 *
 * Alcance por rol: el administrador ve todo el consultorio y el odontólogo solo lo suyo,
 * salvo citas_hoy y saldo_por_cobrar, que siempre son del consultorio: el sillón es único
 * (todas las citas del día lo ocupan) y la caja es compartida.
 */
class PanelService
{
    // Meses que muestra el gráfico de ingresos, contando el actual.
    public const MESES_INGRESOS = 6;

    private const MESES = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    /**
     * Las seis cifras del Dashboard, en UNA sola consulta (una subconsulta escalar por cifra).
     *
     * @return array{citas_hoy: int, pacientes_mes: int, tratamientos_en_proceso: int, sesiones_pendientes: int, saldo_por_cobrar: float, avisos_no_leidos: int}
     */
    public function indicadores(User $usuario): array
    {
        $odontologo = $this->odontologoDe($usuario);
        $hoy = today()->toDateString();

        $fila = DB::query()
            // Del consultorio: citas programadas para hoy (el sillón es único).
            ->selectSub(fn (Builder $q) => $this->citas($q, EstadoCita::PROGRAMADA)
                ->where('c.fecha', $hoy)
                ->selectRaw('COUNT(*)'), 'citas_hoy')
            // Pacientes distintos con citas atendidas en el mes en curso (mismo criterio que RF-83).
            ->selectSub(fn (Builder $q) => $this->citas($q, EstadoCita::ATENDIDA)
                ->whereBetween('c.fecha', [today()->startOfMonth()->toDateString(), $hoy])
                ->when($odontologo, fn ($q, $id) => $q->where('c.odontologo_id', $id))
                ->selectRaw('COUNT(DISTINCT c.paciente_id)'), 'pacientes_mes')
            ->selectSub(fn (Builder $q) => $this->enProceso($q, $odontologo)
                ->selectRaw('COUNT(*)'), 'tratamientos_en_proceso')
            // En proceso con menos sesiones realizadas que planificadas (mismo criterio que RF-88).
            ->selectSub(fn (Builder $q) => $this->enProceso($q, $odontologo)
                ->whereRaw("(SELECT COUNT(*) FROM sesiones s WHERE s.tratamiento_aplicado_id = ta.id AND s.estado = 'realizada') < ta.sesiones_planificadas")
                ->selectRaw('COUNT(*)'), 'sesiones_pendientes')
            // De la caja compartida: costo acordado − abonos vigentes, sin los cancelados (PagoService, RF-75).
            ->selectSub(fn (Builder $q) => $q->from('tratamientos_aplicados as ta')
                ->where('ta.estado', '!=', 'cancelado')
                ->selectRaw("COALESCE(SUM(GREATEST(ta.costo_acordado - COALESCE((SELECT SUM(pg.monto) FROM pagos pg WHERE pg.tratamiento_aplicado_id = ta.id AND pg.estado = 'vigente'), 0), 0)), 0)"), 'saldo_por_cobrar')
            // Los avisos siempre son del propio usuario, también para el administrador.
            ->selectSub(fn (Builder $q) => $q->from('avisos as a')
                ->where('a.odontologo_id', $usuario->id)
                ->where('a.leido', false)
                ->selectRaw('COUNT(*)'), 'avisos_no_leidos')
            ->first();

        return [
            'citas_hoy' => (int) $fila->citas_hoy,
            'pacientes_mes' => (int) $fila->pacientes_mes,
            'tratamientos_en_proceso' => (int) $fila->tratamientos_en_proceso,
            'sesiones_pendientes' => (int) $fila->sesiones_pendientes,
            'saldo_por_cobrar' => round((float) $fila->saldo_por_cobrar, 2),
            'avisos_no_leidos' => (int) $fila->avisos_no_leidos,
        ];
    }

    /**
     * Datos de los dos gráficos del Dashboard.
     *
     * ingresos_meses: abonos vigentes cobrados en cada uno de los últimos 6 meses (caja compartida:
     * siempre del consultorio). Los meses sin cobros aparecen con 0.
     * tratamientos_especialidad: tratamientos aplicados no cancelados por especialidad (con el
     * alcance del rol), solo las especialidades que tienen alguno.
     *
     * @return array{ingresos_meses: list<array{mes: string, etiqueta: string, monto: float}>, tratamientos_especialidad: list<array{especialidad: string, cantidad: int}>}
     */
    public function graficos(User $usuario): array
    {
        $odontologo = $this->odontologoDe($usuario);
        $desde = today()->startOfMonth()->subMonths(self::MESES_INGRESOS - 1);

        $cobrado = DB::table('pagos')
            ->where('estado', 'vigente')
            ->whereBetween('fecha_pago', [$desde->toDateString(), today()->toDateString()])
            ->groupByRaw("DATE_FORMAT(fecha_pago, '%Y-%m')")
            ->selectRaw("DATE_FORMAT(fecha_pago, '%Y-%m') as mes, SUM(monto) as total")
            ->pluck('total', 'mes');

        $ingresos = [];
        for ($i = 0; $i < self::MESES_INGRESOS; $i++) {
            $mes = $desde->copy()->addMonths($i);

            $ingresos[] = [
                'mes' => $mes->format('Y-m'),
                'etiqueta' => $this->etiquetaMes($mes),
                'monto' => round((float) ($cobrado[$mes->format('Y-m')] ?? 0), 2),
            ];
        }

        $especialidades = DB::table('tratamientos_aplicados as ta')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->join('especialidades as e', 'e.id', '=', 't.especialidad_id')
            ->where('ta.estado', '!=', 'cancelado')
            ->when($odontologo, fn ($q, $id) => $q->where('ta.odontologo_id', $id))
            ->groupBy('e.id', 'e.nombre')
            ->orderByDesc('cantidad')
            ->orderBy('e.nombre')
            ->selectRaw('e.nombre as especialidad, COUNT(*) as cantidad')
            ->get()
            ->map(fn ($f) => ['especialidad' => $f->especialidad, 'cantidad' => (int) $f->cantidad])
            ->all();

        return [
            'ingresos_meses' => $ingresos,
            'tratamientos_especialidad' => $especialidades,
        ];
    }

    // null = todo el consultorio (administrador); si no, el id del odontólogo.
    private function odontologoDe(User $usuario): ?int
    {
        return $usuario->rol === 'administrador' ? null : $usuario->id;
    }

    // Citas en un estado, buscado por nombre (si el catálogo no está cargado, cuenta 0).
    private function citas(Builder $q, string $estado): Builder
    {
        return $q->from('citas as c')
            ->join('estados_cita as ec', 'ec.id', '=', 'c.estado_cita_id')
            ->where('ec.nombre', $estado);
    }

    // Tratamientos aplicados en proceso, con el alcance del rol.
    private function enProceso(Builder $q, ?int $odontologo): Builder
    {
        return $q->from('tratamientos_aplicados as ta')
            ->where('ta.estado', 'en proceso')
            ->when($odontologo, fn ($q, $id) => $q->where('ta.odontologo_id', $id));
    }

    // «Nov 2026»: sin depender del locale de la aplicación.
    private function etiquetaMes(Carbon $mes): string
    {
        return self::MESES[$mes->month - 1].' '.$mes->year;
    }
}
