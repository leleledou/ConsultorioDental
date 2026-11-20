<?php

namespace App\Services;

use App\Models\Cita;
use App\Models\EstadoCita;
use App\Models\Odontograma;
use App\Models\Paciente;
use App\Models\Sesion;
use App\Models\TratamientoAplicado;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Clase de control "CTR ReporteService" del análisis.
 *
 * CU42 a CU44: estatico() ejecuta un reporte predefinido y dinamico() arma uno con los
 * filtros y columnas elegidos. CU45: exportarPdf() y exportarExcel() reciben esos mismos
 * datos, así el archivo coincide con el reporte en pantalla.
 *
 * Los dos métodos devuelven la misma estructura: titulo, nombre_archivo, periodo,
 * columnas (clave => encabezado), numericas, montos (claves de columnas y etiquetas de
 * totales que son dinero), filas y totales (etiqueta => valor).
 *
 * RF-84 (historiaClinica) y RF-85 (comparativoOdontograma) no son reportes tabulares por
 * periodo sino documentos por paciente: tienen sus propios métodos, vistas y PDF.
 */
class ReporteService
{
    // Reportes estáticos de cada familia (tipo => título).
    public const ESTATICOS = [
        'clinico' => [
            'pacientes_atendidos' => 'Pacientes atendidos por periodo',
            'tratamientos_odontologo' => 'Tratamientos realizados por odontólogo y especialidad',
            'sesiones_pendientes' => 'Tratamientos con sesiones pendientes',
            'materiales_utilizados' => 'Materiales utilizados por periodo y tratamiento',
        ],
        'agenda' => [
            'agenda_citas' => 'Agenda de citas del periodo',
            'citas_no_asistidas' => 'Citas no asistidas por periodo',
        ],
        'financiero' => [
            'saldo_pendiente' => 'Tratamientos con saldo pendiente',
            'ingresos' => 'Ingresos por periodo y método de pago',
        ],
    ];

    // Lista blanca de los reportes dinámicos: clave => [encabezado, expresión SQL fija].
    private const COLUMNAS = [
        'clinico' => [
            'paciente' => ['Paciente', "CONCAT(p.nombres, ' ', p.apellidos)"],
            'tratamiento' => ['Tratamiento', 't.nombre'],
            'especialidad' => ['Especialidad', 'e.nombre'],
            'odontologo' => ['Odontólogo', "CONCAT(u.nombres, ' ', u.apellidos)"],
            'diagnostico' => ['Diagnóstico', 'd.nombre'],
            'pieza' => ['Pieza', 'ta.pieza_dental_id'],
            'fecha_inicio' => ['Fecha de inicio', 'ta.fecha_inicio'],
            'fecha_fin' => ['Fecha de fin', 'ta.fecha_fin'],
            'estado' => ['Estado', 'ta.estado'],
            'costo_acordado' => ['Costo acordado', 'ta.costo_acordado'],
            'sesiones_planificadas' => ['Sesiones planificadas', 'ta.sesiones_planificadas'],
            'sesiones_realizadas' => ['Sesiones realizadas', "(SELECT COUNT(*) FROM sesiones s WHERE s.tratamiento_aplicado_id = ta.id AND s.estado = 'realizada')"],
            'ultima_sesion' => ['Última sesión', "(SELECT MAX(s.fecha) FROM sesiones s WHERE s.tratamiento_aplicado_id = ta.id AND s.estado = 'realizada')"],
            'estudios_cargados' => ['Estudios cargados', '(SELECT COUNT(*) FROM estudios_diagnosticos ed WHERE ed.tratamiento_aplicado_id = ta.id AND ed.paciente_id = pl.paciente_id)'],
            'controles_pendientes' => ['Controles pendientes', "(SELECT COUNT(*) FROM controles co WHERE co.tratamiento_aplicado_id = ta.id AND co.estado IN ('pendiente', 'vencido'))"],
        ],
        'agenda' => [
            'fecha' => ['Fecha', 'c.fecha'],
            'hora_inicio' => ['Hora inicio', 'c.hora_inicio'],
            'hora_fin' => ['Hora fin', 'c.hora_fin'],
            'duracion_minutos' => ['Duración', 'c.duracion_minutos'],
            'paciente' => ['Paciente', "CONCAT(p.nombres, ' ', p.apellidos)"],
            'odontologo' => ['Odontólogo', "CONCAT(u.nombres, ' ', u.apellidos)"],
            'motivo' => ['Motivo', 'c.motivo'],
            'estado' => ['Estado', 'ec.nombre'],
        ],
        'financiero' => [
            'comprobante' => ['Comprobante', 'pg.numero_comprobante'],
            'fecha_pago' => ['Fecha de pago', 'pg.fecha_pago'],
            'paciente' => ['Paciente', "CONCAT(p.nombres, ' ', p.apellidos)"],
            'tratamiento' => ['Tratamiento', 't.nombre'],
            'metodo_pago' => ['Método de pago', 'mp.nombre'],
            'odontologo' => ['Odontólogo', "CONCAT(u.nombres, ' ', u.apellidos)"],
            'monto' => ['Monto', 'pg.monto'],
            'estado' => ['Estado', 'pg.estado'],
            'motivo_anulacion' => ['Motivo de anulación', 'pg.motivo_anulacion'],
        ],
    ];

