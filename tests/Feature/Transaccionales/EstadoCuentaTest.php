<?php

use App\Services\PagoService;

/*
 * CU36 Consultar estado de cuenta (RF-75). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->plan = crearPlan(['paciente_id' => $this->paciente->id]);
});

it('saldoPendiente descuenta solo los abonos vigentes', function () {
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 1000]);
    crearPago($tratamiento->id, ['monto' => 300]);
    crearPago($tratamiento->id, ['monto' => 150.50]);
    crearPago($tratamiento->id, ['monto' => 200, 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    expect(app(PagoService::class)->saldoPendiente($tratamiento))->toBe(549.5);
});

it('validarMonto acepta el saldo exacto y rechaza un centavo más', function () {
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 0.30]);
    crearPago($tratamiento->id, ['monto' => 0.10]);

    $servicio = app(PagoService::class);

    expect($servicio->validarMonto($tratamiento, '0.20'))->toBeTrue()
        ->and($servicio->validarMonto($tratamiento, 0.2))->toBeTrue()
        ->and($servicio->validarMonto($tratamiento, '0.21'))->toBeFalse();
});

it('generarComprobante da el siguiente correlativo con cuatro dígitos', function () {
    expect(app(PagoService::class)->generarComprobante())->toBe('REC-0001');

    $tratamiento = crearTratamientoAplicado();
    crearPago($tratamiento->id, ['numero_comprobante' => 'REC-0062']);

    expect(app(PagoService::class)->generarComprobante())->toBe('REC-0063');
});

it('generarComprobante cuenta los anulados', function () {
    $tratamiento = crearTratamientoAplicado();
    crearPago($tratamiento->id, ['numero_comprobante' => 'REC-0005']);
    crearPago($tratamiento->id, ['numero_comprobante' => 'REC-0009', 'estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    expect(app(PagoService::class)->generarComprobante())->toBe('REC-0010');
});

it('generarComprobante sigue sin recortar desde 9999 y compara como número', function () {
    $tratamiento = crearTratamientoAplicado();
    crearPago($tratamiento->id, ['numero_comprobante' => 'REC-9999']);
    expect(app(PagoService::class)->generarComprobante())->toBe('REC-10000');

    crearPago($tratamiento->id, ['numero_comprobante' => 'REC-10000']);
    expect(app(PagoService::class)->generarComprobante())->toBe('REC-10001');
});

it('muestra costo, abonado, saldo y totales de los tratamientos no cancelados', function () {
    $primero = crearTratamientoAplicado([
        'plan_tratamiento_id' => $this->plan->id,
        'tratamiento_id' => crearTratamiento(['nombre' => 'Xilofono'])->id,
        'costo_acordado' => 1250,
    ]);
    crearPago($primero->id, ['monto' => 250]);
    crearPago($primero->id, ['monto' => 100, 'estado' => 'anulado', 'motivo_anulacion' => 'Duplicado']);

    crearTratamientoAplicado([
        'plan_tratamiento_id' => $this->plan->id,
        'tratamiento_id' => crearTratamiento(['nombre' => 'Quetzal'])->id,
        'costo_acordado' => 500,
    ]);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', $this->paciente->id))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertSee('Quetzal')
        ->assertSee('Bs 1.250,00')
        ->assertSee('Bs 1.000,00')
        ->assertSee('Duplicado');

    expect($respuesta->viewData('totalCostos'))->toEqual(1750)
        ->and($respuesta->viewData('totalAbonado'))->toEqual(250)
        ->and($respuesta->viewData('saldoTotal'))->toEqual(1500);
});

it('no muestra los tratamientos cancelados ni los de otros pacientes', function () {
    crearTratamientoAplicado([
        'plan_tratamiento_id' => $this->plan->id,
        'tratamiento_id' => crearTratamiento(['nombre' => 'Xilofono'])->id,
        'estado' => 'cancelado',
    ]);
    crearTratamientoAplicado(['tratamiento_id' => crearTratamiento(['nombre' => 'Quetzal'])->id]);

    $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', $this->paciente->id))
        ->assertOk()
        ->assertDontSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('avisa si el paciente no tiene tratamientos', function () {
    $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', crearPaciente()->id))
        ->assertOk()
        ->assertSee('El paciente no tiene tratamientos con costo registrado')
        ->assertDontSee('Totales del paciente');
});

it('responde 404 si el paciente no existe', function () {
    $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', 999999))
        ->assertNotFound();
});

it('ofrece Registrar abono solo si hay saldo y el plan no está cancelado', function () {
    $conSaldo = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 100]);
    $pagado = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 100]);
    crearPago($pagado->id, ['monto' => 100]);

    $cancelado = crearPlan(['paciente_id' => $this->paciente->id, 'estado' => 'cancelado']);
    $enCancelado = crearTratamientoAplicado(['plan_tratamiento_id' => $cancelado->id, 'costo_acordado' => 100]);

    $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', $this->paciente->id))
        ->assertSee(route('pagos.crear', ['tratamiento_aplicado_id' => $conSaldo->id]), false)
        ->assertDontSee(route('pagos.crear', ['tratamiento_aplicado_id' => $pagado->id]), false)
        ->assertDontSee(route('pagos.crear', ['tratamiento_aplicado_id' => $enCancelado->id]), false);
});

it('solo los abonos vigentes ofrecen Anular', function () {
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id]);
    $vigente = crearPago($tratamiento->id);
    $anulado = crearPago($tratamiento->id, ['estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    $this->actingAs($this->usuario)
        ->get(route('pagos.cuenta', $this->paciente->id))
        ->assertSee('name="id" value="'.$vigente->id.'"', false)
        ->assertDontSee('name="id" value="'.$anulado->id.'"', false);
});

it('la ficha del paciente enlaza el estado de cuenta', function () {
    $this->actingAs($this->usuario)
        ->get(route('pacientes.mostrar', $this->paciente->id))
        ->assertSee(route('pagos.cuenta', $this->paciente->id), false);
});
