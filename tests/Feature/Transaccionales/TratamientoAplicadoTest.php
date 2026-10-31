<?php

use App\Models\Bitacora;
use App\Models\TratamientoAplicado;
use App\Services\BitacoraService;

/*
 * CU28 Registrar tratamiento aplicado (RF-27, RF-37, RF-38, RF-39). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->plan = crearPlan();
    $this->tratamiento = crearTratamiento(['nombre' => 'Conducto', 'numero_sesiones_previstas' => 3]);
    $this->diagnostico = crearDiagnostico(['nombre' => 'Pulpitis']);
    $this->tipo = crearTipoEstudio(['nombre' => 'Radiografía periapical']);
});

// Datos válidos del tratamiento aplicado; $cambios reemplaza algunos.
function datosTratamientoAplicado(int $planId, int $tratamientoId, int $diagnosticoId, array $cambios = []): array
{
    return $cambios + [
        'plan_tratamiento_id' => $planId,
        'tratamiento_id' => $tratamientoId,
        'diagnostico_id' => $diagnosticoId,
        'observacion_diagnostica' => 'Dolor al frío',
        'costo_acordado' => '250.00',
        'sesiones_planificadas' => 3,
        'fecha_inicio' => today()->toDateString(),
    ];
}

it('propone el precio vigente de hoy y las sesiones del catálogo', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 300, 'fecha_inicio_vigencia' => today()->subMonth()]);
    $sinTarifa = crearTratamiento();

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.crear', $this->plan->id))
        ->assertOk();

    expect($respuesta->viewData('precios')[$this->tratamiento->id])->toBe('300.00')
        ->and($respuesta->viewData('precios')[$sinTarifa->id])->toBeNull()
        ->and($respuesta->viewData('sesiones')[$this->tratamiento->id])->toBe(3);
});

it('advierte cuando falta un estudio obligatorio previo', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.crear', $this->plan->id))
        ->assertSee('Faltan estudios previos obligatorios: ');

    expect($respuesta->viewData('faltantes')[$this->tratamiento->id])->toBe(['Radiografía periapical']);
});

it('lista todos los estudios que faltan', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);
    crearEstudioRequerido($this->tratamiento->id, crearTipoEstudio(['nombre' => 'Tomografía'])->id);

    $respuesta = $this->actingAs($this->usuario)->get(route('tratamientos-aplicados.crear', $this->plan->id));

    expect($respuesta->viewData('faltantes')[$this->tratamiento->id])->toBe(['Radiografía periapical', 'Tomografía']);
});

it('no advierte si el estudio ya está cargado para el paciente', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);
    crearEstudioDiagnostico($this->plan->paciente_id, $this->tipo->id);

    $respuesta = $this->actingAs($this->usuario)->get(route('tratamientos-aplicados.crear', $this->plan->id));

    expect($respuesta->viewData('faltantes'))->toBe([]);
});

it('un estudio de otro paciente no cuenta como cargado', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);
    crearEstudioDiagnostico(crearPaciente()->id, $this->tipo->id);

    $respuesta = $this->actingAs($this->usuario)->get(route('tratamientos-aplicados.crear', $this->plan->id));

    expect($respuesta->viewData('faltantes'))->toHaveKey($this->tratamiento->id);
});

it('no advierte si el estudio es recomendado o no es previo', function (array $datos) {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id, $datos);

    $respuesta = $this->actingAs($this->usuario)->get(route('tratamientos-aplicados.crear', $this->plan->id));

    expect($respuesta->viewData('faltantes'))->toBe([]);
})->with([
    'recomendado' => [['obligatorio' => false]],
    'no previo' => [['momento_clinico' => 'control posterior']],
]);

it('solo ofrece tratamientos disponibles y diagnósticos activos', function () {
    crearTratamiento(['nombre' => 'Xilofono', 'estado' => 'no disponible']);
    crearDiagnostico(['nombre' => 'Quetzal', 'estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.crear', $this->plan->id))
        ->assertSee('Conducto')
        ->assertSee('Pulpitis')
        ->assertDontSee('Xilofono')
        ->assertDontSee('Quetzal');
});

it('registra el tratamiento planificado y recalcula el costo del plan', function () {
    crearTratamientoAplicado(['plan_tratamiento_id' => $this->plan->id, 'costo_acordado' => 100]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $this->diagnostico->id))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('planes.mostrar', $this->plan->id))
        ->assertSessionHas('exito', 'Tratamiento registrado en el plan.');

    $aplicado = TratamientoAplicado::where('tratamiento_id', $this->tratamiento->id)->first();

    expect($aplicado->estado)->toBe('planificado')
        ->and($aplicado->odontologo_id)->toBe($this->plan->odontologo_id)
        ->and($aplicado->costo_acordado)->toBe('250.00')
        ->and($this->plan->fresh()->costo_total)->toBe('350.00');

    $acciones = Bitacora::where('tabla_afectada', 'tratamientos_aplicados')
        ->where('registro_id', $aplicado->id)
        ->where('usuario_id', $this->usuario->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::REGISTRAR_TRATAMIENTO_APLICADO]);
});

it('guarda el odontólogo del plan aunque registre otro usuario', function () {
    expect($this->usuario->id)->not->toBe($this->plan->odontologo_id);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $this->diagnostico->id))
        ->assertSessionHasNoErrors();

    $aplicado = TratamientoAplicado::first();

    expect($aplicado->odontologo_id)->toBe($this->plan->odontologo_id);

    // La bitácora sí queda a nombre de quien registró.
    expect(Bitacora::where('accion', BitacoraService::REGISTRAR_TRATAMIENTO_APLICADO)->where('registro_id', $aplicado->id)->value('usuario_id'))
        ->toBe($this->usuario->id);
});

it('A2 no bloquea: registra aunque falte el estudio', function () {
    crearEstudioRequerido($this->tratamiento->id, $this->tipo->id);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $this->diagnostico->id))
        ->assertSessionHasNoErrors();

    expect(TratamientoAplicado::count())->toBe(1);
});

it('A1: rechaza datos no válidos junto al campo y conserva los datos', function (string $campo, $valor, string $mensaje) {
    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $this->diagnostico->id, [$campo => $valor]))
        ->assertSessionHasErrors([$campo => $mensaje])
        ->assertSessionHasInput('observacion_diagnostica', 'Dolor al frío');

    expect(TratamientoAplicado::count())->toBe(0);
})->with([
    'costo negativo' => ['costo_acordado', -1, 'El costo acordado no puede ser negativo.'],
    'sesiones 0' => ['sesiones_planificadas', 0, 'Debe haber al menos 1 sesión planificada.'],
    'sin diagnóstico' => ['diagnostico_id', '', 'Debe seleccionar el diagnóstico.'],
    'sin fecha' => ['fecha_inicio', '', 'El campo Fecha de inicio es obligatorio.'],
]);

it('A1: rechaza un diagnóstico inactivo', function () {
    $inactivo = crearDiagnostico(['estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $inactivo->id))
        ->assertSessionHasErrors(['diagnostico_id' => 'El diagnóstico indicado no existe o está inactivo.']);

    expect(TratamientoAplicado::count())->toBe(0);
});

it('rechaza un tratamiento no disponible', function () {
    $noDisponible = crearTratamiento(['estado' => 'no disponible']);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $noDisponible->id, $this->diagnostico->id))
        ->assertSessionHasErrors('tratamiento_id');

    expect(TratamientoAplicado::count())->toBe(0);
});

it('no abre el formulario de un plan cancelado', function () {
    $cancelado = crearPlan(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.crear', $cancelado->id))
        ->assertRedirect(route('planes.mostrar', $cancelado->id))
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);
});

it('rechaza agregar a un plan cancelado', function () {
    $cancelado = crearPlan(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($cancelado->id, $this->tratamiento->id, $this->diagnostico->id))
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);

    expect(TratamientoAplicado::count())->toBe(0);
});

it('el costo acordado no cambia al registrar una tarifa nueva', function () {
    crearTarifa($this->tratamiento->id, ['precio' => 250, 'fecha_inicio_vigencia' => today()->subMonth()]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.guardar'), datosTratamientoAplicado($this->plan->id, $this->tratamiento->id, $this->diagnostico->id));

    $admin = crearUsuario('administrador');

    $this->actingAs($admin)
        ->post(route('tarifas.guardar'), [
            'tratamiento_id' => $this->tratamiento->id,
            'precio' => 900,
            'fecha_inicio_vigencia' => today()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect(TratamientoAplicado::first()->costo_acordado)->toBe('250.00')
        ->and($this->plan->fresh()->costo_total)->toBe('250.00');
});
