<?php

use App\Models\Bitacora;
use App\Models\Material;
use App\Services\BitacoraService;

/*
 * CU20 Gestionar materiales. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un material; $cambios reemplaza algunos.
function datosMaterial(array $cambios = []): array
{
    return $cambios + [
        'nombre' => 'Resina compuesta',
        'descripcion' => 'Material de restauración.',
        'categoria' => 'restaurador',
        'unidad_medida' => 'jeringa',
    ];
}

it('registra un material válido como activo', function () {
    $this->actingAs($this->admin)
        ->post(route('materiales.guardar'), datosMaterial())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material registrado correctamente.');

    $registro = Material::where('nombre', 'Resina compuesta')->first();

    expect($registro)->not->toBeNull()
        ->and($registro->estado)->toBe('activo');
});

it('rechaza un nombre duplicado', function () {
    crearMaterial(['nombre' => 'Resina compuesta']);

    $this->actingAs($this->admin)
        ->post(route('materiales.guardar'), datosMaterial())
        ->assertSessionHasErrors(['nombre' => 'Ya existe un material con ese nombre.']);

    expect(Material::count())->toBe(1);
});

it('permite modificar un material conservando su propio nombre', function () {
    $registro = crearMaterial(['nombre' => 'Resina compuesta']);

    $this->actingAs($this->admin)
        ->post(route('materiales.modificar'), datosMaterial(['id' => $registro->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material actualizado correctamente.');

    expect($registro->fresh()->descripcion)->toBe('Texto nuevo')
        ->and($registro->fresh()->estado)->toBe('activo');
});

it('rechaza nombre de más de 60 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('materiales.guardar'), datosMaterial(['nombre' => str_repeat('x', 61)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 60 caracteres.']);

    expect(Material::count())->toBe(0);
});

it('rechaza categoria de más de 20 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('materiales.guardar'), datosMaterial(['categoria' => str_repeat('x', 21)]))
        ->assertSessionHasErrors(['categoria' => 'La categoría no puede superar los 20 caracteres.']);

    expect(Material::count())->toBe(0);
});

it('rechaza unidad_medida de más de 15 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('materiales.guardar'), datosMaterial(['unidad_medida' => str_repeat('x', 16)]))
        ->assertSessionHasErrors(['unidad_medida' => 'La unidad de medida no puede superar los 15 caracteres.']);

    expect(Material::count())->toBe(0);
});

it('da de baja cambiando el estado sin borrar el registro', function () {
    $registro = crearMaterial();

    $this->actingAs($this->admin)
        ->post(route('materiales.eliminar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material dado de baja.');

    expect(Material::find($registro->id))->not->toBeNull()
        ->and($registro->fresh()->estado)->toBe('inactivo');
});

it('no da de baja un material ya inactivo', function () {
    $registro = crearMaterial(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('materiales.eliminar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El material ya está dado de baja.']);

    expect(Bitacora::where('accion', BitacoraService::BAJA_MATERIAL)->count())->toBe(0);
});

it('habilita un material dado de baja', function () {
    $registro = crearMaterial(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('materiales.habilitar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Material habilitado.');

    expect($registro->fresh()->estado)->toBe('activo');
});

it('no habilita un material ya activo', function () {
    $registro = crearMaterial();

    $this->actingAs($this->admin)
        ->post(route('materiales.habilitar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El material ya está activo.']);

    expect(Bitacora::where('accion', BitacoraService::HABILITAR_MATERIAL)->count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('materiales.guardar'), datosMaterial());

    $registro = Material::first();

    $this->actingAs($this->admin)->post(route('materiales.modificar'), datosMaterial(['id' => $registro->id]));
    $this->actingAs($this->admin)->post(route('materiales.eliminar'), ['id' => $registro->id]);
    $this->actingAs($this->admin)->post(route('materiales.habilitar'), ['id' => $registro->id]);

    $acciones = Bitacora::where('tabla_afectada', 'materiales')
        ->where('registro_id', $registro->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_MATERIAL,
        BitacoraService::MODIFICAR_MATERIAL,
        BitacoraService::BAJA_MATERIAL,
        BitacoraService::HABILITAR_MATERIAL,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('materiales.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('materiales.guardar'), datosMaterial())
        ->assertForbidden();

    expect(Material::count())->toBe(0);
});

it('muestra la lista y busca por nombre', function () {
    crearMaterial(['nombre' => 'Xilofono']);
    crearMaterial(['nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('materiales.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('muestra el detalle', function () {
    $registro = crearMaterial(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('materiales.mostrar', $registro->id))
        ->assertOk()
        ->assertSee('Xilofono');
});
