<?php

use App\Models\Bitacora;
use App\Models\SesionMaterial;
use App\Services\BitacoraService;

/*
 * CU30 Registrar sesión de evolución: materiales de la sesión (RF-45, RF-46). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->sesion = crearSesion(crearTratamientoAplicado()->id);
    $this->material = crearMaterial(['nombre' => 'Hipoclorito', 'unidad_medida' => 'ml']);
});

it('agrega un material con cantidad', function () {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.guardar'), [
            'sesion_id' => $this->sesion->id,
            'material_id' => $this->material->id,
            'cantidad' => '2.5',
            'observacion' => 'Irrigación',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material registrado en la sesión.');

    $registro = SesionMaterial::first();

    expect((float) $registro->cantidad)->toBe(2.5)
        ->and($registro->observacion)->toBe('Irrigación')
        ->and(Bitacora::where('accion', BitacoraService::REGISTRAR_MATERIAL_SESION)->where('registro_id', $registro->id)->exists())->toBeTrue();
});

it('rechaza el mismo material dos veces con el mensaje exacto', function () {
    crearSesionMaterial($this->sesion->id, $this->material->id, 1);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.guardar'), [
            'sesion_id' => $this->sesion->id,
            'material_id' => $this->material->id,
            'cantidad' => 3,
        ])
        ->assertSessionHasErrors(['operacion' => 'Este material ya está registrado en la sesión. Modifique la cantidad del registro existente.']);

    expect(SesionMaterial::count())->toBe(1);
});

it('rechaza cantidad 0 o negativa', function (mixed $cantidad) {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.guardar'), [
            'sesion_id' => $this->sesion->id,
            'material_id' => $this->material->id,
            'cantidad' => $cantidad,
        ])
        ->assertSessionHasErrors(['cantidad' => 'La cantidad debe ser mayor a 0.']);

    expect(SesionMaterial::count())->toBe(0);
})->with([0, -1]);

it('modifica la cantidad', function () {
    $registro = crearSesionMaterial($this->sesion->id, $this->material->id, 1);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.modificar'), ['id' => $registro->id, 'cantidad' => '4', 'observacion' => 'Corrección'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material actualizado.');

    expect((float) $registro->fresh()->cantidad)->toBe(4.0)
        ->and($registro->fresh()->observacion)->toBe('Corrección')
        ->and(Bitacora::where('accion', BitacoraService::MODIFICAR_MATERIAL_SESION)->exists())->toBeTrue();
});

it('quita el material', function () {
    $registro = crearSesionMaterial($this->sesion->id, $this->material->id, 1);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.quitar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material quitado de la sesión.');

    expect(SesionMaterial::find($registro->id))->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::QUITAR_MATERIAL_SESION)->exists())->toBeTrue();
});

it('no permite tocar materiales de una sesión anulada', function () {
    $registro = crearSesionMaterial($this->sesion->id, $this->material->id, 1);
    $this->sesion->update(['estado' => 'anulada']);
    $mensaje = 'No se puede modificar una sesión anulada.';

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.guardar'), ['sesion_id' => $this->sesion->id, 'material_id' => crearMaterial()->id, 'cantidad' => 1])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.modificar'), ['id' => $registro->id, 'cantidad' => 9])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.material.quitar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    expect(SesionMaterial::count())->toBe(1)
        ->and((float) $registro->fresh()->cantidad)->toBe(1.0);
});

it('muestra la sesión con sus materiales', function () {
    crearSesionMaterial($this->sesion->id, $this->material->id, 2);

    $this->actingAs($this->usuario)
        ->get(route('sesiones.mostrar', $this->sesion->id))
        ->assertOk()
        ->assertSee('Hipoclorito')
        ->assertSee('ml');
});
