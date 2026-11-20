<?php

use App\Models\Bitacora;
use App\Services\BitacoraService;
use Illuminate\Support\Carbon;

/*
 * CU39 Actualizar controles vencidos (RF-80). Actor: Temporizador (comando controles:vencer),
 * con disparo manual desde la lista de controles. Pruebas transaccionales.
 */

beforeEach(function () {
    Carbon::setTestNow('2026-11-20 00:05:00');

    $this->ta = crearTratamientoAplicado(['fecha_inicio' => '2026-10-01']);
    $this->pasado = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-19']);
    $this->muyPasado = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-02']);
    $this->hoy = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-20']);
    $this->futuro = crearControl($this->ta->id, ['fecha_prevista' => '2026-12-01']);
    $this->cumplido = crearControl($this->ta->id, ['fecha_prevista' => '2026-11-05', 'estado' => 'cumplido', 'fecha_cumplimiento' => '2026-11-05']);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('el comando controles:vencer marca como vencidos solo los pendientes con fecha pasada', function () {
    $this->artisan('controles:vencer')
        ->expectsOutput('Se actualizaron 2 controles vencidos.')
        ->assertSuccessful();

    expect($this->pasado->fresh()->estado)->toBe('vencido')
        ->and($this->muyPasado->fresh()->estado)->toBe('vencido');
});

it('no toca los cumplidos ni los que vencen hoy o en el futuro', function () {
    $this->artisan('controles:vencer')->assertSuccessful();

    expect($this->hoy->fresh()->estado)->toBe('pendiente')
        ->and($this->futuro->fresh()->estado)->toBe('pendiente')
        ->and($this->cumplido->fresh()->estado)->toBe('cumplido');
});

it('registra VENCER_CONTROLES con usuario nulo y usuario_digitado sistema', function () {
    $this->artisan('controles:vencer')->assertSuccessful();

    $registro = Bitacora::where('accion', BitacoraService::VENCER_CONTROLES)->sole();

    expect($registro->usuario_id)->toBeNull()
        ->and($registro->usuario_digitado)->toBe('sistema')
        ->and($registro->tabla_afectada)->toBe('controles');
});

it('si no hay nada que vencer, no escribe en la bitácora', function () {
    $this->artisan('controles:vencer')->assertSuccessful();

    // Segunda corrida del mismo día: ya no queda nada por vencer.
    $this->artisan('controles:vencer')
        ->expectsOutput('No hay controles vencidos por actualizar.')
        ->assertSuccessful();

    expect(Bitacora::where('accion', BitacoraService::VENCER_CONTROLES)->count())->toBe(1);
});

it('el botón manual produce el mismo resultado, pero con el usuario autenticado', function () {
    $usuario = crearUsuario('odontologo');

    $this->actingAs($usuario)
        ->post(route('controles.vencer'))
        ->assertSessionHas('exito', 'Se actualizaron 2 controles vencidos.');

    expect($this->pasado->fresh()->estado)->toBe('vencido')
        ->and($this->muyPasado->fresh()->estado)->toBe('vencido')
        ->and($this->hoy->fresh()->estado)->toBe('pendiente')
        ->and($this->cumplido->fresh()->estado)->toBe('cumplido');

    $registro = Bitacora::where('accion', BitacoraService::VENCER_CONTROLES)->sole();
    expect($registro->usuario_id)->toBe($usuario->id)
        ->and($registro->usuario_digitado)->toBeNull();

    $this->actingAs($usuario)
        ->post(route('controles.vencer'))
        ->assertSessionHas('exito', 'No hay controles vencidos por actualizar.');
});
