<?php

use App\Models\Bitacora;
use App\Models\Pago;
use App\Services\BitacoraService;
use App\Services\PagoService;

/*
 * CU34 Registrar abono (RF-75, RF-76). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->plan = crearPlan();
    $this->tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 1000]);
    $this->metodo = crearMetodoPago(['nombre' => 'Efectivo']);
});

// Datos válidos del abono; $cambios reemplaza algunos.
function datosAbono(int $tratamientoId, int $metodoId, array $cambios = []): array
{
    return $cambios + [
        'tratamiento_aplicado_id' => $tratamientoId,
        'monto' => '250.00',
        'fecha_pago' => today()->toDateString(),
        'metodo_pago_id' => $metodoId,
        'observaciones' => 'Primer abono',
    ];
}

it('muestra el saldo y solo los métodos activos', function () {
    crearPago($this->tratamiento->id, ['monto' => 400]);
    crearMetodoPago(['nombre' => 'Xilofono', 'estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->get(route('pagos.crear', ['tratamiento_aplicado_id' => $this->tratamiento->id]))
        ->assertOk()
        ->assertSee('Bs 1.000,00')
        ->assertSee('Bs 400,00')
        ->assertSee('Bs 600,00')
        ->assertSee('Efectivo')
        ->assertDontSee('Xilofono');
});

it('no abre el formulario sin saldo disponible', function (string $caso) {
    $tratamiento = $this->tratamiento;

    match ($caso) {
        'tratamiento cancelado' => $tratamiento->update(['estado' => 'cancelado']),
        'plan cancelado' => $this->plan->update(['estado' => 'cancelado']),
        'saldo 0' => crearPago($tratamiento->id, ['monto' => 1000]),
    };

    $this->actingAs($this->usuario)
        ->get(route('pagos.crear', ['tratamiento_aplicado_id' => $tratamiento->id]))
        ->assertRedirect(route('pagos.cuenta', $this->plan->paciente_id))
        ->assertSessionHasErrors(['operacion' => 'El tratamiento no tiene saldo pendiente para registrar abonos.']);
})->with(['tratamiento cancelado', 'plan cancelado', 'saldo 0']);

it('registra el abono vigente con comprobante correlativo', function () {
    crearPago($this->tratamiento->id, ['monto' => 10, 'numero_comprobante' => 'REC-0062']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('pagos.cuenta', $this->plan->paciente_id))
        ->assertSessionHas('exito', 'Abono registrado.');

    $pago = Pago::where('numero_comprobante', 'REC-0063')->first();

    expect($pago)->not->toBeNull()
        ->and($pago->estado)->toBe('vigente')
        ->and($pago->monto)->toBe('250.00')
        ->and($pago->odontologo_id)->toBe($this->usuario->id)
        ->and($pago->tratamiento_aplicado_id)->toBe($this->tratamiento->id);

    $acciones = Bitacora::where('tabla_afectada', 'pagos')
        ->where('registro_id', $pago->id)
        ->where('usuario_id', $this->usuario->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::REGISTRAR_ABONO]);
});

it('A1: rechaza datos no válidos junto al campo y conserva los datos', function (string $campo, $valor, string $mensaje) {
    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id, [$campo => $valor]))
        ->assertSessionHasErrors([$campo => $mensaje])
        ->assertSessionHasInput('observaciones', 'Primer abono');

    expect(Pago::count())->toBe(0);
})->with([
    'monto 0' => ['monto', 0, 'El monto debe ser mayor a 0.'],
    'monto negativo' => ['monto', -5, 'El monto debe ser mayor a 0.'],
    'monto vacío' => ['monto', '', 'El campo Monto es obligatorio.'],
    'sin método' => ['metodo_pago_id', '', 'Debe seleccionar el método de pago.'],
]);

it('A1: rechaza una fecha de pago futura', function () {
    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id, [
            'fecha_pago' => today()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['fecha_pago' => 'La fecha de pago no puede ser posterior a hoy.']);

    expect(Pago::count())->toBe(0);
});

it('A1: rechaza un método de pago inactivo', function () {
    $inactivo = crearMetodoPago(['estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $inactivo->id))
        ->assertSessionHasErrors(['metodo_pago_id' => 'El método de pago indicado no existe o está inactivo.']);

    expect(Pago::count())->toBe(0);
});

it('A2: rechaza un monto mayor al saldo con el formato boliviano', function () {
    crearPago($this->tratamiento->id, ['monto' => 600]);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id, ['monto' => 1600.5]))
        ->assertSessionHasErrors(['monto' => 'El monto (Bs 1.600,50) supera el saldo pendiente (Bs 400,00)'])
        ->assertSessionHasInput('monto', 1600.5);

    expect(Pago::count())->toBe(1)
        ->and(Bitacora::where('accion', BitacoraService::REGISTRAR_ABONO)->count())->toBe(0);
});

it('un monto igual al saldo deja el saldo en 0', function () {
    crearPago($this->tratamiento->id, ['monto' => 600]);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id, ['monto' => '400.00']))
        ->assertSessionHasNoErrors();

    expect(app(PagoService::class)->saldoPendiente($this->tratamiento))->toEqual(0);
});

it('rechaza abonar a un tratamiento o plan cancelado', function (string $caso) {
    $caso === 'plan'
        ? $this->plan->update(['estado' => 'cancelado'])
        : $this->tratamiento->update(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id))
        ->assertSessionHasErrors(['operacion' => 'El tratamiento no tiene saldo pendiente para registrar abonos.']);

    expect(Pago::count())->toBe(0);
})->with(['plan', 'tratamiento']);

it('un plan concluido admite abonos', function () {
    $this->plan->update(['estado' => 'concluido']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.guardar'), datosAbono($this->tratamiento->id, $this->metodo->id))
        ->assertSessionHasNoErrors();

    expect(Pago::count())->toBe(1);
});
