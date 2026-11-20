<?php

use App\Models\Bitacora;
use App\Models\EstadoCita;
use App\Models\Sesion;
use App\Services\BitacoraService;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU30 Registrar sesión de evolución (RF-43 a RF-50, RF-66). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->plan = crearPlan(['paciente_id' => $this->paciente->id]);
    $this->ta = crearTratamientoAplicado([
        'plan_tratamiento_id' => $this->plan->id,
        'sesiones_planificadas' => 2,
        'fecha_inicio' => today()->subMonth(),
        'estado' => 'planificado',
    ]);
});

// Datos válidos de una sesión; $cambios reemplaza algunos.
function datosSesion(int $taId, array $cambios = []): array
{
    return $cambios + [
        'tratamiento_aplicado_id' => $taId,
        'fecha' => today()->toDateString(),
        'duracion_real_minutos' => 45,
        'avance_tecnico' => 'Apertura cameral',
        'fecha_proxima_sesion' => today()->addWeek()->toDateString(),
    ];
}

it('registra la primera sesión y la numera 1', function () {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Sesión registrada correctamente.');

    $sesion = Sesion::first();

    expect($sesion->numero_sesion)->toBe(1)
        ->and($sesion->odontologo_id)->toBe($this->usuario->id)
        ->and($sesion->estado)->toBe('realizada')
        ->and($sesion->avance_tecnico)->toBe('Apertura cameral');
});

it('la segunda sesión se numera 2 sin que el formulario mande el número', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDay()]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['numero_sesion' => 99]))
        ->assertSessionHasNoErrors();

    expect(Sesion::orderByDesc('id')->first()->numero_sesion)->toBe(2);
});

it('el tratamiento planificado pasa a en proceso al registrar la primera sesión', function () {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasNoErrors();

    expect($this->ta->fresh()->estado)->toBe('en proceso')
        ->and(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_TRATAMIENTO)
            ->where('registro_id', $this->ta->id)->exists())->toBeTrue();
});

it('rechaza sesión en tratamiento cancelado, con el mensaje exacto', function () {
    $this->ta->update(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasErrors(['operacion' => 'No se pueden registrar sesiones en un tratamiento cancelado.']);

    expect(Sesion::count())->toBe(0);
});

it('rechaza sesión en tratamiento concluido, con el mensaje exacto', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDay()]);
    $this->ta->update(['estado' => 'concluido', 'fecha_fin' => today()]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasErrors(['operacion' => 'No se pueden registrar sesiones en un tratamiento concluido.']);

    expect(Sesion::count())->toBe(1)
        ->and($this->ta->fresh()->sesiones_planificadas)->toBe(2);
});

it('rechaza fecha futura y fecha anterior al inicio del tratamiento', function () {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, [
            'fecha' => today()->addDay()->toDateString(),
            'fecha_proxima_sesion' => null,
        ]))
        ->assertSessionHasErrors(['fecha' => 'La fecha de la sesión no puede ser futura.']);

    $inicio = $this->ta->fecha_inicio;

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['fecha' => $inicio->copy()->subDay()->toDateString()]))
        ->assertSessionHasErrors(['fecha' => 'La sesión no puede ser anterior al inicio del tratamiento ('.$inicio->format('d/m/Y').').']);

    expect(Sesion::count())->toBe(0);
});

it('rechaza duración fuera del rango 1-600', function (mixed $duracion) {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['duracion_real_minutos' => $duracion]))
        ->assertSessionHasErrors(['duracion_real_minutos' => 'La duración debe estar entre 1 y 600 minutos.']);

    expect(Sesion::count())->toBe(0);
})->with([0, 601, -5]);

it('rechaza fecha_proxima_sesion anterior o igual a la fecha de la sesión', function (int $dias) {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, [
            'fecha_proxima_sesion' => today()->addDays($dias)->toDateString(),
        ]))
        ->assertSessionHasErrors(['fecha_proxima_sesion' => 'La próxima sesión debe ser posterior a la fecha de esta sesión.']);
})->with(['igual' => 0, 'anterior' => -3]);

it('al exceder las sesiones planificadas sin marcar ampliación, devuelve el mensaje exacto', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDays(2)]);
    crearSesion($this->ta->id, ['numero_sesion' => 2, 'fecha' => today()->subDay()]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasErrors(['ampliar' => 'El tratamiento tiene 2 sesiones planificadas. Marque la ampliación para continuar.']);

    expect(Sesion::count())->toBe(2)
        ->and($this->ta->fresh()->sesiones_planificadas)->toBe(2);
});

