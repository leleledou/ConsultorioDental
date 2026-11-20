<?php

use App\Models\Bitacora;
use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use App\Models\Odontograma;
use App\Services\BitacoraService;
use Database\Seeders\EstadoPiezaSeeder;
use Database\Seeders\PiezaDentalSeeder;

/*
 * CU25 Registrar odontograma (RF-15, RF-20, RF-23). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->seed([PiezaDentalSeeder::class, EstadoPiezaSeeder::class]);

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->historia = crearHistoria($this->paciente->id);
});

// Datos válidos de un odontograma nuevo; $cambios reemplaza algunos.
function datosOdontograma(int $historiaId, array $cambios = []): array
{
    return $cambios + [
        'historia_clinica_id' => $historiaId,
        'fecha_registro' => today()->toDateString(),
        'tipo' => 'inicial',
        'tipo_denticion' => 'permanente',
        'observaciones' => 'Toma inicial',
    ];
}

// Odontograma con la pieza 11 en estado SANO; devuelve el detalle.
function odontogramaConPieza(int $historiaId, array $datos = []): DetalleOdontograma
{
    $odontograma = crearOdontograma($historiaId, $datos);

    return DetalleOdontograma::create([
        'odontograma_id' => $odontograma->id,
        'pieza_dental_id' => 11,
        'estado_pieza_id' => EstadoPieza::where('codigo', 'SANO')->value('id'),
    ]);
}

it('registra un odontograma inicial permanente y carga automáticamente 32 piezas', function () {
    $respuesta = $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id));

    $odontograma = Odontograma::first();

    $respuesta->assertSessionHasNoErrors()
        ->assertRedirect(route('odontogramas.mostrar', $odontograma->id))
        ->assertSessionHas('exito', 'Odontograma registrado correctamente.');

    expect($odontograma->tipo)->toBe('inicial')
        ->and($odontograma->estado)->toBe('activo')
        ->and($odontograma->detalles()->count())->toBe(32);
});

it('registra uno temporal y carga 20 piezas', function () {
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id, ['tipo_denticion' => 'temporal']))
        ->assertSessionHasNoErrors();

    $odontograma = Odontograma::first();

    expect($odontograma->detalles()->count())->toBe(20)
        ->and($odontograma->detalles()->where('pieza_dental_id', '<', 51)->count())->toBe(0);
});

it('registra uno mixto y carga 52 piezas', function () {
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id, ['tipo_denticion' => 'mixta']))
        ->assertSessionHasNoErrors();

    expect(Odontograma::first()->detalles()->count())->toBe(52);
});

it('todas las piezas quedan con el estado SANO', function () {
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id))
        ->assertSessionHasNoErrors();

    $sano = EstadoPieza::where('codigo', 'SANO')->value('id');

    expect(DetalleOdontograma::count())->toBe(32)
        ->and(DetalleOdontograma::where('estado_pieza_id', '!=', $sano)->count())->toBe(0);
});

it('rechaza un segundo odontograma inicial con el mensaje exacto', function () {
    crearOdontograma($this->historia->id, ['fecha_registro' => today()->subMonth()]);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id))
        ->assertSessionHasErrors(['operacion' => 'El paciente ya tiene un odontograma inicial. Registre uno de evolución.']);

    expect(Odontograma::count())->toBe(1);
});

it('rechaza dos odontogramas del mismo paciente en la misma fecha con el mensaje exacto', function () {
    crearOdontograma($this->historia->id);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id, ['tipo' => 'evolución']))
        ->assertSessionHasErrors(['operacion' => 'Ya existe un odontograma registrado para esta fecha.']);

    expect(Odontograma::count())->toBe(1);
});

it('rechaza fecha futura', function () {
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id, [
            'fecha_registro' => today()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['fecha_registro' => 'La fecha del odontograma no puede ser futura.']);

    expect(Odontograma::count())->toBe(0)
        ->and(DetalleOdontograma::count())->toBe(0);
});

it('el odontologo_id guardado es el del usuario autenticado', function () {
    $otro = crearUsuario();

    // Aunque el formulario mande otro odontólogo, se usa el de la sesión.
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id, ['odontologo_id' => $otro->id]))
        ->assertSessionHasNoErrors();

    expect(Odontograma::first()->odontologo_id)->toBe($this->usuario->id);
});

it('registra la acción CREAR_ODONTOGRAMA en la bitácora', function () {
    $this->actingAs($this->usuario)
        ->post(route('odontogramas.guardar'), datosOdontograma($this->historia->id))
        ->assertSessionHasNoErrors();

    $odontograma = Odontograma::first();

    expect(Bitacora::where('accion', BitacoraService::CREAR_ODONTOGRAMA)
        ->where('usuario_id', $this->usuario->id)
        ->where('tabla_afectada', 'odontogramas')
        ->where('registro_id', $odontograma->id)
        ->exists())->toBeTrue();
});

it('muestra el odontograma gráfico con sus piezas y la leyenda', function () {
    $detalle = odontogramaConPieza($this->historia->id);

    $this->actingAs($this->usuario)
        ->get(route('odontogramas.mostrar', $detalle->odontograma_id))
        ->assertOk()
        ->assertSee('Arcada superior')
        ->assertSee('#43A047')
        ->assertSee('Tratamiento de conducto');
});

it('agregar una pieza ya presente devuelve el mensaje exacto', function () {
    $detalle = odontogramaConPieza($this->historia->id);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.guardar'), [
            'odontograma_id' => $detalle->odontograma_id,
            'pieza_dental_id' => 11,
            'estado_pieza_id' => EstadoPieza::where('codigo', 'CARIES')->value('id'),
        ])
        ->assertSessionHasErrors(['operacion' => 'Esta pieza ya está registrada en el odontograma.']);

    expect(DetalleOdontograma::count())->toBe(1);
});

it('agrega una pieza que no está en el odontograma', function () {
    $detalle = odontogramaConPieza($this->historia->id);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.guardar'), [
            'odontograma_id' => $detalle->odontograma_id,
            'pieza_dental_id' => 12,
            'estado_pieza_id' => EstadoPieza::where('codigo', 'CARIES')->value('id'),
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de la pieza registrado correctamente.');

    expect(DetalleOdontograma::where('pieza_dental_id', 12)->exists())->toBeTrue()
        ->and(Bitacora::where('accion', BitacoraService::REGISTRAR_DETALLE_ODONTOGRAMA)->exists())->toBeTrue();
});

it('modificar el estado de una pieza lo cambia y queda en bitácora', function () {
    $detalle = odontogramaConPieza($this->historia->id);
    $caries = EstadoPieza::where('codigo', 'CARIES')->value('id');

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.modificar'), [
            'id' => $detalle->id,
            'estado_pieza_id' => $caries,
            'observacion' => 'Caries oclusal',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado de la pieza actualizado correctamente.');

    $detalle->refresh();

    expect($detalle->estado_pieza_id)->toBe($caries)
        ->and($detalle->observacion)->toBe('Caries oclusal')
        ->and(Bitacora::where('accion', BitacoraService::MODIFICAR_DETALLE_ODONTOGRAMA)
            ->where('tabla_afectada', 'detalles_odontograma')
            ->where('registro_id', $detalle->id)
            ->exists())->toBeTrue();
});

it('vincula un tratamiento aplicado del paciente a la pieza (RF-20)', function () {
    $detalle = odontogramaConPieza($this->historia->id);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => crearPlan(['paciente_id' => $this->paciente->id])->id]);
    $ajeno = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.modificar'), [
            'id' => $detalle->id,
            'estado_pieza_id' => $detalle->estado_pieza_id,
            'tratamiento_aplicado_id' => $ajeno->id,
        ])
        ->assertSessionHasErrors(['operacion' => 'El tratamiento aplicado no pertenece al paciente del odontograma.']);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.modificar'), [
            'id' => $detalle->id,
            'estado_pieza_id' => $detalle->estado_pieza_id,
            'tratamiento_aplicado_id' => $tratamiento->id,
        ])
        ->assertSessionHasNoErrors();

    expect($detalle->fresh()->tratamiento_aplicado_id)->toBe($tratamiento->id);
});

it('quitar una pieza la elimina del odontograma', function () {
    $detalle = odontogramaConPieza($this->historia->id);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.quitar'), ['id' => $detalle->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Pieza quitada del odontograma.');

    expect(DetalleOdontograma::find($detalle->id))->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::QUITAR_DETALLE_ODONTOGRAMA)->exists())->toBeTrue();
});

it('no permite modificar un odontograma anulado', function () {
    $detalle = odontogramaConPieza($this->historia->id, ['estado' => 'anulado']);
    $mensaje = 'No se puede modificar un odontograma anulado.';

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.modificar'), [
            'id' => $detalle->id,
            'estado_pieza_id' => EstadoPieza::where('codigo', 'CARIES')->value('id'),
        ])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.guardar'), [
            'odontograma_id' => $detalle->odontograma_id,
            'pieza_dental_id' => 12,
            'estado_pieza_id' => $detalle->estado_pieza_id,
        ])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    $this->actingAs($this->usuario)
        ->post(route('odontogramas.detalle.quitar'), ['id' => $detalle->id])
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    expect($detalle->fresh()->estado_pieza_id)->toBe($detalle->estado_pieza_id)
        ->and(DetalleOdontograma::count())->toBe(1);
});
