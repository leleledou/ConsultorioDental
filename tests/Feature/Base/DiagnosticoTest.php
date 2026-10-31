<?php

use App\Models\Bitacora;
use App\Models\Diagnostico;
use App\Services\BitacoraService;

/*
 * CU17 Gestionar diagnósticos. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un diagnóstico; $cambios reemplaza algunos.
function datosDiagnostico(array $cambios = []): array
{
    return $cambios + [
        'codigo' => 'K02.1',
        'nombre' => 'Caries de la dentina',
        'descripcion' => 'Lesión que alcanza la dentina.',
    ];
}

it('registra un diagnóstico válido como activo', function () {
    $this->actingAs($this->admin)
        ->post(route('diagnosticos.guardar'), datosDiagnostico())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Diagnóstico registrado correctamente.');

    $registro = Diagnostico::where('codigo', 'K02.1')->first();

    expect($registro)->not->toBeNull()
        ->and($registro->estado)->toBe('activo');
});

it('rechaza un código duplicado', function () {
    crearDiagnostico(['codigo' => 'K02.1']);

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.guardar'), datosDiagnostico())
        ->assertSessionHasErrors(['codigo' => 'Ya existe un diagnóstico con ese código.']);

    expect(Diagnostico::count())->toBe(1);
});

it('permite modificar un diagnóstico conservando su propio código', function () {
    $registro = crearDiagnostico(['codigo' => 'K02.1']);

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.modificar'), datosDiagnostico(['id' => $registro->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Diagnóstico actualizado correctamente.');

    expect($registro->fresh()->descripcion)->toBe('Texto nuevo')
        ->and($registro->fresh()->estado)->toBe('activo');
});

it('rechaza codigo de más de 10 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('diagnosticos.guardar'), datosDiagnostico(['codigo' => str_repeat('x', 11)]))
        ->assertSessionHasErrors(['codigo' => 'El código no puede superar los 10 caracteres.']);

    expect(Diagnostico::count())->toBe(0);
});

it('rechaza nombre de más de 60 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('diagnosticos.guardar'), datosDiagnostico(['nombre' => str_repeat('x', 61)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 60 caracteres.']);

    expect(Diagnostico::count())->toBe(0);
});

it('da de baja cambiando el estado sin borrar el registro', function () {
    $registro = crearDiagnostico();

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.eliminar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Diagnóstico dado de baja.');

    expect(Diagnostico::find($registro->id))->not->toBeNull()
        ->and($registro->fresh()->estado)->toBe('inactivo');
});

it('no da de baja un diagnóstico ya inactivo', function () {
    $registro = crearDiagnostico(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.eliminar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El diagnóstico ya está dado de baja.']);

    expect(Bitacora::where('accion', BitacoraService::BAJA_DIAGNOSTICO)->count())->toBe(0);
});

it('habilita un diagnóstico dado de baja', function () {
    $registro = crearDiagnostico(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.habilitar'), ['id' => $registro->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Diagnóstico habilitado.');

    expect($registro->fresh()->estado)->toBe('activo');
});

it('no habilita un diagnóstico ya activo', function () {
    $registro = crearDiagnostico();

    $this->actingAs($this->admin)
        ->post(route('diagnosticos.habilitar'), ['id' => $registro->id])
        ->assertSessionHasErrors(['operacion' => 'El diagnóstico ya está activo.']);

    expect(Bitacora::where('accion', BitacoraService::HABILITAR_DIAGNOSTICO)->count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('diagnosticos.guardar'), datosDiagnostico());

    $registro = Diagnostico::first();

    $this->actingAs($this->admin)->post(route('diagnosticos.modificar'), datosDiagnostico(['id' => $registro->id]));
    $this->actingAs($this->admin)->post(route('diagnosticos.eliminar'), ['id' => $registro->id]);
    $this->actingAs($this->admin)->post(route('diagnosticos.habilitar'), ['id' => $registro->id]);

    $acciones = Bitacora::where('tabla_afectada', 'diagnosticos')
        ->where('registro_id', $registro->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_DIAGNOSTICO,
        BitacoraService::MODIFICAR_DIAGNOSTICO,
        BitacoraService::BAJA_DIAGNOSTICO,
        BitacoraService::HABILITAR_DIAGNOSTICO,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('diagnosticos.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('diagnosticos.guardar'), datosDiagnostico())
        ->assertForbidden();

    expect(Diagnostico::count())->toBe(0);
});

it('muestra la lista y busca por nombre', function () {
    crearDiagnostico(['nombre' => 'Xilofono']);
    crearDiagnostico(['nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('diagnosticos.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('busca también por código', function () {
    crearDiagnostico(['codigo' => 'K99', 'nombre' => 'Xilofono']);
    crearDiagnostico(['codigo' => 'M11', 'nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('diagnosticos.listar', ['buscar' => 'K99']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('muestra el detalle', function () {
    $registro = crearDiagnostico(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('diagnosticos.mostrar', $registro->id))
        ->assertOk()
        ->assertSee('Xilofono');
});
