<?php

use App\Models\Bitacora;
use App\Models\Pago;
use App\Services\BitacoraService;
use App\Services\PagoService;

/*
 * CU35 Anular abono (RNF-06). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->tratamiento = crearTratamientoAplicado(['costo_acordado' => 1000]);
    $this->pago = crearPago($this->tratamiento->id, ['monto' => 300]);
});

it('anula con motivo sin borrar el registro', function () {
    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => 'Monto mal digitado'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Abono anulado. El saldo fue recalculado.');

    expect(Pago::find($this->pago->id))->not->toBeNull()
        ->and($this->pago->fresh()->estado)->toBe('anulado')
        ->and($this->pago->fresh()->motivo_anulacion)->toBe('Monto mal digitado');

    $acciones = Bitacora::where('tabla_afectada', 'pagos')
        ->where('registro_id', $this->pago->id)
        ->where('usuario_id', $this->usuario->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::ANULAR_ABONO]);
});

it('el saldo aumenta en el monto anulado', function () {
    $servicio = app(PagoService::class);

    expect($servicio->saldoPendiente($this->tratamiento))->toEqual(700);

    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => 'Error']);

    expect($servicio->saldoPendiente($this->tratamiento))->toEqual(1000);
});

it('A1: exige el motivo', function () {
    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => ''])
        ->assertSessionHasErrors(['motivo_anulacion' => 'Debe indicar el motivo de la anulación.']);

    expect($this->pago->fresh()->estado)->toBe('vigente');
});

it('A1: rechaza un motivo de más de 150 caracteres', function () {
    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => str_repeat('x', 151)])
        ->assertSessionHasErrors(['motivo_anulacion' => 'El motivo no puede superar los 150 caracteres.']);

    expect($this->pago->fresh()->estado)->toBe('vigente');
});

it('A1: no anula un abono ya anulado', function () {
    $this->pago->update(['estado' => 'anulado', 'motivo_anulacion' => 'Primero']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => 'Segundo'])
        ->assertSessionHasErrors(['operacion' => 'El abono ya está anulado.']);

    expect($this->pago->fresh()->motivo_anulacion)->toBe('Primero')
        ->and(Bitacora::where('accion', BitacoraService::ANULAR_ABONO)->count())->toBe(0);
});

it('el comprobante de un abono anulado no se reutiliza', function () {
    $this->pago->update(['numero_comprobante' => 'REC-0001']);

    $this->actingAs($this->usuario)
        ->post(route('pagos.anular'), ['id' => $this->pago->id, 'motivo_anulacion' => 'Error']);

    expect(app(PagoService::class)->generarComprobante())->toBe('REC-0002');
});