it('marcando la ampliación, sube sesiones_planificadas y deja la línea en observaciones', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDays(2)]);
    crearSesion($this->ta->id, ['numero_sesion' => 2, 'fecha' => today()->subDay()]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['ampliar' => '1']))
        ->assertSessionHasNoErrors();

    $ta = $this->ta->fresh();

    expect($ta->sesiones_planificadas)->toBe(3)
        ->and($ta->observaciones)->toContain('[Sesiones ampliadas el '.today()->format('d/m/Y').' de 2 a 3]')
        ->and(Sesion::orderByDesc('id')->first()->numero_sesion)->toBe(3)
        ->and(Bitacora::where('accion', BitacoraService::AMPLIAR_SESIONES)->where('registro_id', $ta->id)->exists())->toBeTrue();
});

it('registra REGISTRAR_SESION en la bitácora', function () {
    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id))
        ->assertSessionHasNoErrors();

    expect(Bitacora::where('accion', BitacoraService::REGISTRAR_SESION)
        ->where('usuario_id', $this->usuario->id)
        ->where('tabla_afectada', 'sesiones')
        ->where('registro_id', Sesion::first()->id)
        ->exists())->toBeTrue();
});

it('registra materiales junto con la sesión', function () {
    $resina = crearMaterial(['nombre' => 'Resina A2']);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, [
            'materiales' => [['material_id' => $resina->id, 'cantidad' => '1.5', 'observacion' => 'Capa final']],
        ]))
        ->assertSessionHasNoErrors();

    $registro = Sesion::first()->materiales()->first();

    expect($registro->material_id)->toBe($resina->id)
        ->and((float) $registro->cantidad)->toBe(1.5);
});

it('genera la sesión desde una cita atendida y guarda cita_id', function () {
    $cita = crearCita([
        'estado' => EstadoCita::ATENDIDA,
        'paciente_id' => $this->paciente->id,
        'tratamiento_aplicado_id' => $this->ta->id,
        'fecha' => today()->toDateString(),
        'duracion_minutos' => 50,
    ]);

    // RF-49: el formulario precarga la fecha y la duración de la cita.
    $this->actingAs($this->usuario)
        ->get(route('sesiones.desde-cita', $cita->id))
        ->assertOk()
        ->assertViewIs('sesiones.create')
        ->assertViewHas('fechaPropuesta', today()->toDateString())
        ->assertViewHas('duracionPropuesta', 50)
        ->assertViewHas('tratamiento', fn ($t) => $t->id === $this->ta->id);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['cita_id' => $cita->id, 'duracion_real_minutos' => 50]))
        ->assertSessionHasNoErrors();

    expect(Sesion::first()->cita_id)->toBe($cita->id);
});

it('rechaza generar la sesión desde una cita que no está atendida', function () {
    $cita = crearCita(['paciente_id' => $this->paciente->id, 'tratamiento_aplicado_id' => $this->ta->id]);
    $mensaje = 'La cita debe estar en estado atendida para generar la sesión.';

    $this->actingAs($this->usuario)
        ->get(route('sesiones.desde-cita', $cita->id))
        ->assertRedirect(route('citas.mostrar', $cita->id))
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['cita_id' => $cita->id]))
        ->assertSessionHasErrors(['operacion' => $mensaje]);

    expect(Sesion::count())->toBe(0);
});

it('RF-66: al generar la sesión desde una cita sin tratamiento_aplicado_id, la cita queda con el tratamiento elegido', function () {
    $cita = crearCita([
        'estado' => EstadoCita::ATENDIDA,
        'paciente_id' => $this->paciente->id,
        'fecha' => today()->toDateString(),
    ]);

    // Sin tratamiento en la cita, primero se elige uno activo del paciente.
    $this->actingAs($this->usuario)
        ->get(route('sesiones.desde-cita', $cita->id))
        ->assertOk()
        ->assertViewIs('sesiones.elegir-tratamiento')
        ->assertSee($this->ta->tratamiento->nombre);

    $this->actingAs($this->usuario)
        ->get(route('sesiones.desde-cita', ['cita' => $cita->id, 'tratamiento_aplicado_id' => $this->ta->id]))
        ->assertOk()
        ->assertViewIs('sesiones.create');

    $this->actingAs($this->usuario)
        ->post(route('sesiones.guardar'), datosSesion($this->ta->id, ['cita_id' => $cita->id]))
        ->assertSessionHasNoErrors();

    expect($cita->fresh()->tratamiento_aplicado_id)->toBe($this->ta->id)
        ->and(Sesion::first()->cita_id)->toBe($cita->id);
});

it('muestra el formulario con el número de sesión y la advertencia del RF-50', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDays(2)]);
    crearSesion($this->ta->id, ['numero_sesion' => 2, 'fecha' => today()->subDay()]);

    $this->actingAs($this->usuario)
        ->get(route('sesiones.crear', $this->ta->id))
        ->assertOk()
        ->assertSee('Sesión 3')
        ->assertSee('Ampliar el número de sesiones a 3');
});