    // Columnas dinámicas numéricas, con dinero, de fecha y de hora.
    private const NUMERICAS = ['costo_acordado', 'sesiones_planificadas', 'sesiones_realizadas', 'estudios_cargados', 'controles_pendientes', 'duracion_minutos', 'monto'];

    private const MONTOS = ['costo_acordado', 'monto'];

    private const FECHAS = ['fecha_inicio', 'fecha_fin', 'ultima_sesion', 'fecha', 'fecha_pago'];

    private const HORAS = ['hora_inicio', 'hora_fin'];

    // RF-84 y RF-85: documentos por paciente (no tabulares). Solo se exportan a PDF.
    public const DOCUMENTOS = [
        'historia' => 'Historia clínica',
        'odontogramas' => 'Comparativo de odontogramas',
    ];

    public const SIN_COMPARATIVO = 'El paciente no tiene dos odontogramas para comparar.';

    public function __construct(
        private PagoService $pagos,
        private HistoriaClinicaService $historias,
        private OdontogramaService $odontogramas,
    ) {
    }

    /**
     * Columnas que se pueden elegir en el reporte dinámico de una familia (clave => encabezado).
     *
     * @return array<string, string>
     */
    public static function columnasDisponibles(string $reporte): array
    {
        return array_map(fn ($columna) => $columna[0], self::COLUMNAS[$reporte]);
    }

    /**
     * Texto de una celda o total para la pantalla y el PDF: dinero en formato boliviano.
     */
    public static function formatear(array $datos, string|int $clave, mixed $valor): string
    {
        if (in_array($clave, $datos['montos'], true)) {
            return 'Bs '.number_format((float) $valor, 2, ',', '.');
        }

        if (is_float($valor) || is_int($valor)) {
            return number_format($valor, floor($valor) == $valor ? 0 : 2, ',', '.');
        }

        return (string) $valor;
    }

    /**
     * Ejecuta el reporte predefinido del tipo, con sus columnas fijas.
     *
     * @param  array{fecha_inicio: string, fecha_fin: string}  $periodo
     */
    public function estatico(string $tipo, array $periodo): array
    {
        return match ($tipo) {
            'pacientes_atendidos' => $this->pacientesAtendidos($periodo),
            'tratamientos_odontologo' => $this->tratamientosOdontologo($periodo),
            'sesiones_pendientes' => $this->sesionesPendientes($periodo),
            'materiales_utilizados' => $this->materialesUtilizados($periodo),
            'agenda_citas' => $this->agendaCitas($periodo),
            'citas_no_asistidas' => $this->citasNoAsistidas($periodo),
            'saldo_pendiente' => $this->saldoPendiente($periodo),
            'ingresos' => $this->ingresos($periodo),
        };
    }

    /**
     * Arma el reporte con los filtros elegidos y solo las columnas marcadas.
     *
     * @param  array  $filtros  reporte, fecha_inicio, fecha_fin y los filtros opcionales.
     * @param  list<string>  $columnas  Claves de la lista blanca (validadas por el controlador).
     */
    public function dinamico(array $filtros, array $columnas): array
    {
        $reporte = $filtros['reporte'];
        $elegidas = array_intersect_key(self::COLUMNAS[$reporte], array_flip($columnas));

        $consulta = match ($reporte) {
            'clinico' => $this->baseClinica($filtros),
            'agenda' => $this->baseAgenda($filtros),
            'financiero' => $this->baseFinanciera($filtros),
        };

        // Las expresiones salen de la lista blanca, nunca de lo que envió el usuario.
        $seleccion = [];
        foreach ($elegidas as $clave => $columna) {
            $seleccion[] = DB::raw("{$columna[1]} as {$clave}");
        }

        $filas = (clone $consulta)->select($seleccion)->get()
            ->map(fn ($fila) => $this->filaDinamica((array) $fila))
            ->all();

        $totales = ['Registros' => count($filas)];
        $montos = array_values(array_intersect(array_keys($elegidas), self::MONTOS));

        if (isset($elegidas['costo_acordado'])) {
            $totales['Costo acordado'] = array_sum(array_column($filas, 'costo_acordado'));
            $montos[] = 'Costo acordado';
        }

        if (isset($elegidas['monto'])) {
            // Solo los abonos vigentes, aunque la columna de estado no esté marcada.
            $totales['Monto vigente'] = (float) (clone $consulta)->where('pg.estado', 'vigente')->sum('pg.monto');
            $montos[] = 'Monto vigente';
        }

        return $this->datos(
            'Reporte '.['clinico' => 'clínico', 'agenda' => 'de agenda', 'financiero' => 'financiero'][$reporte].' personalizado',
            "reporte_{$reporte}",
            $filtros,
            array_map(fn ($columna) => $columna[0], $elegidas),
            array_values(array_intersect(array_keys($elegidas), self::NUMERICAS)),
            $montos,
            $filas,
            $totales
        );
    }

