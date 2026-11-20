<?php

use App\Models\Aviso;
use Illuminate\Support\Carbon;

/*
 * CU41 Consultar avisos (RF-82). Pruebas de consulta.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 09:00:00');

    $this->usuario = crearUsuario('odontologo');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('lista los avisos del odontólogo autenticado y no los de otro', function () {
    crearAviso($this->usuario->id, ['mensaje' => 'Aviso propio']);
    crearAviso(crearUsuario()->id, ['mensaje' => 'Aviso ajeno']);

    $this->actingAs($this->usuario)
        ->get(route('avisos.listar'))
        ->assertOk()
        ->assertSee('Aviso propio')
        ->assertDontSee('Aviso ajeno');
});

it('los no leídos aparecen primero', function () {
    crearAviso($this->usuario->id, ['mensaje' => 'Leído reciente', 'leido' => true, 'fecha_generacion' => now()]);
    crearAviso($this->usuario->id, ['mensaje' => 'Sin leer antiguo', 'fecha_generacion' => now()->subDays(3)]);
    crearAviso($this->usuario->id, ['mensaje' => 'Sin leer nuevo', 'fecha_generacion' => now()->subDay()]);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('avisos.listar'))
        ->assertOk()
        ->assertSeeInOrder(['Sin leer nuevo', 'Sin leer antiguo', 'Leído reciente']);

    expect($respuesta->viewData('avisos')->pluck('mensaje')->all())->toBe(['Sin leer nuevo', 'Sin leer antiguo', 'Leído reciente']);
});

it('filtra por tipo y enlaza el aviso a su origen', function () {
    $ta = crearTratamientoAplicado();
    crearAviso($this->usuario->id, ['tipo' => Aviso::SALDO_PENDIENTE, 'referencia_id' => $ta->id, 'mensaje' => 'Saldo']);
    crearAviso($this->usuario->id, ['tipo' => Aviso::SESION_SIN_CITA, 'referencia_id' => $ta->id, 'mensaje' => 'Sesión']);

    $this->actingAs($this->usuario)
        ->get(route('avisos.listar', ['tipo' => Aviso::SALDO_PENDIENTE]))
        ->assertOk()
        ->assertSee('Saldo')
        ->assertDontSee('>Sesión<', false)
        ->assertSee(route('pagos.cuenta', $ta->plan->paciente_id));
});

it('marca uno como leído', function () {
    $aviso = crearAviso($this->usuario->id);
    $otro = crearAviso($this->usuario->id);

    $this->actingAs($this->usuario)
        ->post(route('avisos.leer'), ['id' => $aviso->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Aviso marcado como leído.');

    expect($aviso->fresh()->leido)->toBeTrue()
        ->and($otro->fresh()->leido)->toBeFalse();
});

it('no permite marcar el aviso de otro odontólogo', function () {
    $ajeno = crearAviso(crearUsuario()->id);

    $this->actingAs($this->usuario)
        ->post(route('avisos.leer'), ['id' => $ajeno->id])
        ->assertSessionHasErrors(['id' => 'El aviso indicado no existe.']);

    expect($ajeno->fresh()->leido)->toBeFalse();
});

it('marca todos como leídos', function () {
    crearAviso($this->usuario->id);
    crearAviso($this->usuario->id);
    crearAviso($this->usuario->id, ['leido' => true]);
    $ajeno = crearAviso(crearUsuario()->id);

    $this->actingAs($this->usuario)
        ->post(route('avisos.leer'), ['todos' => '1'])
        ->assertSessionHas('exito', 'Se marcaron 2 avisos como leídos.');

    expect(Aviso::where('odontologo_id', $this->usuario->id)->where('leido', false)->count())->toBe(0)
        ->and($ajeno->fresh()->leido)->toBeFalse();
});

it('sin avisos muestra el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->get(route('avisos.listar'))
        ->assertOk()
        ->assertSee('No tiene avisos pendientes.');
});

it('la barra lateral muestra el contador de avisos sin leer solo si hay', function () {
    $this->actingAs($this->usuario)
        ->get(route('panel'))
        ->assertOk()
        ->assertDontSee('Avisos: ');

    crearAviso($this->usuario->id);
    crearAviso($this->usuario->id);

    $this->actingAs($this->usuario)
        ->get(route('panel'))
        ->assertOk()
        ->assertSee('Avisos: 2 sin leer');
});
