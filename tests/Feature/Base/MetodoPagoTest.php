<?php

use App\Models\Bitacora;
use App\Models\MetodoPago;
use App\Services\BitacoraService;

/*
 * CU37 Gestionar métodos de pago. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un método de pago; $cambios reemplaza algunos.
function datosMetodoPago(array $cambios = []): array
{
    return $cambios + [
        'nombre' => 'Transferencia',
        'descripcion' => 'Transferencia bancaria.',
    ];
}

it('registra un método de pago válido como activo', function () {
    $this->actingAs($this->admin)
        ->post(route('metodos-pago.guardar'), datosMetodoPago())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Método de pago registrado correctamente.');

    $registro = MetodoPago::where('nombre', 'Transferencia')->first();

    expect($registro)->not->toBeNull()
        ->and($registro->estado)->toBe('activo');
});

it('rechaza un nombre duplicado', function () {
    crearMetodoPago(['nombre' => 'Transferencia']);

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.guardar'), datosMetodoPago())
        ->assertSessionHasErrors(['nombre' => 'Ya existe un método de pago con ese nombre.']);

    expect(MetodoPago::count())->toBe(1);
});

it('permite modificar un método de pago conservando su propio nombre', function () {
    $registro = crearMetodoPago(['nombre' => 'Transferencia']);

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.modificar'), datosMetodoPago(['id' => $registro->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Método de pago actualizado correctamente.');

    expect($registro->fresh()->descripcion)->toBe('Texto nuevo')
        ->and($registro->fresh()->estado)->toBe('activo');
});

it('rechaza nombre de más de 30 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('metodos-pago.guardar'), datosMetodoPago(['nombre' => str_repeat('x', 31)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 30 caracteres.']);

    expect(MetodoPago::count())->toBe(0);
});

it('da de baja cambiando el estado sin borrar el registro', function () {
    $registro = crearMetodoPago();

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.eliminar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Método de pago dado de baja.');

    expect(MetodoPago::find($registro->id))->not->toBeNull()
        ->and($registro->fresh()->estado)->toBe('inactivo');
});

it('no da de baja un método de pago ya inactivo', function () {
    $registro = crearMetodoPago(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.eliminar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El método de pago ya está dado de baja.']);

    expect(Bitacora::where('accion', BitacoraService::BAJA_METODO_PAGO)->count())->toBe(0);
});

it('habilita un método de pago dado de baja', function () {
    $registro = crearMetodoPago(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.habilitar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Método de pago habilitado.');

    expect($registro->fresh()->estado)->toBe('activo');
});

it('no habilita un método de pago ya activo', function () {
    $registro = crearMetodoPago();

    $this->actingAs($this->admin)
        ->post(route('metodos-pago.habilitar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El método de pago ya está activo.']);

    expect(Bitacora::where('accion', BitacoraService::HABILITAR_METODO_PAGO)->count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('metodos-pago.guardar'), datosMetodoPago());

    $registro = MetodoPago::first();

    $this->actingAs($this->admin)->post(route('metodos-pago.modificar'), datosMetodoPago(['id' => $registro->id]));
    $this->actingAs($this->admin)->post(route('metodos-pago.eliminar'), ['id' => $registro->id]);
    $this->actingAs($this->admin)->post(route('metodos-pago.habilitar'), ['id' => $registro->id]);

    $acciones = Bitacora::where('tabla_afectada', 'metodos_pago')
        ->where('registro_id', $registro->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_METODO_PAGO,
        BitacoraService::MODIFICAR_METODO_PAGO,
        BitacoraService::BAJA_METODO_PAGO,
        BitacoraService::HABILITAR_METODO_PAGO,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('metodos-pago.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('metodos-pago.guardar'), datosMetodoPago())
        ->assertForbidden();

    expect(MetodoPago::count())->toBe(0);
});

it('muestra la lista y busca por nombre', function () {
    crearMetodoPago(['nombre' => 'Xilofono']);
    crearMetodoPago(['nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('metodos-pago.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('muestra el detalle', function () {
    $registro = crearMetodoPago(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('metodos-pago.mostrar', $registro->id))
        ->assertOk()
        ->assertSee('Xilofono');
});
