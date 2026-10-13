<?php

use App\Models\Bitacora;
use App\Models\EstadoCita;
use App\Services\BitacoraService;
use Carbon\Carbon;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU12 Actualizar estado de cita (RF-71). Pruebas transaccionales.
 */

beforeEach(function () {
    // Lunes 12/10/2026 a las 18:00 (hoy).
    $this->travelTo(Carbon::parse('2026-10-12 18:00'));
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo');
});

it('marca como atendida una cita programada de hoy', function () {
    $cita = crearCita(['fecha' => '2026-10-12', 'hora_inicio' => '09:00']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), [
            'id' => $cita->id,
            'estado_cita_id' => EstadoCita::idDe(EstadoCita::ATENDIDA),
            'observacion' => 'Sin complicaciones',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    $cita->refresh()->load('estado');

    expect($cita->estado->nombre)->toBe(EstadoCita::ATENDIDA)
        ->and($cita->observaciones)->toContain('Atendida: Sin complicaciones');
});

it('marca como no asistida una cita programada de un día anterior', function () {
    $cita = crearCita(['fecha' => '2026-10-09', 'hora_inicio' => '10:00']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::NO_ASISTIDA)])
        ->assertSessionHasNoErrors();

    expect($cita->fresh('estado')->estado->nombre)->toBe(EstadoCita::NO_ASISTIDA);
});

it('rechaza marcar como atendida una cita futura', function () {
    $cita = crearCita(['fecha' => '2026-10-13']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::ATENDIDA)])
        ->assertSessionHasErrors(['operacion' => 'Solo se puede marcar como atendida o no asistida una cita de hoy o de días anteriores.']);

    expect($cita->fresh('estado')->estado->nombre)->toBe(EstadoCita::PROGRAMADA);
});

it('permite cancelar una cita futura desde el cambio de estado', function () {
    $cita = crearCita(['fecha' => '2026-10-13']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::CANCELADA)])
        ->assertSessionHasNoErrors();

    expect($cita->fresh('estado')->estado->nombre)->toBe(EstadoCita::CANCELADA);
});

it('rechaza cambiar una cita que ya está cancelada', function () {
    $cita = crearCita(['fecha' => '2026-10-12', 'estado' => EstadoCita::CANCELADA]);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::ATENDIDA)])
        ->assertSessionHasErrors(['operacion' => 'La cita ya tiene un estado final.']);

    expect($cita->fresh('estado')->estado->nombre)->toBe(EstadoCita::CANCELADA)
        ->and(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_CITA)->exists())->toBeFalse();
});

it('rechaza volver a programada como estado destino', function () {
    $cita = crearCita(['fecha' => '2026-10-12']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::PROGRAMADA)])
        ->assertSessionHasErrors('operacion');
});

it('registra el cambio de estado en la bitácora', function () {
    $cita = crearCita(['fecha' => '2026-10-12']);

    $this->actingAs($this->usuario)
        ->post(route('citas.estado'), ['id' => $cita->id, 'estado_cita_id' => EstadoCita::idDe(EstadoCita::ATENDIDA)]);

    expect(Bitacora::where('accion', BitacoraService::CAMBIO_ESTADO_CITA)
        ->where('tabla_afectada', 'citas')
        ->where('registro_id', $cita->id)
        ->where('usuario_id', $this->usuario->id)
        ->exists())->toBeTrue();
});

it('lista las citas del día elegido', function () {
    $hoy = crearCita(['fecha' => '2026-10-12', 'motivo' => 'Control de hoy']);
    crearCita(['fecha' => '2026-10-13', 'motivo' => 'Control de mañana']);

    $this->actingAs($this->usuario)
        ->get(route('citas.listar', ['fecha' => '2026-10-12']))
        ->assertOk()
        ->assertSee('Control de hoy')
        ->assertDontSee('Control de mañana')
        ->assertSee(route('citas.estado'));
});
