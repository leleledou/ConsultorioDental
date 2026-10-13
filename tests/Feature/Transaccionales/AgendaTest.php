<?php

use App\Models\EstadoCita;
use Carbon\Carbon;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU13 Consultar agenda (RF-72). Vista diaria, vista semanal y filtros.
 */

beforeEach(function () {
    // Lunes 12/10/2026 a las 08:00. La semana va del lunes 12 al sábado 17.
    $this->travelTo(Carbon::parse('2026-10-12 08:00'));
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo');
    $this->otro = crearUsuario('odontologo');

    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '09:00', 'motivo' => 'Cita del martes', 'odontologo_id' => $this->usuario->id]);
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '11:00', 'motivo' => 'Cita cancelada del martes', 'estado' => EstadoCita::CANCELADA, 'odontologo_id' => $this->otro->id]);
    crearCita(['fecha' => '2026-10-16', 'hora_inicio' => '15:00', 'motivo' => 'Cita del viernes', 'odontologo_id' => $this->otro->id]);
    crearCita(['fecha' => '2026-10-19', 'hora_inicio' => '10:00', 'motivo' => 'Cita de la otra semana']);
});

it('muestra la vista diaria con las citas del día y las franjas disponibles', function () {
    $respuesta = $this->actingAs($this->usuario)
        ->get(route('agenda', ['fecha' => '2026-10-13']))
        ->assertOk()
        ->assertSee('Cita del martes')
        ->assertSee('Cita cancelada del martes')
        ->assertDontSee('Cita del viernes')
        ->assertSee('09:00–10:00');

    // La franja de las 10:00 está libre: enlace a CU10 con fecha y hora cargadas.
    $respuesta->assertSee(e(route('citas.crear', ['fecha' => '2026-10-13', 'hora_inicio' => '10:00'])), false);
    // La de las 09:00 está ocupada: no se ofrece.
    $respuesta->assertDontSee(e(route('citas.crear', ['fecha' => '2026-10-13', 'hora_inicio' => '09:00'])), false);
    // La cancelada no ocupa el sillón: la franja de las 11:00 se ofrece.
    $respuesta->assertSee(e(route('citas.crear', ['fecha' => '2026-10-13', 'hora_inicio' => '11:00'])), false);
});

it('muestra la vista semanal de lunes a sábado', function () {
    $this->actingAs($this->usuario)
        ->get(route('agenda', ['vista' => 'semana', 'fecha' => '2026-10-14']))
        ->assertOk()
        ->assertSee('Cita del martes')
        ->assertSee('Cita del viernes')
        ->assertDontSee('Cita de la otra semana')
        ->assertSee('12/10 al 17/10/2026');
});

it('filtra por odontólogo', function () {
    $this->actingAs($this->usuario)
        ->get(route('agenda', ['vista' => 'semana', 'fecha' => '2026-10-12', 'odontologo_id' => $this->otro->id]))
        ->assertOk()
        ->assertSee('Cita del viernes')
        ->assertDontSee('Cita del martes');
});

it('filtra por estado', function () {
    $this->actingAs($this->usuario)
        ->get(route('agenda', ['fecha' => '2026-10-13', 'estado_cita_id' => EstadoCita::idDe(EstadoCita::CANCELADA)]))
        ->assertOk()
        ->assertSee('Cita cancelada del martes')
        ->assertDontSee('Cita del martes ·')
        // La cita programada oculta por el filtro sigue ocupando el sillón.
        ->assertSee('Ocupado (cita oculta por los filtros)');
});

it('rechaza una vista desconocida', function () {
    $this->actingAs($this->usuario)
        ->get(route('agenda', ['vista' => 'mes']))
        ->assertSessionHasErrors('vista');
});

it('redirige al login a un usuario sin sesión', function () {
    $this->get(route('agenda'))->assertRedirect(route('login'));
});
