<?php

use App\Models\Bitacora;
use App\Models\TipoEstudio;
use App\Services\BitacoraService;

/*
 * CU18 Gestionar tipos de estudio. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un tipo de estudio; $cambios reemplaza algunos.
function datosTipoEstudio(array $cambios = []): array
{
    return $cambios + [
        'nombre' => 'Radiografía panorámica',
        'descripcion' => 'Vista general de ambas arcadas.',
    ];
}

it('registra un tipo de estudio válido como activo', function () {
    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.guardar'), datosTipoEstudio())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Tipo de estudio registrado correctamente.');

    $registro = TipoEstudio::where('nombre', 'Radiografía panorámica')->first();

    expect($registro)->not->toBeNull()
        ->and($registro->estado)->toBe('activo');
});

it('rechaza un nombre duplicado', function () {
    crearTipoEstudio(['nombre' => 'Radiografía panorámica']);

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.guardar'), datosTipoEstudio())
        ->assertSessionHasErrors(['nombre' => 'Ya existe un tipo de estudio con ese nombre.']);

    expect(TipoEstudio::count())->toBe(1);
});

it('permite modificar un tipo de estudio conservando su propio nombre', function () {
    $registro = crearTipoEstudio(['nombre' => 'Radiografía panorámica']);

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.modificar'), datosTipoEstudio(['id' => $registro->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Tipo de estudio actualizado correctamente.');

    expect($registro->fresh()->descripcion)->toBe('Texto nuevo')
        ->and($registro->fresh()->estado)->toBe('activo');
});

it('rechaza nombre de más de 50 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.guardar'), datosTipoEstudio(['nombre' => str_repeat('x', 51)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 50 caracteres.']);

    expect(TipoEstudio::count())->toBe(0);
});

it('da de baja cambiando el estado sin borrar el registro', function () {
    $registro = crearTipoEstudio();

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.eliminar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Tipo de estudio dado de baja.');

    expect(TipoEstudio::find($registro->id))->not->toBeNull()
        ->and($registro->fresh()->estado)->toBe('inactivo');
});

it('no da de baja un tipo de estudio ya inactivo', function () {
    $registro = crearTipoEstudio(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.eliminar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El tipo de estudio ya está dado de baja.']);

    expect(Bitacora::where('accion', BitacoraService::BAJA_TIPO_ESTUDIO)->count())->toBe(0);
});

it('habilita un tipo de estudio dado de baja', function () {
    $registro = crearTipoEstudio(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.habilitar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Tipo de estudio habilitado.');

    expect($registro->fresh()->estado)->toBe('activo');
});

it('no habilita un tipo de estudio ya activo', function () {
    $registro = crearTipoEstudio();

    $this->actingAs($this->admin)
        ->post(route('tipos-estudio.habilitar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El tipo de estudio ya está activo.']);

    expect(Bitacora::where('accion', BitacoraService::HABILITAR_TIPO_ESTUDIO)->count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('tipos-estudio.guardar'), datosTipoEstudio());

    $registro = TipoEstudio::first();

    $this->actingAs($this->admin)->post(route('tipos-estudio.modificar'), datosTipoEstudio(['id' => $registro->id]));
    $this->actingAs($this->admin)->post(route('tipos-estudio.eliminar'), ['id' => $registro->id]);
    $this->actingAs($this->admin)->post(route('tipos-estudio.habilitar'), ['id' => $registro->id]);

    $acciones = Bitacora::where('tabla_afectada', 'tipos_estudio')
        ->where('registro_id', $registro->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_TIPO_ESTUDIO,
        BitacoraService::MODIFICAR_TIPO_ESTUDIO,
        BitacoraService::BAJA_TIPO_ESTUDIO,
        BitacoraService::HABILITAR_TIPO_ESTUDIO,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('tipos-estudio.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('tipos-estudio.guardar'), datosTipoEstudio())
        ->assertForbidden();

    expect(TipoEstudio::count())->toBe(0);
});

it('muestra la lista y busca por nombre', function () {
    crearTipoEstudio(['nombre' => 'Xilofono']);
    crearTipoEstudio(['nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('tipos-estudio.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('muestra el detalle', function () {
    $registro = crearTipoEstudio(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('tipos-estudio.mostrar', $registro->id))
        ->assertOk()
        ->assertSee('Xilofono');
});
