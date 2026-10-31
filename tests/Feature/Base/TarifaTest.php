<?php

use App\Models\Bitacora;
use App\Models\Tarifa;
use App\Services\BitacoraService;
use App\Services\TarifaService;
use Illuminate\Support\Facades\Route;

/*
 * CU16 Gestionar tarifas. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
    $this->tratamiento = crearTratamiento(['nombre' => 'Limpieza dental']);
});

// Datos válidos de un precio nuevo; $cambios reemplaza algunos.
function datosTarifa(int $tratamientoId, array $cambios = []): array
{
    return $cambios + [
        'tratamiento_id' => $tratamientoId,
        'precio' => '150.00',
        'fecha_inicio_vigencia' => '2026-03-01',
    ];
}

it('lista los tratamientos con su precio vigente o Sin tarifa', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 123.45, 'fecha_inicio_vigencia' => '2026-01-15']);
    crearTarifa($this->tratamiento->id, ['precio' => 99.00, 'fecha_inicio_vigencia' => '2025-01-01', 'fecha_fin_vigencia' => '2026-01-14', 'estado' => 'cerrada']);
    crearTratamiento(['nombre' => 'Sin precio aún']);

    $this->actingAs($this->admin)
        ->get(route('tarifas.listar'))
        ->assertOk()
        ->assertSee('123.45')
        ->assertSee('15/01/2026')
        ->assertDontSee('99.00')
        ->assertSee('Sin tarifa');
});

it('busca por nombre del tratamiento', function () {
    crearTratamiento(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('tarifas.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Limpieza dental');
});

it('registra el primer precio de un tratamiento sin tarifa', function () {
    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id))
        ->assertSessionHasNoErrors();

    $tarifa = Tarifa::first();

    expect(Tarifa::count())->toBe(1)
        ->and($tarifa->estado)->toBe('vigente')
        ->and($tarifa->precio)->toBe('150.00')
        ->and($tarifa->fecha_fin_vigencia)->toBeNull();
});

it('un precio nuevo cierra la vigente el día anterior y crea la nueva en la misma operación', function () {
    $anterior = crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2026-01-01']);

    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Precio registrado. La tarifa anterior quedó cerrada.');

    $anterior->refresh();
    $nueva = Tarifa::where('fecha_inicio_vigencia', '2026-03-01')->first();

    expect($anterior->estado)->toBe('cerrada')
        ->and($anterior->fecha_fin_vigencia->toDateString())->toBe('2026-02-28')
        ->and($nueva->estado)->toBe('vigente')
        ->and($this->tratamiento->tarifaVigente->id)->toBe($nueva->id);
});

it('A1: rechaza un precio no válido y no cambia nada', function ($precio) {
    $vigente = crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2026-01-01']);

    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id, ['precio' => $precio]))
        ->assertSessionHasErrors(['precio' => 'El precio debe ser mayor a 0'])
        ->assertSessionHasInput('fecha_inicio_vigencia', '2026-03-01');

    expect(Tarifa::count())->toBe(1)
        ->and($vigente->fresh()->estado)->toBe('vigente');
})->with(['cero' => 0, 'negativo' => -10, 'vacío' => '', 'texto' => 'abc']);

it('rechaza un precio con más de 2 decimales', function () {
    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id, ['precio' => '150.123']))
        ->assertSessionHasErrors(['precio' => 'El precio debe tener como máximo 2 decimales.']);

    expect(Tarifa::count())->toBe(0);
});

it('rechaza un precio mayor al de la columna', function () {
    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id, ['precio' => 100000000]))
        ->assertSessionHasErrors(['precio' => 'El precio no puede superar 99999999.99.']);

    expect(Tarifa::count())->toBe(0);
});

it('A2: rechaza una fecha igual o anterior al inicio de la vigente y la deja abierta', function (string $fecha) {
    $vigente = crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2026-01-15']);

    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => $fecha]))
        ->assertSessionHasErrors(['fecha_inicio_vigencia' => 'La nueva tarifa debe comenzar después del 15/01/2026'])
        ->assertSessionHasInput('precio', '150.00');

    expect(Tarifa::count())->toBe(1)
        ->and($vigente->fresh()->estado)->toBe('vigente')
        ->and($vigente->fresh()->fecha_fin_vigencia)->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::CREAR_TARIFA)->count())->toBe(0);
})->with(['igual' => '2026-01-15', 'anterior' => '2026-01-14']);

it('registra CREAR_TARIFA en la bitácora', function () {
    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id));

    $tarifa = Tarifa::first();

    $acciones = Bitacora::where('tabla_afectada', 'tarifas')
        ->where('registro_id', $tarifa->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::CREAR_TARIFA]);
});

it('muestra el historial ordenado por fecha descendente', function () {
    crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2024-01-01', 'fecha_fin_vigencia' => '2024-12-31', 'estado' => 'cerrada']);
    crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2026-01-01']);
    crearTarifa($this->tratamiento->id, ['fecha_inicio_vigencia' => '2025-01-01', 'fecha_fin_vigencia' => '2025-12-31', 'estado' => 'cerrada']);

    $this->actingAs($this->admin)
        ->get(route('tarifas.mostrar', $this->tratamiento->id))
        ->assertOk()
        ->assertSee('Limpieza dental')
        ->assertSeeInOrder(['01/01/2026', '01/01/2025', '01/01/2024']);
});

it('no existen rutas para modificar ni eliminar tarifas', function () {
    foreach (['modificar', 'eliminar', 'habilitar'] as $accion) {
        expect(Route::has("tarifas.{$accion}"))->toBeFalse();
    }
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('tarifas.listar'))->assertForbidden();
    $this->actingAs($odontologo)->get(route('tarifas.mostrar', $this->tratamiento->id))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id))
        ->assertForbidden();

    expect(Tarifa::count())->toBe(0);
});

it('precioVigente devuelve el precio de hoy', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 150, 'fecha_inicio_vigencia' => today()->subMonth()]);

    expect(app(TarifaService::class)->precioVigente($this->tratamiento->id, today()))->toBe('150.00');
});

it('precioVigente devuelve el precio de una fecha dentro de una tarifa cerrada', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 100, 'fecha_inicio_vigencia' => '2025-01-01', 'fecha_fin_vigencia' => '2025-12-31', 'estado' => 'cerrada']);
    crearTarifa($this->tratamiento->id, ['precio' => 150, 'fecha_inicio_vigencia' => '2026-01-01']);

    expect(app(TarifaService::class)->precioVigente($this->tratamiento->id, '2025-06-15'))->toBe('100.00');
});

it('precioVigente devuelve null si no hay tarifa', function () {
    expect(app(TarifaService::class)->precioVigente($this->tratamiento->id, today()))->toBeNull();
});

it('con una tarifa de inicio futuro, hoy sigue valiendo el precio anterior', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 150, 'fecha_inicio_vigencia' => today()->subMonth()]);

    $this->actingAs($this->admin)
        ->post(route('tarifas.guardar'), datosTarifa($this->tratamiento->id, [
            'precio' => 200,
            'fecha_inicio_vigencia' => today()->addMonth()->toDateString(),
        ]))
        ->assertSessionHasNoErrors();

    $servicio = app(TarifaService::class);

    expect($servicio->precioVigente($this->tratamiento->id, today()))->toBe('150.00')
        ->and($servicio->precioVigente($this->tratamiento->id, today()->addMonth()))->toBe('200.00');
});
