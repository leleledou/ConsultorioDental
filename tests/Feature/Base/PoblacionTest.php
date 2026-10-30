<?php

use App\Models\EstadoCita;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;

/*
 * Coherencia de la población completa (DatabaseSeeder). Comprueba las reglas que la
 * base no puede garantizar sola (no hay disparadores: las reglas van en el código).
 */

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
});

it('llena las 29 tablas del diagrama de clases', function () {
    $tablas = [
        'especialidades', 'users', 'bitacoras', 'antecedentes_medicos', 'piezas_dentales',
        'estados_pieza', 'diagnosticos', 'tipos_estudio', 'materiales', 'estados_cita',
        'metodos_pago', 'pacientes', 'tratamientos', 'avisos', 'historias_clinicas',
        'tarifas', 'tratamientos_tipos_estudio', 'planes_tratamiento', 'historias_antecedentes',
        'odontogramas', 'tratamientos_aplicados', 'detalles_odontograma', 'citas',
        'estudios_diagnosticos', 'pagos', 'controles', 'sesiones', 'valoraciones_implante',
        'sesiones_materiales',
    ];

    $vacias = array_values(array_filter($tablas, fn ($tabla) => DB::table($tabla)->count() === 0));

    expect($tablas)->toHaveCount(29)
        ->and($vacias)->toBe([]);
});

it('no tiene citas programadas o atendidas que se crucen en el sillón', function () {
    $ocupan = [EstadoCita::idDe(EstadoCita::PROGRAMADA), EstadoCita::idDe(EstadoCita::ATENDIDA)];

    // Hay cruce si una empieza antes de que termine la otra y termina después de que empiece.
    $cruces = DB::table('citas as a')
        ->join('citas as b', function ($join) {
            $join->on('a.fecha', '=', 'b.fecha')
                ->on('a.id', '<', 'b.id')
                ->on('a.hora_inicio', '<', 'b.hora_fin')
                ->on('a.hora_fin', '>', 'b.hora_inicio');
        })
        ->whereIn('a.estado_cita_id', $ocupan)
        ->whereIn('b.estado_cita_id', $ocupan)
        ->get(['a.id as cita', 'b.id as cruza_con', 'a.fecha']);

    expect($cruces)->toBeEmpty();
});

it('no cobra más que el costo acordado de cada tratamiento aplicado', function () {
    $excedidos = DB::table('tratamientos_aplicados as ta')
        ->join('pagos as p', 'p.tratamiento_aplicado_id', '=', 'ta.id')
        ->where('p.estado', 'vigente')
        ->groupBy('ta.id', 'ta.costo_acordado')
        ->havingRaw('SUM(p.monto) > ta.costo_acordado')
        ->pluck('ta.id');

    expect($excedidos)->toBeEmpty();
});

it('calcula el costo total de cada plan como la suma de sus tratamientos aplicados', function () {
    $descuadrados = DB::table('planes_tratamiento as pt')
        ->leftJoin('tratamientos_aplicados as ta', 'ta.plan_tratamiento_id', '=', 'pt.id')
        ->groupBy('pt.id', 'pt.costo_total')
        ->havingRaw('pt.costo_total <> COALESCE(SUM(ta.costo_acordado), 0)')
        ->pluck('pt.id');

    expect($descuadrados)->toBeEmpty();
});

it('enlaza cada sesión con una cita del mismo tratamiento aplicado y del paciente del plan', function () {
    $sesiones = DB::table('sesiones as s')
        ->join('citas as c', 'c.id', '=', 's.cita_id')
        ->join('tratamientos_aplicados as ta', 'ta.id', '=', 's.tratamiento_aplicado_id')
        ->join('planes_tratamiento as pt', 'pt.id', '=', 'ta.plan_tratamiento_id')
        ->get(['s.id', 's.tratamiento_aplicado_id', 'c.tratamiento_aplicado_id as ta_cita', 'c.paciente_id', 'pt.paciente_id as paciente_plan']);

    $incoherentes = $sesiones
        ->filter(fn ($s) => $s->tratamiento_aplicado_id !== $s->ta_cita || $s->paciente_id !== $s->paciente_plan)
        ->pluck('id');

    expect($sesiones)->not->toBeEmpty()
        ->and($incoherentes)->toBeEmpty();
});

it('tiene un solo odontograma por historia y fecha, sin piezas repetidas', function () {
    $fechasRepetidas = DB::table('odontogramas')
        ->groupBy('historia_clinica_id', 'fecha_registro')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('historia_clinica_id');

    $piezasRepetidas = DB::table('detalles_odontograma')
        ->groupBy('odontograma_id', 'pieza_dental_id')
        ->havingRaw('COUNT(*) > 1')
        ->pluck('odontograma_id');

    expect($fechasRepetidas)->toBeEmpty()
        ->and($piezasRepetidas)->toBeEmpty();
});

it('tiene exactamente un administrador', function () {
    expect(DB::table('users')->where('rol', 'administrador')->pluck('usuario')->all())->toBe(['rcalizaya']);
});

it('tiene un paciente inhabilitado sin planes ni citas futuras', function () {
    $inactivos = DB::table('pacientes')->where('estado', 'inactivo')->pluck('id');

    expect($inactivos)->toHaveCount(1)
        ->and(DB::table('planes_tratamiento')->whereIn('paciente_id', $inactivos)->exists())->toBeFalse()
        ->and(DB::table('citas')->whereIn('paciente_id', $inactivos)->where('fecha', '>=', today())->exists())->toBeFalse();
});

it('tiene un tratamiento dado de baja que no se usa en tratamientos aplicados', function () {
    $bajas = DB::table('tratamientos')->where('estado', 'no disponible')->pluck('id');

    expect($bajas)->toHaveCount(1)
        ->and(DB::table('tratamientos_aplicados')->whereIn('tratamiento_id', $bajas)->exists())->toBeFalse();
});

it('tiene citas en la semana actual y en las dos siguientes (lunes a sábado)', function () {
    $lunes = today()->startOfWeek(Carbon::MONDAY);

    foreach ([0, 1, 2] as $semana) {
        $inicio = $lunes->copy()->addWeeks($semana);

        expect(DB::table('citas')->whereBetween('fecha', [$inicio, $inicio->copy()->addDays(5)])->count())
            ->toBeGreaterThan(0);
    }
});
