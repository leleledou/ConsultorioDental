<?php

use App\Models\Bitacora;
use App\Services\BitacoraService;

/*
 * CU29 Actualizar estado del tratamiento aplicado (RF-40). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
});

it('calcula el avance solo con las sesiones realizadas', function () {
    $tratamiento = crearTratamientoAplicado(['sesiones_planificadas' => 4, 'estado' => 'en proceso']);
    crearSesion($tratamiento->id);
    crearSesion($tratamiento->id);
    crearSesion($tratamiento->id, ['estado' => 'pendiente']);
    crearSesion($tratamiento->id, ['estado' => 'anulada']);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.mostrar', $tratamiento->id))
        ->assertOk()
        ->assertSee('Sesión 2 de 4');
});

it('ofrece solo los destinos permitidos', function (string $estado, array $destinos) {
    $tratamiento = crearTratamientoAplicado(['estado' => $estado, 'fecha_fin' => $estado === 'concluido' ? today() : null]);

    $respuesta = $this->actingAs($this->usuario)->get(route('tratamientos-aplicados.mostrar', $tratamiento->id));

    expect($respuesta->viewData('destinos'))->toBe($destinos);
})->with([
    'planificado' => ['planificado', ['en proceso', 'cancelado']],
    'en proceso' => ['en proceso', ['concluido', 'cancelado']],
    'concluido' => ['concluido', []],
    'cancelado' => ['cancelado', []],
]);

it('un tratamiento en estado final no muestra el formulario', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.mostrar', $tratamiento->id))
        ->assertDontSee('Cambiar estado');
});

it('pasa de planificado a en proceso', function () {
    $tratamiento = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'en proceso'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado del tratamiento actualizado.');

    expect($tratamiento->fresh()->estado)->toBe('en proceso')
        ->and($tratamiento->fresh()->fecha_fin)->toBeNull();
});

it('concluye con todas las sesiones y pone la fecha de fin', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'en proceso', 'sesiones_planificadas' => 2]);
    crearSesion($tratamiento->id);
    crearSesion($tratamiento->id);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estado del tratamiento actualizado.');

    expect($tratamiento->fresh()->estado)->toBe('concluido')
        ->and($tratamiento->fresh()->fecha_fin->toDateString())->toBe(today()->toDateString());
});

it('A2: concluir con pendientes sin confirmar no cambia y devuelve confirmar', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'en proceso', 'sesiones_planificadas' => 3]);
    crearSesion($tratamiento->id);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido', 'confirmado' => 0])
        ->assertSessionHas('confirmar', 2);

    expect($tratamiento->fresh()->estado)->toBe('en proceso')
        ->and($tratamiento->fresh()->fecha_fin)->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_TRATAMIENTO)->count())->toBe(0);
});

it('A2: muestra el aviso en singular y en plural', function (int $planificadas, string $texto) {
    $tratamiento = crearTratamientoAplicado(['estado' => 'en proceso', 'sesiones_planificadas' => $planificadas]);

    $this->actingAs($this->usuario)
        ->from(route('tratamientos-aplicados.mostrar', $tratamiento->id))
        ->followingRedirects()
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido'])
        ->assertSee($texto);
})->with([
    'singular' => [1, 'El tratamiento tiene 1 sesión pendiente. ¿Desea concluirlo igualmente?'],
    'plural' => [3, 'El tratamiento tiene 3 sesiones pendientes. ¿Desea concluirlo igualmente?'],
]);

it('A2: con confirmado = 1 concluye', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'en proceso', 'sesiones_planificadas' => 3]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido', 'confirmado' => 1])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    expect($tratamiento->fresh()->estado)->toBe('concluido');
});

it('A1: no permite pasar de concluido a en proceso', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'concluido', 'fecha_fin' => today()]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'en proceso'])
        ->assertSessionHasErrors(['estado' => 'No se puede pasar de concluido a en proceso']);

    expect($tratamiento->fresh()->estado)->toBe('concluido');
});

it('A1: no permite saltar de planificado a concluido', function () {
    $tratamiento = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido'])
        ->assertSessionHasErrors(['estado' => 'No se puede pasar de planificado a concluido']);

    expect($tratamiento->fresh()->estado)->toBe('planificado');
});

it('no concluye antes de la fecha de inicio, aunque venga confirmado', function (int $confirmado) {
    $this->withoutExceptionHandling();

    $inicio = today()->addDays(5);
    $tratamiento = crearTratamientoAplicado(['estado' => 'en proceso', 'fecha_inicio' => $inicio]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido', 'confirmado' => $confirmado])
        ->assertRedirect()
        ->assertSessionHasErrors(['estado' => "No se puede concluir antes de la fecha de inicio ({$inicio->format('d/m/Y')})"])
        ->assertSessionMissing('confirmar');

    expect($tratamiento->fresh()->estado)->toBe('en proceso')
        ->and($tratamiento->fresh()->fecha_fin)->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_TRATAMIENTO)->count())->toBe(0);
})->with([0, 1]);

it('A1 va antes que la fecha de inicio', function () {
    $tratamiento = crearTratamientoAplicado(['estado' => 'planificado', 'fecha_inicio' => today()->addDays(5)]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'concluido'])
        ->assertSessionHasErrors(['estado' => 'No se puede pasar de planificado a concluido']);
});

it('no cambia el estado de un tratamiento de un plan que no está activo', function (string $estadoPlan) {
    $plan = crearPlan(['estado' => $estadoPlan, 'costo_total' => 100]);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'cancelado', 'motivo' => 'x'])
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);

    expect($tratamiento->fresh()->estado)->toBe('planificado')
        ->and($tratamiento->fresh()->observaciones)->toBeNull()
        ->and($plan->fresh()->costo_total)->toBe('100.00')
        ->and(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_TRATAMIENTO)->count())->toBe(0);
})->with(['cancelado', 'concluido']);

it('el plan no activo se revisa antes que A1', function () {
    $plan = crearPlan(['estado' => 'cancelado']);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'estado' => 'concluido', 'fecha_fin' => today()]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'en proceso'])
        ->assertSessionHasErrors(['operacion' => 'El plan no está activo.']);
});

it('la ficha de un tratamiento de un plan cancelado no muestra el formulario de estado', function () {
    $plan = crearPlan(['estado' => 'cancelado']);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id]);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.mostrar', $tratamiento->id))
        ->assertOk()
        ->assertSee('El plan no está activo.')
        ->assertDontSee('Cambiar estado')
        ->assertDontSee(route('tratamientos-aplicados.estado'), false);
});

it('rechaza un estado fuera de la lista', function () {
    $tratamiento = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'otro'])
        ->assertSessionHasErrors(['estado' => 'El estado indicado no es válido.']);
});

it('cancelar exige el motivo', function () {
    $tratamiento = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'cancelado', 'motivo' => ''])
        ->assertSessionHasErrors(['motivo' => 'Debe indicar el motivo de la cancelación.']);

    expect($tratamiento->fresh()->estado)->toBe('planificado');
});

it('cancelar guarda el motivo en observaciones y recalcula el costo del plan', function () {
    $plan = crearPlan(['costo_total' => 300]);
    $tratamiento = crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 100, 'observaciones' => 'Original']);
    crearTratamientoAplicado(['plan_tratamiento_id' => $plan->id, 'costo_acordado' => 200]);

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'cancelado', 'motivo' => 'Decisión del paciente'])
        ->assertSessionHasNoErrors();

    $fecha = today()->format('d/m/Y');

    expect($tratamiento->fresh()->estado)->toBe('cancelado')
        ->and($tratamiento->fresh()->observaciones)->toBe("Original\n[Cancelado el {$fecha}] Decisión del paciente")
        ->and($plan->fresh()->costo_total)->toBe('200.00');
});

it('registra CAMBIO_ESTADO_TRATAMIENTO en la bitácora', function () {
    $tratamiento = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('tratamientos-aplicados.estado'), ['id' => $tratamiento->id, 'estado' => 'en proceso']);

    $acciones = Bitacora::where('tabla_afectada', 'tratamientos_aplicados')
        ->where('registro_id', $tratamiento->id)
        ->where('usuario_id', $this->usuario->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::CAMBIO_ESTADO_TRATAMIENTO]);
});