    /**
     * CU45: genera el PDF en memoria y devuelve su contenido.
     */
    public function exportarPdf(array $datos): string
    {
        $orientacion = count($datos['columnas']) > 6 ? 'landscape' : 'portrait';

        return Pdf::loadView('reportes.pdf', ['datos' => $datos])
            ->setPaper('letter', $orientacion)
            ->output();
    }

    /**
     * CU45: genera el .xlsx en memoria y devuelve su contenido.
     */
    public function exportarExcel(array $datos): string
    {
        $libro = new Spreadsheet;
        $hoja = $libro->getActiveSheet();
        $hoja->setTitle('Reporte');

        $hoja->setCellValueExplicit('A1', $datos['titulo'], DataType::TYPE_STRING);
        $hoja->setCellValueExplicit('A2', $datos['periodo'], DataType::TYPE_STRING);
        $hoja->getStyle('A1')->getFont()->setBold(true);

        $claves = array_keys($datos['columnas']);

        foreach (array_values($datos['columnas']) as $indice => $encabezado) {
            $celda = Coordinate::stringFromColumnIndex($indice + 1).'3';
            $hoja->setCellValueExplicit($celda, $encabezado, DataType::TYPE_STRING);
            $hoja->getStyle($celda)->getFont()->setBold(true);
        }

        $fila = 4;

        foreach ($datos['filas'] as $registro) {
            foreach ($claves as $indice => $clave) {
                $this->escribirCelda($hoja, $indice + 1, $fila, $datos, $clave, $registro[$clave]);
            }
            $fila++;
        }

        if ($datos['filas'] === []) {
            $hoja->setCellValueExplicit("A{$fila}", 'Sin registros', DataType::TYPE_STRING);
            $fila++;
        }

        foreach ($datos['totales'] as $etiqueta => $valor) {
            $hoja->setCellValueExplicit("A{$fila}", $etiqueta, DataType::TYPE_STRING);
            $hoja->getStyle("A{$fila}")->getFont()->setBold(true);
            $this->escribirCelda($hoja, 2, $fila, $datos, $etiqueta, $valor);
            $fila++;
        }

        foreach (range(1, max(count($claves), 2)) as $indice) {
            $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($indice))->setAutoSize(true);
        }

        ob_start();
        (new Xlsx($libro))->save('php://output');

