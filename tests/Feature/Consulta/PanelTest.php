<?php

use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\TratamientoAplicado;
use App\Services\PagoService;
use App\Services\PanelService;
use Database\Seeders\EstadoCitaSeeder;
use Illuminate\Support\Carbon;

/*
 * Dashboard del Panel (indicadores y gráficos). Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed(EstadoCitaSeeder::class);
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->admin = crearUsuario('administrador');
    $this->yo = crearUsuario('odontologo');
    $this->otro = crearUsuario('odontologo');
    $this->servicio = app(PanelService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

// Datos de prueba de los seis indicadores. Saldo esperado: 200 + 0 + 200 + 50 = Bs 450.
function cargarDatosPanel($test): void
{
    // Citas: 2 programadas hoy (una de cada odontólogo); cancelada hoy y programada mañana no cuentan.
    crearCita(['odontologo_id' => $test->yo->id, 'fecha' => '2026-11-20', 'hora_inicio' => '09:00']);
    crearCita(['odontologo_id' => $test->otro->id, 'fecha' => '2026-11-20', 'hora_inicio' => '11:00']);
    crearCita(['fecha' => '2026-11-20', 'hora_inicio' => '15:00', 'estado' => EstadoCita::CANCELADA]);
    crearCita(['fecha' => '2026-11-21']);

    // Pacientes atendidos en noviembre: Ana dos veces (yo) y Luis (otro); Eva en octubre no cuenta.
    $ana = crearPaciente(['nombres' => 'Ana']);
    crearCita(['paciente_id' => $ana->id, 'odontologo_id' => $test->yo->id, 'fecha' => '2026-11-03', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['paciente_id' => $ana->id, 'odontologo_id' => $test->yo->id, 'fecha' => '2026-11-10', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['odontologo_id' => $test->otro->id, 'fecha' => '2026-11-05', 'estado' => EstadoCita::ATENDIDA]);
    crearCita(['odontologo_id' => $test->yo->id, 'fecha' => '2026-10-28', 'estado' => EstadoCita::ATENDIDA]);

    // Tratamientos: dos en proceso míos (uno con sesiones pendientes), uno en proceso del otro (pendiente),
    // uno planificado y uno cancelado.
    $test->ta1 = crearTratamientoAplicado(['odontologo_id' => $test->yo->id, 'estado' => 'en proceso', 'sesiones_planificadas' => 3, 'costo_acordado' => 300]);
    crearSesion($test->ta1->id);
    crearPago($test->ta1->id, ['monto' => 100]);
    crearPago($test->ta1->id, ['monto' => 50, 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    $test->ta2 = crearTratamientoAplicado(['odontologo_id' => $test->yo->id, 'estado' => 'en proceso', 'sesiones_planificadas' => 1, 'costo_acordado' => 100]);
    crearSesion($test->ta2->id);
    crearPago($test->ta2->id, ['monto' => 100]);

    $test->ta3 = crearTratamientoAplicado(['odontologo_id' => $test->otro->id, 'estado' => 'en proceso', 'sesiones_planificadas' => 2, 'costo_acordado' => 200]);
    crearTratamientoAplicado(['odontologo_id' => $test->yo->id, 'estado' => 'planificado', 'costo_acordado' => 50]);
    crearTratamientoAplicado(['odontologo_id' => $test->otro->id, 'estado' => 'cancelado', 'costo_acordado' => 999]);

    // Avisos: dos sin leer míos, uno leído mío y uno sin leer del otro.
    crearAviso($test->yo->id);
    crearAviso($test->yo->id);
    crearAviso($test->yo->id, ['leido' => true]);
    crearAviso($test->otro->id);
}

it('los seis indicadores muestran los números correctos con datos de prueba', function () {
    cargarDatosPanel($this);
    crearAviso($this->admin->id);

    $respuesta = $this->actingAs($this->admin)->get(route('panel'))->assertOk();

    expect($respuesta->viewData('indicadores'))->toBe([
        'citas_hoy' => 2,
        'pacientes_mes' => 2,
        'tratamientos_en_proceso' => 3,
        'sesiones_pendientes' => 2,
        'saldo_por_cobrar' => 450.0,
        'avisos_no_leidos' => 1,
    ]);

    $respuesta->assertSeeInOrder([
        'Citas programadas para hoy',
        'Pacientes atendidos este mes',
        'Tratamientos en proceso',
        'Tratamientos con sesiones pendientes',
        'Bs 450,00', 'Saldo por cobrar',
        'Avisos sin leer',
        'Ingresos de los últimos 6 meses',
        'Tratamientos por especialidad',
    ])->assertSee(route('citas.listar'))
        ->assertSee(route('avisos.listar'))
        ->assertSee(route('reportes.financieros'));
});

it('el saldo por cobrar coincide con la suma de PagoService', function () {
    cargarDatosPanel($this);

    $pagos = app(PagoService::class);
    $suma = TratamientoAplicado::where('estado', '!=', 'cancelado')->get()
        ->sum(fn ($ta) => $pagos->saldoPendiente($ta));

    expect($this->servicio->indicadores($this->admin)['saldo_por_cobrar'])->toBe(round($suma, 2))
        ->and($suma)->toBe(450.0);
});

it('un indicador en cero se muestra igual', function () {
    $respuesta = $this->actingAs($this->yo)->get(route('panel'))->assertOk();

    expect($respuesta->viewData('indicadores'))->toBe([
        'citas_hoy' => 0,
        'pacientes_mes' => 0,
        'tratamientos_en_proceso' => 0,
        'sesiones_pendientes' => 0,
        'saldo_por_cobrar' => 0.0,
        'avisos_no_leidos' => 0,
    ]);

    $respuesta->assertSee('Citas programadas para hoy')
        ->assertSee('Avisos sin leer')
        ->assertSee('Bs 0,00')
        ->assertSee('<span class="tabular-nums">0</span>', false);
});

it('el odontólogo ve sus tratamientos y no los de otro odontólogo', function () {
    cargarDatosPanel($this);

    $mios = $this->servicio->indicadores($this->yo);
    $delOtro = $this->servicio->indicadores($this->otro);

    expect($mios)->toBe([
        'citas_hoy' => 2,
        'pacientes_mes' => 1,
        'tratamientos_en_proceso' => 2,
        'sesiones_pendientes' => 1,
        'saldo_por_cobrar' => 450.0,
        'avisos_no_leidos' => 2,
    ])->and($delOtro['tratamientos_en_proceso'])->toBe(1)
        ->and($delOtro['sesiones_pendientes'])->toBe(1)
        ->and($delOtro['pacientes_mes'])->toBe(1)
        ->and($delOtro['avisos_no_leidos'])->toBe(1)
        // Citas de hoy y saldo son del consultorio para todos.
        ->and($delOtro['citas_hoy'])->toBe(2)
        ->and($delOtro['saldo_por_cobrar'])->toBe(450.0);
});

it('los gráficos devuelven 6 meses y las especialidades con datos', function () {
    $endodoncia = Especialidad::factory()->create(['nombre' => 'Endodoncia']);
    $ortodoncia = Especialidad::factory()->create(['nombre' => 'Ortodoncia']);
    $cirugia = Especialidad::factory()->create(['nombre' => 'Cirugía']);

    $conducto = crearTratamiento(['especialidad_id' => $endodoncia->id]);
    $brackets = crearTratamiento(['especialidad_id' => $ortodoncia->id]);

    $ta = crearTratamientoAplicado(['tratamiento_id' => $conducto->id, 'odontologo_id' => $this->yo->id, 'costo_acordado' => 5000]);
    crearTratamientoAplicado(['tratamiento_id' => $conducto->id, 'odontologo_id' => $this->otro->id]);
    crearTratamientoAplicado(['tratamiento_id' => $brackets->id, 'odontologo_id' => $this->yo->id]);
    // Cancelado: Cirugía no aparece.
    crearTratamientoAplicado(['tratamiento_id' => crearTratamiento(['especialidad_id' => $cirugia->id])->id, 'estado' => 'cancelado']);

    crearPago($ta->id, ['monto' => 300, 'fecha_pago' => '2026-11-02']);
    crearPago($ta->id, ['monto' => 200.5, 'fecha_pago' => '2026-11-15']);
    crearPago($ta->id, ['monto' => 150, 'fecha_pago' => '2026-09-10']);
    crearPago($ta->id, ['monto' => 999, 'fecha_pago' => '2026-10-10', 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);
    // Fuera de la ventana de 6 meses (junio a noviembre).
    crearPago($ta->id, ['monto' => 777, 'fecha_pago' => '2026-05-31']);

    $graficos = $this->servicio->graficos($this->admin);

    expect($graficos['ingresos_meses'])->toHaveCount(6)
        ->and(array_column($graficos['ingresos_meses'], 'etiqueta'))->toBe(['Jun 2026', 'Jul 2026', 'Ago 2026', 'Sep 2026', 'Oct 2026', 'Nov 2026'])
        ->and(array_column($graficos['ingresos_meses'], 'monto'))->toBe([0.0, 0.0, 0.0, 150.0, 0.0, 500.5])
        ->and($graficos['tratamientos_especialidad'])->toBe([
            ['especialidad' => 'Endodoncia', 'cantidad' => 2],
            ['especialidad' => 'Ortodoncia', 'cantidad' => 1],
        ]);

    // El odontólogo solo cuenta sus tratamientos; los ingresos son de la caja compartida.
    $mios = $this->servicio->graficos($this->otro);

    expect($mios['tratamientos_especialidad'])->toBe([['especialidad' => 'Endodoncia', 'cantidad' => 1]])
        ->and(array_sum(array_column($mios['ingresos_meses'], 'monto')))->toBe(650.5);
});
