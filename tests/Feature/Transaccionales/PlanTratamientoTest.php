<?php

use App\Models\Bitacora;
use App\Models\PlanTratamiento;
use App\Services\BitacoraService;
use App\Services\PlanTratamientoService;

/*
 * CU27 Gestionar plan de tratamiento. Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
});

it('sin paciente muestra solo el buscador', function () {
    crearPaciente(['nombres' => 'Xilofono']);
    crearPaciente(['nombres' => 'Quetzal']);

    $this->actingAs($this->usuario)
        ->get(route('planes.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal')
        ->assertDontSee('Nuevo plan');
});

it('lista los planes del paciente por fecha de creación descendente', function () {
    crearPlan(['paciente_id' => $this->paciente->id, 'fecha_creacion' => '2026-01-10', 'descripcion' => 'Plan antiguo']);
    crearPlan(['paciente_id' => $this->paciente->id, 'fecha_creacion' => '2026-03-10', 'descripcion' => 'Plan reciente']);
    crearPlan(['descripcion' => 'De otro paciente']);

    $this->actingAs($this->usuario)
        ->get(route('planes.listar', ['paciente_id' => $this->paciente->id]))
        ->assertOk()
        ->assertSeeInOrder(['Plan reciente', 'Plan antiguo'])
        ->assertDontSee('De otro paciente');
});

it('solo ofrece Nuevo plan a un paciente activo', function () {
    $inactivo = crearPaciente(['estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->get(route('planes.listar', ['paciente_id' => $inactivo->id]))
        ->assertOk()
        ->assertDontSee('Nuevo plan')
        ->assertDontSee('name="odontologo_id"', false);

    $this->actingAs($this->usuario)
        ->get(route('planes.listar', ['paciente_id' => $this->paciente->id]))
        ->assertSee('Nuevo plan')
        ->assertSee('name="odontologo_id"', false);
});

it('crea un plan activo con costo 0 y la fecha de hoy', function () {
    $this->actingAs($this->usuario)
        ->post(route('planes.guardar'), [
            'paciente_id' => $this->paciente->id,
            'odontologo_id' => $this->usuario->id,
            'descripcion' => 'Rehabilitación',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Plan de tratamiento creado.');

    $plan = PlanTratamiento::first();

    expect($plan->estado)->toBe('activo')
        ->and($plan->costo_total)->toBe('0.00')
        ->and($plan->fecha_creacion->toDateString())->toBe(today()->toDateString());

    expect(Bitacora::where('accion', BitacoraService::CREAR_PLAN)->where('registro_id', $plan->id)->count())->toBe(1);
});

it('rechaza crear un plan para un paciente inactivo', function () {
    $inactivo = crearPaciente(['estado' => 'inactivo']);

    $this->actingAs($this->usuario)
        ->post(route('planes.guardar'), ['paciente_id' => $inactivo->id, 'odontologo_id' => $this->usuario->id])
        ->assertSessionHasErrors(['paciente_id' => 'El paciente indicado no existe o está inactivo.']);

    expect(PlanTratamiento::count())->toBe(0);
});

it('rechaza un odontólogo inactivo o sin seleccionar', function ($odontologo) {
    $id = $odontologo === 'inactivo' ? crearUsuario('odontologo', ['estado' => 'inactivo'])->id : '';

    $this->actingAs($this->usuario)
        ->post(route('planes.guardar'), ['paciente_id' => $this->paciente->id, 'odontologo_id' => $id])
        ->assertSessionHasErrors('odontologo_id');

    expect(PlanTratamiento::count())->toBe(0);
})->with(['inactivo', 'vacío']);

it('muestra el plan con el avance y solo cuenta sesiones realizadas', function () {
    $plan = crearPlan(['paciente_id' => $this->paciente->id]);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'sesiones_planificadas' => 3]);
    crearSesion($tratamiento->id);
    crearSesion($tratamiento->id, ['estado' => 'pendiente']);

    $this->actingAs($this->usuario)
        ->get(route('planes.mostrar', $plan->id))
        ->assertOk()
        ->assertSee('Sesión 1 de 3')
        ->assertSee('Agregar tratamiento')
        ->assertSee('Cancelar plan');
});

it('un plan no activo no ofrece las acciones', function () {
    $plan = crearPlan(['paciente_id' => $this->paciente->id, 'estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->get(route('planes.mostrar', $plan->id))
        ->assertOk()
        ->assertDontSee('Agregar tratamiento')
        ->assertDontSee('Modificar descripción')
        ->assertDontSee('Cancelar plan');
});

it('modifica la descripción y registra MODIFICAR_PLAN', function () {
    $plan = crearPlan();

    $this->actingAs($this->usuario)
        ->post(route('planes.modificar'), ['id' => $plan->id, 'descripcion' => 'Nueva'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Plan de tratamiento actualizado.');

    expect($plan->fresh()->descripcion)->toBe('Nueva')
        ->and(Bitacora::where('accion', BitacoraService::MODIFICAR_PLAN)->where('registro_id', $plan->id)->count())->toBe(1);
});

it('no modifica un plan que no está activo', function () {
    $plan = crearPlan(['estado' => 'cancelado', 'descripcion' => 'Original']);

    $this->actingAs($this->usuario)
        ->post(route('planes.modificar'), ['id' => $plan->id, 'descripcion' => 'Nueva'])
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);

    expect($plan->fresh()->descripcion)->toBe('Original');
});

it('cancela un plan sin tratamientos en proceso ni abonos', function () {
    $plan = crearPlan(['descripcion' => 'Original', 'costo_total' => 999]);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 100, 'estado' => 'planificado']);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 50, 'estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => 'El paciente desiste'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Plan cancelado.');

    $plan->refresh();
    $fecha = today()->format('d/m/Y');

    expect($plan->estado)->toBe('cancelado')
        ->and($plan->descripcion)->toBe("Original\n[Cancelado el {$fecha}] El paciente desiste")
        ->and($plan->costo_total)->toBe('100.00')
        ->and(Bitacora::where('accion', BitacoraService::CANCELAR_PLAN)->where('registro_id', $plan->id)->count())->toBe(1);
});

it('exige el motivo al cancelar', function () {
    $plan = crearPlan();

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => ''])
        ->assertSessionHasErrors(['motivo' => 'Debe indicar el motivo de la cancelación.']);

    expect($plan->fresh()->estado)->toBe('activo');
});

it('A1: no cancela con un tratamiento en proceso', function () {
    $plan = crearPlan();
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'estado' => 'en proceso']);

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => 'x'])
        ->assertSessionHasErrors(['operacion' => 'No se puede cancelar: el plan tiene tratamientos en proceso o abonos registrados']);

    expect($plan->fresh()->estado)->toBe('activo');
});

it('A1: no cancela con un abono vigente', function () {
    $plan = crearPlan();
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id]);
    crearPago($tratamiento->id);

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => 'x'])
        ->assertSessionHasErrors(['operacion' => 'No se puede cancelar: el plan tiene tratamientos en proceso o abonos registrados']);

    expect($plan->fresh()->estado)->toBe('activo');
});

it('con un abono anulado sí permite cancelar', function () {
    $plan = crearPlan();
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id]);
    crearPago($tratamiento->id, ['estado' => 'anulado', 'motivo_anulacion' => 'Error']);

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => 'x'])
        ->assertSessionHasNoErrors();

    expect($plan->fresh()->estado)->toBe('cancelado');
});

it('no cancela un plan ya cancelado', function () {
    $plan = crearPlan(['estado' => 'cancelado', 'descripcion' => 'Original']);

    $this->actingAs($this->usuario)
        ->post(route('planes.cancelar'), ['id' => $plan->id, 'motivo' => 'x'])
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);

    expect($plan->fresh()->descripcion)->toBe('Original');
});

it('recalcularCosto suma los costos acordados excluyendo los cancelados', function () {
    $plan = crearPlan();
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 100.50, 'estado' => 'planificado']);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 200, 'estado' => 'concluido', 'fecha_fin' => today()]);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 999, 'estado' => 'cancelado']);
    crearTratamientoAplicado(['costo_acordado' => 777]);

    app(PlanTratamientoService::class)->recalcularCosto($plan);

    expect($plan->fresh()->costo_total)->toBe('300.50');
});
