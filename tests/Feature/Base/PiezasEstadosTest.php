<?php

use App\Models\Bitacora;
use App\Models\EstadoPieza;
use App\Models\PiezaDental;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\Route;

/*
 * CU22 Gestionar piezas dentales y estados de pieza. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un estado de pieza; $cambios reemplaza algunos.
function datosEstadoPieza(array $cambios = []): array
{
    return $cambios + [
        'codigo' => 'CARIES',
        'nombre' => 'Caries',
        'descripcion' => 'Pieza con caries.',
        'color_referencia' => '#E53935',
    ];
}

it('registra un estado de pieza válido como activo', function () {
    $this->actingAs($this->admin)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de pieza registrado correctamente.');

    $estado = EstadoPieza::where('codigo', 'CARIES')->first();

    expect($estado)->not->toBeNull()
        ->and($estado->estado)->toBe('activo')
        ->and($estado->color_referencia)->toBe('#E53935');
});

it('rechaza un código duplicado', function () {
    crearEstadoPieza(['codigo' => 'CARIES']);

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza())
        ->assertSessionHasErrors(['codigo' => 'Ya existe un estado de pieza con ese código.']);

    expect(EstadoPieza::count())->toBe(1);
});

it('permite modificar un estado de pieza conservando su propio código', function () {
    $estado = crearEstadoPieza(['codigo' => 'CARIES']);

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.modificar'), datosEstadoPieza(['id' => $estado->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de pieza actualizado correctamente.');

    expect($estado->fresh()->descripcion)->toBe('Texto nuevo')
        ->and($estado->fresh()->estado)->toBe('activo');
});

it('rechaza codigo de más de 10 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza(['codigo' => str_repeat('x', 11)]))
        ->assertSessionHasErrors(['codigo' => 'El código no puede superar los 10 caracteres.']);

    expect(EstadoPieza::count())->toBe(0);
});

it('rechaza nombre de más de 40 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza(['nombre' => str_repeat('x', 41)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 40 caracteres.']);

    expect(EstadoPieza::count())->toBe(0);
});

it('rechaza un color con formato inválido', function (string $color) {
    $this->actingAs($this->admin)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza(['color_referencia' => $color]))
        ->assertSessionHasErrors(['color_referencia' => 'El color de referencia debe tener el formato hexadecimal #RRGGBB.']);

    expect(EstadoPieza::count())->toBe(0);
})->with(['rojo', '#FFF', 'E53935', '#E5393G', '#E539355']);

it('da de baja cambiando el estado sin borrar el registro', function () {
    $estado = crearEstadoPieza();

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.eliminar'), ['id' => $estado->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de pieza dado de baja.');

    expect(EstadoPieza::find($estado->id))->not->toBeNull()
        ->and($estado->fresh()->estado)->toBe('inactivo');
});

it('no da de baja un estado de pieza ya inactivo', function () {
    $estado = crearEstadoPieza(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.eliminar'), ['id' => $estado->id])
        ->assertSessionHasErrors(['operacion' => 'El estado de pieza ya está dado de baja.']);

    expect(Bitacora::where('accion', BitacoraService::BAJA_ESTADO_PIEZA)->count())->toBe(0);
});

it('habilita un estado de pieza dado de baja', function () {
    $estado = crearEstadoPieza(['estado' => 'inactivo']);

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.habilitar'), ['id' => $estado->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de pieza habilitado.');

    expect($estado->fresh()->estado)->toBe('activo');
});

it('no habilita un estado de pieza ya activo', function () {
    $estado = crearEstadoPieza();

    $this->actingAs($this->admin)
        ->post(route('estados-pieza.habilitar'), ['id' => $estado->id])
        ->assertSessionHasErrors(['operacion' => 'El estado de pieza ya está activo.']);

    expect(Bitacora::where('accion', BitacoraService::HABILITAR_ESTADO_PIEZA)->count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('estados-pieza.guardar'), datosEstadoPieza());

    $estado = EstadoPieza::first();

    $this->actingAs($this->admin)->post(route('estados-pieza.modificar'), datosEstadoPieza(['id' => $estado->id]));
    $this->actingAs($this->admin)->post(route('estados-pieza.eliminar'), ['id' => $estado->id]);
    $this->actingAs($this->admin)->post(route('estados-pieza.habilitar'), ['id' => $estado->id]);

    $acciones = Bitacora::where('tabla_afectada', 'estados_pieza')
        ->where('registro_id', $estado->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_ESTADO_PIEZA,
        BitacoraService::MODIFICAR_ESTADO_PIEZA,
        BitacoraService::BAJA_ESTADO_PIEZA,
        BitacoraService::HABILITAR_ESTADO_PIEZA,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('estados-pieza.listar'))->assertForbidden();
    $this->actingAs($odontologo)->get(route('piezas-dentales.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('estados-pieza.guardar'), datosEstadoPieza())
        ->assertForbidden();

    expect(EstadoPieza::count())->toBe(0);
});

it('muestra la lista de estados y busca por nombre', function () {
    crearEstadoPieza(['nombre' => 'Xilofono']);
    crearEstadoPieza(['nombre' => 'Quetzal']);

    $this->actingAs($this->admin)
        ->get(route('estados-pieza.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('muestra el detalle de un estado de pieza', function () {
    $estado = crearEstadoPieza(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('estados-pieza.mostrar', $estado->id))
        ->assertOk()
        ->assertSee('Xilofono');
});

it('lista todas las piezas ordenadas por código FDI, sin paginar', function () {
    foreach ([48, 11, 21] as $fdi) {
        crearPiezaDental($fdi);
    }

    $respuesta = $this->actingAs($this->admin)
        ->get(route('piezas-dentales.listar'))
        ->assertOk()
        ->assertSeeInOrder(['Pieza 11', 'Pieza 21', 'Pieza 48']);

    expect($respuesta->viewData('piezas'))->toHaveCount(3);
});

it('muestra el detalle de una pieza por su código FDI', function () {
    $pieza = crearPiezaDental(36);

    expect($pieza->id)->toBe(36);

    $this->actingAs($this->admin)
        ->get(route('piezas-dentales.mostrar', 36))
        ->assertOk()
        ->assertSee('Pieza 36');
});

it('no existen rutas para crear, modificar ni eliminar piezas', function () {
    foreach (['guardar', 'modificar', 'eliminar', 'habilitar', 'crear'] as $accion) {
        expect(Route::has("piezas-dentales.{$accion}"))->toBeFalse();
    }

    expect(PiezaDental::count())->toBe(0);
});

it('enlaza las dos pestañas', function () {
    $this->actingAs($this->admin)
        ->get(route('estados-pieza.listar'))
        ->assertSee(route('piezas-dentales.listar'), false);

    $this->actingAs($this->admin)
        ->get(route('piezas-dentales.listar'))
        ->assertSee(route('estados-pieza.listar'), false);
});

it('marca Piezas y estados como opción activa en las dos pestañas', function (string $ruta) {
    $html = $this->actingAs($this->admin)->get(route($ruta))->getContent();

    expect($html)->toMatch('/<a[^>]*font-semibold[^>]*estados-pieza"|<a[^>]*estados-pieza"[^>]*font-semibold/s');
})->with(['estados-pieza.listar', 'piezas-dentales.listar']);
