<?php

use App\Models\EstadoCita;
use App\Services\ReporteService;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU42, CU43 y CU44 Generar reportes. Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo', ['nombres' => 'Rosa', 'apellidos' => 'Calizaya']);
    $this->periodo = ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'];
    $this->servicio = app(ReporteService::class);
});

// Parámetros de un reporte estático de marzo de 2026.
function estatico(string $tipo, array $cambios = []): array
{
    return $cambios + ['modo' => 'estatico', 'tipo' => $tipo, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'];
}

// Parámetros de un reporte dinámico de marzo de 2026.
function dinamico(array $columnas, array $cambios = []): array
{
    return $cambios + ['modo' => 'dinamico', 'columnas' => $columnas, 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'];
}

it('cada pantalla carga para los dos roles', function (string $ruta, string $rol) {
    $this->actingAs(crearUsuario($rol))
        ->get(route($ruta))
        ->assertOk()
        ->assertSee('Reportes clínicos')
        ->assertSee('Reportes de agenda')
        ->assertSee('Reportes financieros')
        ->assertSee('Reporte personalizado');
})->with(['reportes.clinicos', 'reportes.agenda', 'reportes.financieros'])->with(['administrador', 'odontologo']);

it('el menú de reportes queda activo y habilitado', function () {
    $html = $this->actingAs($this->usuario)->get(route('reportes.agenda'))->getContent();

    expect($html)->toMatch('/<a[^>]*font-semibold[^>]*reportes\/clinicos"|<a[^>]*reportes\/clinicos"[^>]*font-semibold/s');
});

it('estático pacientes atendidos agrupa por paciente y solo cuenta atendidas del periodo', function () {
    $ana = crearPaciente(['ci' => '111', 'nombres' => 'Ana', 'apellidos' => 'Zeballos']);
    $luis = crearPaciente(['ci' => '222', 'nombres' => 'Luis', 'apellidos' => 'Arce']);

    crearCita(['paciente_id' => $ana->id, 'fecha' => '2026-03-05', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $ana->id, 'fecha' => '2026-03-20', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $luis->id, 'fecha' => '2026-03-10', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $luis->id, 'fecha' => '2026-03-11', 'estado' => EstadoCita::CANCELADA]);
    crearCita(['paciente_id' => $luis->id, 'fecha' => '2026-04-01', 'estado' => EstadoCita::ATENDIDA]);

    $datos = $this->servicio->estatico('pacientes_atendidos', $this->periodo);

    expect($datos['filas'])->toBe([
        ['ci' => '222', 'paciente' => 'Luis Arce', 'atenciones' => 1, 'ultima_atencion' => '10/03/2026'],
        ['ci' => '111', 'paciente' => 'Ana Zeballos', 'atenciones' => 2, 'ultima_atencion' => '20/03/2026'],
    ])->and($datos['totales'])->toBe(['Pacientes' => 2, 'Atenciones' => 3])
        ->and($datos['periodo'])->toBe('01/03/2026 al 31/03/2026')
        ->and($datos['nombre_archivo'])->toBe('pacientes_atendidos');
});

it('estático tratamientos por odontólogo solo cuenta los iniciados del periodo', function () {
    $especialidad = crearTratamiento(['nombre' => 'Conducto'])->especialidad_id;
    $conducto = crearTratamiento(['especialidad_id' => $especialidad]);
    $base = ['odontologo_id' => $this->usuario->id, 'tratamiento_id' => $conducto->id];

    crearTratamientoAplicado($base + ['fecha_inicio' => '2026-03-02', 'costo_acordado' => 100, 'estado' => 'en proceso']);
    crearTratamientoAplicado($base + ['fecha_inicio' => '2026-03-03', 'costo_acordado' => 250.5, 'estado' => 'concluido', 'fecha_fin' => '2026-03-04']);
    crearTratamientoAplicado($base + ['fecha_inicio' => '2026-02-28', 'costo_acordado' => 999, 'estado' => 'concluido', 'fecha_fin' => '2026-03-04']);

    $datos = $this->servicio->estatico('tratamientos_odontologo', $this->periodo);

    expect($datos['filas'])->toHaveCount(1)
        ->and($datos['filas'][0]['odontologo'])->toBe('Rosa Calizaya')
        ->and($datos['filas'][0]['cantidad'])->toBe(2)
        ->and($datos['filas'][0]['costo_acordado'])->toBe(350.5)
        ->and($datos['totales'])->toBe(['Cantidad' => 2, 'Costo acordado' => 350.5]);
});

it('estático tratamientos por odontólogo no cuenta planificados ni cancelados', function () {
    crearTratamientoAplicado(['fecha_inicio' => '2026-03-02', 'costo_acordado' => 999, 'estado' => 'planificado']);
    crearTratamientoAplicado(['fecha_inicio' => '2026-03-03', 'costo_acordado' => 999, 'estado' => 'cancelado']);

    $datos = $this->servicio->estatico('tratamientos_odontologo', $this->periodo);

    expect($datos['filas'])->toBe([])
        ->and($datos['totales'])->toBe(['Cantidad' => 0, 'Costo acordado' => 0]);
});

it('estático sesiones pendientes solo cuenta las realizadas', function () {
    $paciente = crearPaciente(['nombres' => 'Ana', 'apellidos' => 'Zeballos']);
    $plan = crearPlan(['paciente_id' => $paciente->id]);
    $base = ['plan_tratamiento_id' => $plan->id, 'odontologo_id' => $this->usuario->id, 'fecha_inicio' => '2026-03-02'];

    $pendiente = crearTratamientoAplicado($base + ['estado' => 'en proceso', 'sesiones_planificadas' => 4]);
    crearSesion($pendiente->id, ['fecha' => '2026-03-05']);
    crearSesion($pendiente->id, ['fecha' => '2026-03-09']);
    crearSesion($pendiente->id, ['fecha' => '2026-03-20', 'estado' => 'pendiente']);
    crearSesion($pendiente->id, ['fecha' => '2026-03-21', 'estado' => 'anulada']);

    crearTratamientoAplicado($base + ['estado' => 'planificado', 'sesiones_planificadas' => 2]);

    $completo = crearTratamientoAplicado($base + ['estado' => 'en proceso', 'sesiones_planificadas' => 1]);
    crearSesion($completo->id);

    crearTratamientoAplicado($base + ['estado' => 'concluido', 'sesiones_planificadas' => 3, 'fecha_fin' => '2026-03-10']);

    $datos = $this->servicio->estatico('sesiones_pendientes', $this->periodo);

    expect($datos['filas'])->toHaveCount(2)
        ->and(collect($datos['filas'])->firstWhere('estado', 'En proceso'))->toMatchArray([
            'paciente' => 'Ana Zeballos',
            'avance' => 'Sesión 2 de 4',
            'pendientes' => 2,
            'ultima_sesion' => '09/03/2026',
        ])
        ->and(collect($datos['filas'])->firstWhere('estado', 'Planificado'))->toMatchArray([
            'avance' => 'Sesión 0 de 2',
            'pendientes' => 2,
            'ultima_sesion' => '—',
        ])
        ->and($datos['totales'])->toBe(['Tratamientos' => 2, 'Sesiones pendientes' => 4]);
});

it('estático materiales agrupa por material y tratamiento y solo cuenta sesiones realizadas del periodo', function () {
    $tratamiento = crearTratamientoAplicado(['tratamiento_id' => crearTratamiento(['nombre' => 'Conducto'])->id]);
    $material = crearMaterial(['nombre' => 'Resina', 'unidad_medida' => 'jeringa']);

    crearSesionMaterial(crearSesion($tratamiento->id, ['fecha' => '2026-03-05'])->id, $material->id, 1.5);
    crearSesionMaterial(crearSesion($tratamiento->id, ['fecha' => '2026-03-06'])->id, $material->id, 2);
    crearSesionMaterial(crearSesion($tratamiento->id, ['fecha' => '2026-03-07', 'estado' => 'anulada'])->id, $material->id, 50);
    crearSesionMaterial(crearSesion($tratamiento->id, ['fecha' => '2026-04-07'])->id, $material->id, 50);

    $datos = $this->servicio->estatico('materiales_utilizados', $this->periodo);

    expect($datos['filas'])->toBe([
        ['material' => 'Resina', 'unidad_medida' => 'jeringa', 'tratamiento' => 'Conducto', 'cantidad' => 3.5],
    ])->and($datos['totales'])->toBe(['Registros' => 1]);
});

it('estático agenda ordena por fecha y hora y cuenta por estado', function () {
    $paciente = crearPaciente(['nombres' => 'Ana', 'apellidos' => 'Zeballos']);

    crearCita(['paciente_id' => $paciente->id, 'odontologo_id' => $this->usuario->id, 'fecha' => '2026-03-10', 'hora_inicio' => '11:00', 'motivo' => 'Segunda']);
    crearCita(['paciente_id' => $paciente->id, 'odontologo_id' => $this->usuario->id, 'fecha' => '2026-03-10', 'hora_inicio' => '09:00', 'motivo' => 'Primera', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $paciente->id, 'fecha' => '2026-04-10']);

    $datos = $this->servicio->estatico('agenda_citas', $this->periodo);

    expect($datos['filas'])->toHaveCount(2)
        ->and($datos['filas'][0])->toBe([
            'fecha' => '10/03/2026',
            'hora_inicio' => '09:00',
            'hora_fin' => '10:00',
            'paciente' => 'Ana Zeballos',
            'odontologo' => 'Rosa Calizaya',
            'motivo' => 'Primera',
            'estado' => 'Atendida',
        ])
        ->and($datos['filas'][1]['motivo'])->toBe('Segunda')
        ->and($datos['totales'])->toBe(['Citas' => 2, 'Atendida' => 1, 'Programada' => 1]);
});

it('estático citas no asistidas solo trae ese estado', function () {
    $paciente = crearPaciente(['nombres' => 'Ana', 'apellidos' => 'Zeballos', 'telefono' => '70000000']);

    crearCita(['paciente_id' => $paciente->id, 'fecha' => '2026-03-10', 'estado' => EstadoCita::NO_ASISTIDA]);
    crearCita(['paciente_id' => $paciente->id, 'fecha' => '2026-03-11', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $paciente->id, 'fecha' => '2026-04-10', 'estado' => EstadoCita::NO_ASISTIDA]);

    $datos = $this->servicio->estatico('citas_no_asistidas', $this->periodo);

    expect($datos['filas'])->toHaveCount(1)
        ->and($datos['filas'][0]['telefono'])->toBe('70000000')
        ->and($datos['totales'])->toBe(['Citas no asistidas' => 1]);
});

it('estático saldo pendiente excluye cancelados, pagados y anula los abonos anulados', function () {
    $paciente = crearPaciente(['nombres' => 'Ana', 'apellidos' => 'Zeballos']);
    $plan = crearPlan(['paciente_id' => $paciente->id]);
    $base = ['plan_tratamiento_id' => $plan->id, 'fecha_inicio' => '2026-03-02'];

    $conSaldo = crearTratamientoAplicado($base + ['costo_acordado' => 1000]);
    crearPago($conSaldo->id, ['monto' => 300]);
    crearPago($conSaldo->id, ['monto' => 500, 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    $pagado = crearTratamientoAplicado($base + ['costo_acordado' => 100]);
    crearPago($pagado->id, ['monto' => 100]);

    crearTratamientoAplicado($base + ['costo_acordado' => 500, 'estado' => 'cancelado']);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'fecha_inicio' => '2026-05-01', 'costo_acordado' => 700]);

    $datos = $this->servicio->estatico('saldo_pendiente', $this->periodo);

    expect($datos['filas'])->toHaveCount(1)
        ->and($datos['filas'][0])->toMatchArray([
            'paciente' => 'Ana Zeballos',
            'costo_acordado' => 1000.0,
            'abonado' => 300.0,
            'saldo_pendiente' => 700.0,
        ])
        ->and($datos['totales'])->toBe(['Costo acordado' => 1000.0, 'Abonado' => 300.0, 'Saldo pendiente' => 700.0]);
});

it('estático ingresos agrupa por método y no suma anulados ni fuera del periodo', function () {
    $tratamiento = crearTratamientoAplicado();
    $efectivo = crearMetodoPago(['nombre' => 'Efectivo']);
    $qr = crearMetodoPago(['nombre' => 'QR']);

    crearPago($tratamiento->id, ['metodo_pago_id' => $efectivo->id, 'monto' => 100, 'fecha_pago' => '2026-03-02']);
    crearPago($tratamiento->id, ['metodo_pago_id' => $efectivo->id, 'monto' => 50.5, 'fecha_pago' => '2026-03-03']);
    crearPago($tratamiento->id, ['metodo_pago_id' => $qr->id, 'monto' => 25, 'fecha_pago' => '2026-03-31']);
    crearPago($tratamiento->id, ['metodo_pago_id' => $qr->id, 'monto' => 999, 'fecha_pago' => '2026-03-04', 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);
    crearPago($tratamiento->id, ['metodo_pago_id' => $qr->id, 'monto' => 999, 'fecha_pago' => '2026-04-01']);

    $datos = $this->servicio->estatico('ingresos', $this->periodo);

    expect($datos['filas'])->toBe([
        ['metodo_pago' => 'Efectivo', 'cantidad' => 2, 'total' => 150.5],
        ['metodo_pago' => 'QR', 'cantidad' => 1, 'total' => 25.0],
    ])->and($datos['totales'])->toBe(['Abonos' => 3, 'Total ingresos' => 175.5])
        ->and($datos['nombre_archivo'])->toBe('ingresos');
});

it('muestra el resultado en pantalla con formato boliviano y botones de exportar', function () {
    crearPago(crearTratamientoAplicado()->id, ['metodo_pago_id' => crearMetodoPago(['nombre' => 'Efectivo'])->id, 'monto' => 1250, 'fecha_pago' => '2026-03-02']);

    $this->actingAs($this->usuario)
        ->get(route('reportes.financieros', estatico('ingresos')))
        ->assertOk()
        ->assertSee('Ingresos por periodo y método de pago')
        ->assertSee('01/03/2026 al 31/03/2026')
        ->assertSee('Efectivo')
        ->assertSee('Bs 1.250,00')
        ->assertSee('Exportar PDF')
        ->assertSee('Exportar Excel');
});

it('dinámico clínico aplica cada filtro y devuelve solo las columnas marcadas', function (string $filtro) {
    $tratamiento = crearTratamiento(['nombre' => 'Xilofono']);
    $elegido = crearTratamientoAplicado([
        'tratamiento_id' => $tratamiento->id,
        'odontologo_id' => $this->usuario->id,
        'estado' => 'en proceso',
        'fecha_inicio' => '2026-03-05',
        'costo_acordado' => 200,
    ]);
    crearTratamientoAplicado(['tratamiento_id' => crearTratamiento(['nombre' => 'Quetzal'])->id, 'fecha_inicio' => '2026-03-06']);
    crearSesion($elegido->id);

    $valores = [
        'odontologo_id' => $this->usuario->id,
        'especialidad_id' => $tratamiento->especialidad_id,
        'tratamiento_id' => $tratamiento->id,
        'estado' => 'en proceso',
    ];

    $resultado = $this->actingAs($this->usuario)
        ->get(route('reportes.clinicos', dinamico(['tratamiento', 'costo_acordado', 'sesiones_realizadas'], [$filtro => $valores[$filtro]])))
        ->assertOk()
        ->viewData('resultado');

    expect($resultado['filas'])->toBe([
        ['tratamiento' => 'Xilofono', 'costo_acordado' => 200.0, 'sesiones_realizadas' => 1.0],
    ])->and(array_keys($resultado['columnas']))->toBe(['tratamiento', 'costo_acordado', 'sesiones_realizadas'])
        ->and($resultado['totales'])->toBe(['Registros' => 1, 'Costo acordado' => 200.0]);
})->with(['odontologo_id', 'especialidad_id', 'tratamiento_id', 'estado']);

it('dinámico clínico filtra por periodo y sin costo no suma', function () {
    crearTratamientoAplicado(['fecha_inicio' => '2026-03-05']);
    crearTratamientoAplicado(['fecha_inicio' => '2026-05-05']);

    $resultado = $this->actingAs($this->usuario)
        ->get(route('reportes.clinicos', dinamico(['fecha_inicio', 'pieza'])))
        ->viewData('resultado');

    expect($resultado['filas'])->toBe([['pieza' => '—', 'fecha_inicio' => '05/03/2026']])
        ->and($resultado['totales'])->toBe(['Registros' => 1]);
});

it('dinámico de agenda aplica cada filtro y devuelve solo las columnas marcadas', function (string $filtro) {
    $paciente = crearPaciente(['nombres' => 'Ana', 'apellidos' => 'Zeballos']);

    crearCita(['paciente_id' => $paciente->id, 'odontologo_id' => $this->usuario->id, 'fecha' => '2026-03-10', 'duracion_minutos' => 45, 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['fecha' => '2026-03-11']);
    crearCita(['paciente_id' => $paciente->id, 'odontologo_id' => $this->usuario->id, 'fecha' => '2026-05-10', 'estado' => EstadoCita::ATENDIDA]);

    $valores = [
        'odontologo_id' => $this->usuario->id,
        'paciente_id' => $paciente->id,
        'estado_cita_id' => EstadoCita::idDe(EstadoCita::ATENDIDA),
    ];

    $resultado = $this->actingAs($this->usuario)
        ->get(route('reportes.agenda', dinamico(['paciente', 'hora_fin', 'duracion_minutos', 'estado'], [$filtro => $valores[$filtro]])))
        ->viewData('resultado');

    expect($resultado['filas'])->toBe([
        ['hora_fin' => '09:45', 'duracion_minutos' => 45.0, 'paciente' => 'Ana Zeballos', 'estado' => 'Atendida'],
    ])->and($resultado['totales'])->toBe(['Registros' => 1]);
})->with(['odontologo_id', 'paciente_id', 'estado_cita_id']);

it('dinámico financiero aplica cada filtro y suma solo los montos vigentes', function (string $filtro) {
    $tratamiento = crearTratamientoAplicado();
    $qr = crearMetodoPago(['nombre' => 'QR']);

    crearPago($tratamiento->id, ['monto' => 80, 'fecha_pago' => '2026-03-05', 'metodo_pago_id' => $qr->id, 'odontologo_id' => $this->usuario->id, 'numero_comprobante' => 'REC-0001', 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);
    crearPago($tratamiento->id, ['monto' => 30, 'fecha_pago' => '2026-03-06', 'numero_comprobante' => 'REC-0002']);

    $valores = ['odontologo_id' => $this->usuario->id, 'metodo_pago_id' => $qr->id, 'estado' => 'anulado'];

    $resultado = $this->actingAs($this->usuario)
        ->get(route('reportes.financieros', dinamico(['comprobante', 'monto', 'motivo_anulacion'], [$filtro => $valores[$filtro]])))
        ->viewData('resultado');

    expect($resultado['filas'])->toBe([
        ['comprobante' => 'REC-0001', 'monto' => 80.0, 'motivo_anulacion' => 'Error'],
    ])->and($resultado['totales'])->toBe(['Registros' => 1, 'Monto vigente' => 0.0]);
})->with(['odontologo_id', 'metodo_pago_id', 'estado']);

it('dinámico financiero sin filtros suma los vigentes aunque no se marque el estado', function () {
    $tratamiento = crearTratamientoAplicado();
    crearPago($tratamiento->id, ['monto' => 80, 'fecha_pago' => '2026-03-05', 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);
    crearPago($tratamiento->id, ['monto' => 30, 'fecha_pago' => '2026-03-06']);

    $resultado = $this->actingAs($this->usuario)
        ->get(route('reportes.financieros', dinamico(['monto'])))
        ->viewData('resultado');

    expect($resultado['filas'])->toHaveCount(2)
        ->and($resultado['totales'])->toBe(['Registros' => 2, 'Monto vigente' => 30.0]);
});

it('rechaza una columna fuera de la lista blanca', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.clinicos'))
        ->get(route('reportes.clinicos', dinamico(['paciente', 'password'])))
        ->assertRedirect(route('reportes.clinicos'))
        ->assertSessionHasErrors(['columnas.1' => 'La columna elegida no es válida.']);
});

it('rechaza un tipo de reporte de otra familia', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.clinicos'))
        ->get(route('reportes.clinicos', estatico('ingresos')))
        ->assertSessionHasErrors(['tipo' => 'El reporte indicado no es válido.']);
});

it('A1: rechaza una fecha inicial posterior a la final', function (array $parametros) {
    $this->actingAs($this->usuario)
        ->from(route('reportes.agenda'))
        ->get(route('reportes.agenda', ['fecha_inicio' => '2026-04-01', 'fecha_fin' => '2026-03-01'] + $parametros))
        ->assertSessionHasErrors(['fecha_inicio' => 'La fecha inicial no puede ser posterior a la fecha final.']);
})->with([
    'estático' => [['modo' => 'estatico', 'tipo' => 'agenda_citas']],
    'dinámico' => [['modo' => 'dinamico', 'columnas' => ['fecha']]],
]);

it('A1: rechaza un dinámico sin columnas', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.agenda'))
        ->get(route('reportes.agenda', ['modo' => 'dinamico', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']))
        ->assertSessionHasErrors(['columnas' => 'Debe seleccionar al menos una columna.']);
});

it('sin datos muestra el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->get(route('reportes.financieros', estatico('ingresos')))
        ->assertOk()
        ->assertSee('No hay registros para los filtros elegidos');
});
