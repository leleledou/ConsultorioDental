<?php

use App\Models\Bitacora;
use App\Models\Control;
use App\Services\BitacoraService;
use App\Services\ControlService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/*
 * CU38 Programar control (RF-79, RF-80). Pruebas transaccionales.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente(['nombres' => 'Teodora']);
    $this->ta = crearTratamientoAplicado([
        'plan_tratamiento_id' => crearPlan(['paciente_id' => $this->paciente->id])->id,
        'fecha_inicio' => '2026-11-01',
        'estado' => 'en proceso',
    ]);
});

afterEach(function () {
    Carbon::setTestNow();
});

// Datos válidos de un control; $cambios reemplaza algunos.
function datosControl(int $taId, array $cambios = []): array
{
    return $cambios + [
        'tratamiento_aplicado_id' => $taId,
        'fecha_prevista' => '2026-12-15',
        'motivo' => 'Revisión de oseointegración',
    ];
}

it('programa un control y lo deja en estado pendiente', function () {
    $this->actingAs($this->usuario)
        ->post(route('controles.guardar'), datosControl($this->ta->id))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tratamientos-aplicados.mostrar', $this->ta->id))
        ->assertSessionHas('exito', 'Control programado correctamente.');

    $control = Control::first();

    expect($control->estado)->toBe('pendiente')
        ->and($control->fecha_prevista->toDateString())->toBe('2026-12-15')
        ->and($control->motivo)->toBe('Revisión de oseointegración')
        ->and($control->fecha_cumplimiento)->toBeNull();
});

it('rechaza fecha anterior o igual al inicio del tratamiento, con el mensaje exacto', function (string $fecha) {
    $this->actingAs($this->usuario)
        ->post(route('controles.guardar'), datosControl($this->ta->id, ['fecha_prevista' => $fecha]))
        ->assertSessionHasErrors(['fecha_prevista' => 'La fecha del control debe ser posterior a la del tratamiento.']);

    expect(Control::count())->toBe(0);
})->with(['igual' => '2026-11-01', 'anterior' => '2026-10-20']);

it('rechaza programar en un tratamiento cancelado', function () {
    $this->ta->update(['estado' => 'cancelado']);

    $this->actingAs($this->usuario)
        ->post(route('controles.guardar'), datosControl($this->ta->id))
        ->assertSessionHasErrors(['operacion' => 'No se pueden programar controles en un tratamiento cancelado.']);

    expect(Control::count())->toBe(0);
});

it('marca un control como cumplido y graba fecha_cumplimiento', function () {
    $control = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-20']);

    $this->actingAs($this->usuario)
        ->post(route('controles.cumplir'), ['id' => $control->id, 'observaciones' => 'Sin novedades'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Control marcado como cumplido.');

    $control->refresh();

    expect($control->estado)->toBe('cumplido')
        ->and($control->fecha_cumplimiento->toDateString())->toBe('2026-11-20')
        ->and($control->observaciones)->toBe('Sin novedades');
});

it('rechaza una fecha de cumplimiento futura', function () {
    $control = crearControl($this->ta->id);

    $this->actingAs($this->usuario)
        ->post(route('controles.cumplir'), ['id' => $control->id, 'fecha_cumplimiento' => '2026-11-21'])
        ->assertSessionHasErrors(['fecha_cumplimiento' => 'La fecha de cumplimiento no puede ser futura.']);

    expect($control->fresh()->estado)->toBe('pendiente');
});

it('rechaza cumplir uno ya cumplido, con el mensaje exacto', function () {
    $control = crearControl($this->ta->id, ['estado' => 'cumplido', 'fecha_cumplimiento' => '2026-11-10']);

    $this->actingAs($this->usuario)
        ->post(route('controles.cumplir'), ['id' => $control->id])
        ->assertSessionHasErrors(['operacion' => 'Este control ya fue marcado como cumplido.']);

    expect($control->fresh()->fecha_cumplimiento->toDateString())->toBe('2026-11-10');
});

it('permite cumplir un control VENCIDO', function () {
    $control = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-10', 'estado' => 'vencido']);

    $this->actingAs($this->usuario)
        ->post(route('controles.cumplir'), ['id' => $control->id, 'observaciones' => 'Se atendió tarde'])
        ->assertSessionHasNoErrors();

    expect($control->fresh()->estado)->toBe('cumplido')
        ->and($control->fresh()->fecha_cumplimiento->toDateString())->toBe('2026-11-20');
});

it('el paciente se deriva por el tratamiento, sin columna paciente_id', function () {
    $control = crearControl($this->ta->id);

    expect(Schema::hasColumn('controles', 'paciente_id'))->toBeFalse()
        ->and(app(ControlService::class)->pacienteDe($control)->id)->toBe($this->paciente->id);

    $this->actingAs($this->usuario)
        ->get(route('controles.listar'))
        ->assertOk()
        ->assertSee('Teodora');
});

it('registra PROGRAMAR_CONTROL y CUMPLIR_CONTROL en la bitácora', function () {
    $this->actingAs($this->usuario)
        ->post(route('controles.guardar'), datosControl($this->ta->id))
        ->assertSessionHasNoErrors();

    $control = Control::first();

    $this->actingAs($this->usuario)
        ->post(route('controles.cumplir'), ['id' => $control->id])
        ->assertSessionHasNoErrors();

    foreach ([BitacoraService::PROGRAMAR_CONTROL, BitacoraService::CUMPLIR_CONTROL] as $accion) {
        expect(Bitacora::where('accion', $accion)
            ->where('usuario_id', $this->usuario->id)
            ->where('tabla_afectada', 'controles')
            ->where('registro_id', $control->id)
            ->exists())->toBeTrue();
    }
});

it('lista primero los pendientes por fecha prevista y muestra los contadores', function () {
    crearControl($this->ta->id, ['fecha_prevista' => '2026-11-05', 'estado' => 'cumplido', 'motivo' => 'C']);
    crearControl($this->ta->id, ['fecha_prevista' => '2026-12-20', 'motivo' => 'P2']);
    crearControl($this->ta->id, ['fecha_prevista' => '2026-11-10', 'estado' => 'vencido', 'motivo' => 'V']);
    crearControl($this->ta->id, ['fecha_prevista' => '2026-12-01', 'motivo' => 'P1']);

    $respuesta = $this->actingAs($this->usuario)->get(route('controles.listar'))->assertOk();

    expect($respuesta->viewData('controles')->pluck('motivo')->all())->toBe(['P1', 'P2', 'V', 'C'])
        ->and($respuesta->viewData('contadores')->all())->toMatchArray(['pendiente' => 2, 'vencido' => 1, 'cumplido' => 1]);

    $filtrado = $this->actingAs($this->usuario)->get(route('controles.listar', ['estado' => 'vencido']))->assertOk();
    expect($filtrado->viewData('controles')->pluck('motivo')->all())->toBe(['V']);
});

it('la ficha del tratamiento lista sus controles y ofrece programar uno', function () {
    crearControl($this->ta->id, ['motivo' => 'Revisión anual']);

    $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.mostrar', $this->ta->id))
        ->assertOk()
        ->assertSee('Revisión anual')
        ->assertSee('Programar control')
        ->assertSee(route('controles.crear', $this->ta->id));
});
