<?php

use App\Models\Bitacora;
use App\Models\TratamientoTipoEstudio;
use App\Services\BitacoraService;

/*
 * CU19 Definir estudios requeridos por tratamiento (RF-31). Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
    $this->tratamiento = crearTratamiento();
    $this->tipo = crearTipoEstudio(['nombre' => 'Radiografía periapical']);
});

// Datos válidos de un estudio requerido; $cambios reemplaza algunos.
function datosEstudioRequerido(int $tratamientoId, int $tipoId, array $cambios = []): array
{
    return $cambios + [
        'tratamiento_id' => $tratamientoId,
        'tipo_estudio_id' => $tipoId,
        'obligatorio' => 1,
        'momento_clinico' => 'previo al tratamiento',
        'observacion' => 'Antes de iniciar.',
    ];
}

it('lista solo los estudios del tratamiento', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);
    crearEstudioRequerido(crearTratamiento()->id, crearTipoEstudio(['nombre' => 'Xilofono'])->id, ['observacion' => 'De otro tratamiento']);

    $this->actingAs($this->admin)
        ->get(route('tratamientos.estudios', $this->tratamiento->id))
        ->assertOk()
        ->assertSee('Radiografía periapical')
        ->assertDontSee('De otro tratamiento');
});

it('el select solo ofrece tipos activos', function () {
    crearTipoEstudio(['nombre' => 'Quetzal', 'estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->get(route('tratamientos.estudios', $this->tratamiento->id))
        ->assertSee('Radiografía periapical')
        ->assertDontSee('Quetzal');
});

it('agrega un estudio requerido', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estudio requerido guardado.');

    $requerido = TratamientoTipoEstudio::first();

    expect($requerido->tratamiento_id)->toBe($this->tratamiento->id)
        ->and($requerido->tipo_estudio_id)->toBe($this->tipo->id)
        ->and((bool) $requerido->obligatorio)->toBeTrue()
        ->and($requerido->momento_clinico)->toBe('previo al tratamiento');
});

it('A1: rechaza tipo o momento sin seleccionar', function (string $campo) {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id, [$campo => '']))
        ->assertSessionHasErrors([$campo => 'Tipo de estudio o momento clínico sin seleccionar']);

    expect(TratamientoTipoEstudio::count())->toBe(0);
})->with(['tipo_estudio_id', 'momento_clinico']);

it('rechaza un momento clínico fuera de la lista', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id, ['momento_clinico' => 'otro']))
        ->assertSessionHasErrors(['momento_clinico' => 'El momento clínico indicado no es válido.']);

    expect(TratamientoTipoEstudio::count())->toBe(0);
});

it('A2: rechaza un tipo ya definido para el tratamiento', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id))
        ->assertSessionHasErrors(['tipo_estudio_id' => 'Este tipo de estudio ya está definido para el tratamiento'])
        ->assertSessionHasInput('observacion', 'Antes de iniciar.');

    expect(TratamientoTipoEstudio::count())->toBe(1);
});

it('el mismo tipo se puede asignar a otro tratamiento', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido(crearTratamiento()->id, $this->tipo->id))
        ->assertSessionHasNoErrors();

    expect(TratamientoTipoEstudio::count())->toBe(2);
});

it('modifica un estudio requerido conservando su propio tipo', function () {
    $requerido = crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.modificar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id, [
            'id' => $requerido->id,
            'obligatorio' => 0,
            'momento_clinico' => 'control posterior',
        ]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estudio requerido guardado.');

    expect((bool) $requerido->fresh()->obligatorio)->toBeFalse()
        ->and($requerido->fresh()->momento_clinico)->toBe('control posterior');
});

it('A2: no permite cambiar a un tipo ya asociado', function () {
    $otroTipo = crearTipoEstudio();
    crearEstudioRequerido($this->tratamiento->id, $otroTipo->id);
    $requerido = crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.modificar'), datosEstudioRequerido($this->tratamiento->id, $otroTipo->id, ['id' => $requerido->id]))
        ->assertSessionHasErrors(['tipo_estudio_id' => 'Este tipo de estudio ya está definido para el tratamiento']);

    expect($requerido->fresh()->tipo_estudio_id)->toBe($this->tipo->id);
});

it('quita un estudio requerido', function () {
    $requerido = crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.quitar'), ['id' => $requerido->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estudio requerido quitado.');

    expect(TratamientoTipoEstudio::find($requerido->id))->toBeNull();
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id));

    $requerido = TratamientoTipoEstudio::first();

    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.modificar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id, ['id' => $requerido->id]));
    $this->actingAs($this->admin)
        ->post(route('tratamientos.estudios.quitar'), ['id' => $requerido->id]);

    $acciones = Bitacora::where('tabla_afectada', 'tratamientos_tipos_estudio')
        ->where('registro_id', $requerido->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::ASIGNAR_ESTUDIO_REQUERIDO,
        BitacoraService::MODIFICAR_ESTUDIO_REQUERIDO,
        BitacoraService::QUITAR_ESTUDIO_REQUERIDO,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('tratamientos.estudios', $this->tratamiento->id))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('tratamientos.estudios.guardar'), datosEstudioRequerido($this->tratamiento->id, $this->tipo->id))
        ->assertForbidden();

    expect(TratamientoTipoEstudio::count())->toBe(0);
});