        return ob_get_clean();
    }

    /**
     * RF-84: historia clínica imprimible. Reutiliza HistoriaClinicaService::consolidar() (CU24)
     * y le agrega las sesiones realizadas de cada tratamiento y el dibujo del último odontograma.
     * Devuelve null si el paciente no tiene historia clínica (igual que consolidar()).
     */
    public function historiaClinica(int $pacienteId): ?array
    {
        $paciente = Paciente::findOrFail($pacienteId);
        $datos = $this->historias->consolidar($paciente);

        if ($datos === null) {
            return null;
        }

        $tratamientos = $datos['planes']->flatMap->tratamientosAplicados;

        // Sesiones realizadas de todos los tratamientos en una sola consulta, agrupadas por tratamiento.
        $sesiones = Sesion::with('odontologo')
            ->whereIn('tratamiento_aplicado_id', $tratamientos->pluck('id'))
            ->where('estado', 'realizada')
            ->orderBy('fecha')
            ->orderBy('numero_sesion')
            ->get()
            ->groupBy('tratamiento_aplicado_id');

        return $datos + [
            'titulo' => self::DOCUMENTOS['historia'],
            'nombre_archivo' => 'historia_clinica_'.$this->apellidoArchivo($paciente),
            'tratamientos' => $tratamientos,
            'sesiones' => $sesiones,
            'dibujo' => $datos['ultimoOdontograma'] ? $this->dibujo($datos['ultimoOdontograma']) : null,
        ];
    }

    /**
     * RF-84: PDF de la historia clínica, con el mismo encabezado del consultorio que los reportes.
     */
    public function exportarHistoriaPdf(array $datos): string
    {
        return Pdf::loadView('reportes.historia-pdf', ['datos' => $datos])
            ->setPaper('letter', 'portrait')
            ->output();
    }

    /**
     * RF-85: comparativo de dos odontogramas del paciente. Reutiliza OdontogramaService::comparar() (CU26).
     * Sin $a y $b toma el INICIAL y el ÚLTIMO de evolución (solo activos; si falta alguno, el más
     * antiguo y el más reciente). Con menos de dos odontogramas devuelve 'aviso' = SIN_COMPARATIVO.
     * $a y $b deben venir validados (del paciente y distintos) por el controlador.
     */
    public function comparativoOdontograma(int $pacienteId, ?int $a = null, ?int $b = null): array
    {
        $paciente = Paciente::findOrFail($pacienteId);

        $odontogramas = Odontograma::with('odontologo', 'detalles.estadoPieza')
            ->whereHas('historiaClinica', fn ($q) => $q->where('paciente_id', $paciente->id))
            ->orderBy('fecha_registro')
            ->orderBy('id')
            ->get();

        $datos = [
            'titulo' => self::DOCUMENTOS['odontogramas'],
            'nombre_archivo' => 'comparativo_odontograma_'.$this->apellidoArchivo($paciente),
            'paciente' => $paciente,
            'odontogramas' => $odontogramas,
            'aviso' => null,
        ];

        if ($a !== null && $b !== null) {
            $odontogramaA = $odontogramas->firstWhere('id', $a);
            $odontogramaB = $odontogramas->firstWhere('id', $b);
        } else {
            $activos = $odontogramas->where('estado', 'activo')->values();
            $odontogramaA = $activos->firstWhere('tipo', 'inicial') ?? $activos->first();
            $odontogramaB = $activos->where('tipo', 'evolución')->last() ?? $activos->last();
        }

        if ($odontogramas->count() < 2 || ! $odontogramaA || ! $odontogramaB || $odontogramaA->id === $odontogramaB->id) {
            return array_merge($datos, ['aviso' => self::SIN_COMPARATIVO]);
        }

        $filas = $this->odontogramas->comparar($odontogramaA->id, $odontogramaB->id);
        $cambios = collect($filas)->where('cambio', true);

        // Cambios agrupados por el estado al que llegó la pieza en B.
        $porEstado = $cambios->groupBy(fn ($fila) => $fila['estado_b'] ?? 'No registrada')
            ->map(fn ($grupo, $estado) => [
                'estado' => $estado,
                'color' => $grupo->first()['color_b'],
                'cantidad' => $grupo->count(),
                'piezas' => $grupo->pluck('codigo_fdi')->all(),
            ])
            ->sortByDesc('cantidad')
            ->values()
            ->all();

        return array_merge($datos, [
            'a' => $odontogramaA,
            'b' => $odontogramaB,
            'dibujo_a' => $this->dibujo($odontogramaA),
            'dibujo_b' => $this->dibujo($odontogramaB),
            'filas' => $filas,
            'resumen' => [
                'total_piezas' => count($filas),
                'cambiaron' => $cambios->count(),
                'sin_cambio' => count($filas) - $cambios->count(),
                'por_estado' => $porEstado,
            ],
        ]);
    }

    /**
     * RF-85: PDF del comparativo, con el mismo encabezado del consultorio que los reportes.
     * Hoja apaisada: los dos odontogramas van lado a lado.
     */
    public function exportarComparativoPdf(array $datos): string
    {
        return Pdf::loadView('reportes.odontogramas-pdf', ['datos' => $datos])
            ->setPaper('letter', 'landscape')
            ->output();
    }

    /**
     * Color de texto legible sobre el color de un estado de pieza (igual que odontogramas/show).
     */
    public static function textoSobre(?string $hex): string
    {
        if (! $hex || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $hex)) {
            return '#1F2933';
        }
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) > 160 ? '#1F2933' : '#FFFFFF';
    }

    // Dibujo de un odontograma con el esquema FDI de odontogramas/show: arcadas, cuadrantes y el estado de cada pieza.
    private function dibujo(Odontograma $odontograma): array
    {
        $piezas = $odontograma->detalles->mapWithKeys(fn ($d) => [
            $d->pieza_dental_id => ['estado' => $d->estadoPieza->nombre, 'color' => $d->estadoPieza->color_referencia ?? '#E1E8EB'],
        ]);

        $arcadas = [
            'Arcada superior' => [range(18, 11), range(21, 28)],
            'Arcada inferior' => [range(48, 41), range(31, 38)],
        ];

        if ($odontograma->tipo_denticion !== 'permanente' || $piezas->keys()->contains(fn ($fdi) => $fdi >= 51)) {
            $arcadas['Arcada superior temporal'] = [range(55, 51), range(61, 65)];
            $arcadas['Arcada inferior temporal'] = [range(85, 81), range(71, 75)];
        }

        // Leyenda: solo los estados presentes, con la cantidad de piezas.
        $leyenda = $piezas->groupBy('estado')
            ->map(fn ($grupo, $estado) => ['estado' => $estado, 'color' => $grupo->first()['color'], 'cantidad' => $grupo->count()])
            ->values()
            ->all();

        return ['arcadas' => $arcadas, 'piezas' => $piezas->all(), 'leyenda' => $leyenda];
    }

    // Apellido del paciente para el nombre del archivo: sin tildes ni espacios.
    private function apellidoArchivo(Paciente $paciente): string
    {
        return Str::slug($paciente->apellidos, '_');
    }

    // Números como número (dinero con #,##0.00) y lo demás siempre como texto.
    private function escribirCelda($hoja, int $columna, int $fila, array $datos, string|int $clave, mixed $valor): void
    {
        $celda = Coordinate::stringFromColumnIndex($columna).$fila;

        if (is_int($valor) || is_float($valor)) {
            $hoja->setCellValueExplicit($celda, $valor, DataType::TYPE_NUMERIC);

            if (in_array($clave, $datos['montos'], true)) {
                $hoja->getStyle($celda)->getNumberFormat()->setFormatCode('#,##0.00');
            }

            return;
        }

        $hoja->setCellValueExplicit($celda, (string) $valor, DataType::TYPE_STRING);
    }

    // RF-83: citas atendidas del periodo, agrupadas por paciente.
    private function pacientesAtendidos(array $periodo): array
    {
        $filas = DB::table('citas as c')
            ->join('pacientes as p', 'p.id', '=', 'c.paciente_id')
            ->where('c.estado_cita_id', EstadoCita::idDe(EstadoCita::ATENDIDA))
            ->whereBetween('c.fecha', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->groupBy('p.id', 'p.ci', 'p.nombres', 'p.apellidos')
            ->orderBy('p.apellidos')
            ->orderBy('p.nombres')
            ->selectRaw('p.ci, p.nombres, p.apellidos, COUNT(*) as atenciones, MAX(c.fecha) as ultima')
            ->get()
            ->map(fn ($f) => [
                'ci' => $f->ci,
                'paciente' => "{$f->nombres} {$f->apellidos}",
                'atenciones' => (int) $f->atenciones,
                'ultima_atencion' => $this->fecha($f->ultima),
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['clinico']['pacientes_atendidos'],
            'pacientes_atendidos',
            $periodo,
            ['ci' => 'CI', 'paciente' => 'Paciente', 'atenciones' => 'Atenciones', 'ultima_atencion' => 'Última atención'],
            ['atenciones'],
            [],
            $filas,
            ['Pacientes' => count($filas), 'Atenciones' => array_sum(array_column($filas, 'atenciones'))]
        );
    }

    // RF-86: tratamientos ya iniciados (en proceso o concluidos) por odontólogo y especialidad.
    private function tratamientosOdontologo(array $periodo): array
    {
        $filas = DB::table('tratamientos_aplicados as ta')
            ->join('users as u', 'u.id', '=', 'ta.odontologo_id')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->join('especialidades as e', 'e.id', '=', 't.especialidad_id')
            ->whereIn('ta.estado', ['en proceso', 'concluido'])
            ->whereBetween('ta.fecha_inicio', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->groupBy('u.id', 'u.nombres', 'u.apellidos', 'e.id', 'e.nombre')
            ->orderBy('u.apellidos')
            ->orderBy('u.nombres')
            ->orderBy('e.nombre')
            ->selectRaw('u.nombres, u.apellidos, e.nombre as especialidad, COUNT(*) as cantidad, SUM(ta.costo_acordado) as costo')
            ->get()
            ->map(fn ($f) => [
                'odontologo' => "{$f->nombres} {$f->apellidos}",
                'especialidad' => $f->especialidad,
                'cantidad' => (int) $f->cantidad,
                'costo_acordado' => (float) $f->costo,
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['clinico']['tratamientos_odontologo'],
            'tratamientos_odontologo',
            $periodo,
            ['odontologo' => 'Odontólogo', 'especialidad' => 'Especialidad', 'cantidad' => 'Cantidad', 'costo_acordado' => 'Costo acordado'],
            ['cantidad', 'costo_acordado'],
            ['costo_acordado', 'Costo acordado'],
            $filas,
            ['Cantidad' => array_sum(array_column($filas, 'cantidad')), 'Costo acordado' => array_sum(array_column($filas, 'costo_acordado'))]
        );
    }

    // RF-88: tratamientos planificados o en proceso con menos sesiones realizadas que planificadas.
    private function sesionesPendientes(array $periodo): array
    {
        $filas = DB::table('tratamientos_aplicados as ta')
            ->join('planes_tratamiento as pl', 'pl.id', '=', 'ta.plan_tratamiento_id')
            ->join('pacientes as p', 'p.id', '=', 'pl.paciente_id')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->join('users as u', 'u.id', '=', 'ta.odontologo_id')
            ->leftJoin('sesiones as s', function ($join) {
                $join->on('s.tratamiento_aplicado_id', '=', 'ta.id')
                    ->where('s.estado', 'realizada');
            })
            ->whereIn('ta.estado', ['planificado', 'en proceso'])
            ->whereBetween('ta.fecha_inicio', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->groupBy('ta.id', 'ta.estado', 'ta.sesiones_planificadas', 'p.nombres', 'p.apellidos', 't.nombre', 'u.nombres', 'u.apellidos')
            ->havingRaw('COUNT(s.id) < ta.sesiones_planificadas')
            ->orderBy('p.apellidos')
            ->orderBy('p.nombres')
            ->selectRaw('p.nombres, p.apellidos, t.nombre as tratamiento, u.nombres as o_nombres, u.apellidos as o_apellidos,
                ta.estado, ta.sesiones_planificadas as planificadas, COUNT(s.id) as realizadas, MAX(s.fecha) as ultima')
            ->get()
            ->map(fn ($f) => [
                'paciente' => "{$f->nombres} {$f->apellidos}",
                'tratamiento' => $f->tratamiento,
                'odontologo' => "{$f->o_nombres} {$f->o_apellidos}",
                'estado' => ucfirst($f->estado),
                'avance' => "Sesión {$f->realizadas} de {$f->planificadas}",
                'pendientes' => (int) $f->planificadas - (int) $f->realizadas,
                'ultima_sesion' => $this->fecha($f->ultima),
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['clinico']['sesiones_pendientes'],
            'sesiones_pendientes',
            $periodo,
            [
                'paciente' => 'Paciente', 'tratamiento' => 'Tratamiento', 'odontologo' => 'Odontólogo', 'estado' => 'Estado',
                'avance' => 'Avance', 'pendientes' => 'Pendientes', 'ultima_sesion' => 'Última sesión realizada',
            ],
            ['pendientes'],
            [],
            $filas,
            ['Tratamientos' => count($filas), 'Sesiones pendientes' => array_sum(array_column($filas, 'pendientes'))]
        );
    }

    // RF-89: materiales de las sesiones realizadas del periodo, por material y tratamiento.
    private function materialesUtilizados(array $periodo): array
    {
        $filas = DB::table('sesiones_materiales as sm')
            ->join('sesiones as s', 's.id', '=', 'sm.sesion_id')
            ->join('materiales as m', 'm.id', '=', 'sm.material_id')
            ->join('tratamientos_aplicados as ta', 'ta.id', '=', 's.tratamiento_aplicado_id')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->where('s.estado', 'realizada')
            ->whereBetween('s.fecha', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->groupBy('m.id', 'm.nombre', 'm.unidad_medida', 't.id', 't.nombre')
            ->orderBy('m.nombre')
            ->orderBy('t.nombre')
            ->selectRaw('m.nombre as material, m.unidad_medida, t.nombre as tratamiento, SUM(sm.cantidad) as cantidad')
            ->get()
            ->map(fn ($f) => [
                'material' => $f->material,
                'unidad_medida' => $f->unidad_medida,
                'tratamiento' => $f->tratamiento,
                'cantidad' => (float) $f->cantidad,
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['clinico']['materiales_utilizados'],
            'materiales_utilizados',
            $periodo,
            ['material' => 'Material', 'unidad_medida' => 'Unidad de medida', 'tratamiento' => 'Tratamiento', 'cantidad' => 'Cantidad'],
            ['cantidad'],
            [],
            $filas,
            ['Registros' => count($filas)]
        );
    }

    // RF-87: citas del periodo, con la cantidad por estado.
    private function agendaCitas(array $periodo): array
    {
        $citas = Cita::with('paciente', 'odontologo', 'estado')
            ->whereBetween('fecha', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();

        $filas = $citas->map(fn ($c) => [
            'fecha' => $c->fecha->format('d/m/Y'),
            'hora_inicio' => $c->horaInicioCorta(),
            'hora_fin' => $c->horaFinCorta(),
            'paciente' => $c->paciente->nombreCompleto(),
            'odontologo' => "{$c->odontologo->nombres} {$c->odontologo->apellidos}",
            'motivo' => $c->motivo ?? '—',
            'estado' => ucfirst($c->estado->nombre),
        ])->all();

        return $this->datos(
            self::ESTATICOS['agenda']['agenda_citas'],
            'agenda_citas',
            $periodo,
            [
                'fecha' => 'Fecha', 'hora_inicio' => 'Hora inicio', 'hora_fin' => 'Hora fin', 'paciente' => 'Paciente',
                'odontologo' => 'Odontólogo', 'motivo' => 'Motivo', 'estado' => 'Estado',
            ],
            [],
            [],
            $filas,
            ['Citas' => count($filas)] + array_count_values(array_column($filas, 'estado'))
        );
    }

    // RF-87: citas no asistidas del periodo.
    private function citasNoAsistidas(array $periodo): array
    {
        $filas = Cita::with('paciente', 'odontologo')
            ->where('estado_cita_id', EstadoCita::idDe(EstadoCita::NO_ASISTIDA))
            ->whereBetween('fecha', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn ($c) => [
                'fecha' => $c->fecha->format('d/m/Y'),
                'hora_inicio' => $c->horaInicioCorta(),
                'paciente' => $c->paciente->nombreCompleto(),
                'telefono' => $c->paciente->telefono ?? '—',
                'odontologo' => "{$c->odontologo->nombres} {$c->odontologo->apellidos}",
                'motivo' => $c->motivo ?? '—',
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['agenda']['citas_no_asistidas'],
            'citas_no_asistidas',
            $periodo,
            [
                'fecha' => 'Fecha', 'hora_inicio' => 'Hora inicio', 'paciente' => 'Paciente', 'telefono' => 'Teléfono',
                'odontologo' => 'Odontólogo', 'motivo' => 'Motivo',
            ],
            [],
            [],
            $filas,
            ['Citas no asistidas' => count($filas)]
        );
    }

    // RF-90: tratamientos no cancelados con saldo (nunca guardado: lo calcula PagoService).
    private function saldoPendiente(array $periodo): array
    {
        $filas = TratamientoAplicado::with('plan.paciente', 'tratamiento')
            ->where('estado', '!=', 'cancelado')
            ->whereBetween('fecha_inicio', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->get()
            ->map(function ($t) {
                $saldo = $this->pagos->saldoPendiente($t);
                $costo = (float) $t->costo_acordado;

                return [
                    'paciente' => $t->plan->paciente->nombreCompleto(),
                    'tratamiento' => $t->tratamiento->nombre,
                    'estado' => ucfirst($t->estado),
                    'costo_acordado' => $costo,
                    'abonado' => round($costo - $saldo, 2),
                    'saldo_pendiente' => $saldo,
                ];
            })
            ->filter(fn ($fila) => $fila['saldo_pendiente'] > 0)
            ->sortBy('paciente')
            ->values()
            ->all();

        return $this->datos(
            self::ESTATICOS['financiero']['saldo_pendiente'],
            'saldo_pendiente',
            $periodo,
            [
                'paciente' => 'Paciente', 'tratamiento' => 'Tratamiento', 'estado' => 'Estado',
                'costo_acordado' => 'Costo acordado', 'abonado' => 'Abonado', 'saldo_pendiente' => 'Saldo pendiente',
            ],
            ['costo_acordado', 'abonado', 'saldo_pendiente'],
            ['costo_acordado', 'abonado', 'saldo_pendiente', 'Costo acordado', 'Abonado', 'Saldo pendiente'],
            $filas,
            [
                'Costo acordado' => array_sum(array_column($filas, 'costo_acordado')),
                'Abonado' => array_sum(array_column($filas, 'abonado')),
                'Saldo pendiente' => array_sum(array_column($filas, 'saldo_pendiente')),
            ]
        );
    }

    // RF-91: abonos vigentes del periodo, por método de pago.
    private function ingresos(array $periodo): array
    {
        $filas = DB::table('pagos as pg')
            ->join('metodos_pago as mp', 'mp.id', '=', 'pg.metodo_pago_id')
            ->where('pg.estado', 'vigente')
            ->whereBetween('pg.fecha_pago', [$periodo['fecha_inicio'], $periodo['fecha_fin']])
            ->groupBy('mp.id', 'mp.nombre')
            ->orderBy('mp.nombre')
            ->selectRaw('mp.nombre as metodo, COUNT(*) as cantidad, SUM(pg.monto) as total')
            ->get()
            ->map(fn ($f) => [
                'metodo_pago' => $f->metodo,
                'cantidad' => (int) $f->cantidad,
                'total' => (float) $f->total,
            ])
            ->all();

        return $this->datos(
            self::ESTATICOS['financiero']['ingresos'],
            'ingresos',
            $periodo,
            ['metodo_pago' => 'Método de pago', 'cantidad' => 'Cantidad de abonos', 'total' => 'Total'],
            ['cantidad', 'total'],
            ['total', 'Total ingresos'],
            $filas,
            ['Abonos' => array_sum(array_column($filas, 'cantidad')), 'Total ingresos' => array_sum(array_column($filas, 'total'))]
        );
    }

    // Reporte dinámico clínico: tratamientos aplicados con sus filtros.
    private function baseClinica(array $filtros): Builder
    {
        return DB::table('tratamientos_aplicados as ta')
            ->join('planes_tratamiento as pl', 'pl.id', '=', 'ta.plan_tratamiento_id')
            ->join('pacientes as p', 'p.id', '=', 'pl.paciente_id')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->join('especialidades as e', 'e.id', '=', 't.especialidad_id')
            ->join('users as u', 'u.id', '=', 'ta.odontologo_id')
            ->join('diagnosticos as d', 'd.id', '=', 'ta.diagnostico_id')
            ->whereBetween('ta.fecha_inicio', [$filtros['fecha_inicio'], $filtros['fecha_fin']])
            ->when($filtros['odontologo_id'] ?? null, fn ($q, $id) => $q->where('ta.odontologo_id', $id))
            ->when($filtros['especialidad_id'] ?? null, fn ($q, $id) => $q->where('t.especialidad_id', $id))
            ->when($filtros['tratamiento_id'] ?? null, fn ($q, $id) => $q->where('ta.tratamiento_id', $id))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('ta.estado', $estado))
            ->orderBy('ta.fecha_inicio')
            ->orderBy('ta.id');
    }

    // Reporte dinámico de agenda: citas con sus filtros.
    private function baseAgenda(array $filtros): Builder
    {
        return DB::table('citas as c')
            ->join('pacientes as p', 'p.id', '=', 'c.paciente_id')
            ->join('users as u', 'u.id', '=', 'c.odontologo_id')
            ->join('estados_cita as ec', 'ec.id', '=', 'c.estado_cita_id')
            ->whereBetween('c.fecha', [$filtros['fecha_inicio'], $filtros['fecha_fin']])
            ->when($filtros['odontologo_id'] ?? null, fn ($q, $id) => $q->where('c.odontologo_id', $id))
            ->when($filtros['paciente_id'] ?? null, fn ($q, $id) => $q->where('c.paciente_id', $id))
            ->when($filtros['estado_cita_id'] ?? null, fn ($q, $id) => $q->where('c.estado_cita_id', $id))
            ->orderBy('c.fecha')
            ->orderBy('c.hora_inicio');
    }

    // Reporte dinámico financiero: pagos con sus filtros. El odontólogo es quien recibió el cobro.
    private function baseFinanciera(array $filtros): Builder
    {
        return DB::table('pagos as pg')
            ->join('tratamientos_aplicados as ta', 'ta.id', '=', 'pg.tratamiento_aplicado_id')
            ->join('planes_tratamiento as pl', 'pl.id', '=', 'ta.plan_tratamiento_id')
            ->join('pacientes as p', 'p.id', '=', 'pl.paciente_id')
            ->join('tratamientos as t', 't.id', '=', 'ta.tratamiento_id')
            ->join('metodos_pago as mp', 'mp.id', '=', 'pg.metodo_pago_id')
            ->join('users as u', 'u.id', '=', 'pg.odontologo_id')
            ->whereBetween('pg.fecha_pago', [$filtros['fecha_inicio'], $filtros['fecha_fin']])
            ->when($filtros['odontologo_id'] ?? null, fn ($q, $id) => $q->where('pg.odontologo_id', $id))
            ->when($filtros['metodo_pago_id'] ?? null, fn ($q, $id) => $q->where('pg.metodo_pago_id', $id))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('pg.estado', $estado))
            ->orderBy('pg.fecha_pago')
            ->orderBy('pg.id');
    }

    // Convierte una fila dinámica al formato común de los reportes.
    private function filaDinamica(array $fila): array
    {
        foreach ($fila as $clave => $valor) {
            $fila[$clave] = match (true) {
                in_array($clave, self::NUMERICAS, true) => (float) $valor,
                in_array($clave, self::FECHAS, true) => $this->fecha($valor),
                in_array($clave, self::HORAS, true) => substr((string) $valor, 0, 5),
                $clave === 'estado' => ucfirst($valor),
                default => $valor === null ? '—' : (string) $valor,
            };
        }

        return $fila;
    }

    // Fecha d/m/Y, o "—" si no hay.
    private function fecha(?string $fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : '—';
    }

    // Estructura común de los datos de un reporte.
    private function datos(string $titulo, string $nombreArchivo, array $periodo, array $columnas, array $numericas, array $montos, array $filas, array $totales): array
    {
        return [
            'titulo' => $titulo,
            'nombre_archivo' => $nombreArchivo,
            'periodo' => Carbon::parse($periodo['fecha_inicio'])->format('d/m/Y').' al '.Carbon::parse($periodo['fecha_fin'])->format('d/m/Y'),
            'columnas' => $columnas,
            'numericas' => $numericas,
            'montos' => $montos,
            'filas' => $filas,
            'totales' => $totales,
        ];
    }
}
