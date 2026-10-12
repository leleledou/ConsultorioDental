<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\EstadoCita;
use App\Services\BitacoraService;
use App\Services\CitaService;
use Carbon\Carbon;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU11 Reprogramar o cancelar cita (RF-70). Pruebas transaccionales.
 */

beforeEach(function () {
    // Lunes 12/10/2026 a las 08:00: las citas de prueba son del martes 13.
    $this->travelTo(Carbon::parse('2026-10-12 08:00'));
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo');
    // Cita original: martes 13, de 09:00 a 10:00.
    $this->cita = crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '09:00', 'duracion_minutos' => 60]);
});

// Nombre del estado actual de una cita, leído de la base.
function estadoDe(Cita $cita): string
{
    return $cita->fresh('estado')->estado->nombre;
}

it('reprograma creando una cita nueva y cancelando la original con la observación', function () {
    $this->actingAs($this->usuario)
        ->post(route('citas.reprogramar'), [
            'id' => $this->cita->id,
            'fecha' => '2026-10-14',
            'hora_inicio' => '11:00',
            'motivo_cambio' => 'El paciente viaja el martes',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    $nueva = Cita::where('id', '!=', $this->cita->id)->sole();

    // La nueva: mismo paciente, odontólogo y duración, nuevo horario, programada.
    expect(estadoDe($nueva))->toBe(EstadoCita::PROGRAMADA)
        ->and($nueva->paciente_id)->toBe($this->cita->paciente_id)
        ->and($nueva->odontologo_id)->toBe($this->cita->odontologo_id)
        ->and($nueva->duracion_minutos)->toBe(60)
        ->and($nueva->fecha->toDateString())->toBe('2026-10-14')
        ->and($nueva->hora_fin)->toBe('12:00:00');

    // La original se conserva, cancelada y con la referencia a la nueva.
    expect(estadoDe($this->cita))->toBe(EstadoCita::CANCELADA)
        ->and($this->cita->fresh()->observaciones)
        ->toContain("Reprogramada a la cita N.º {$nueva->id}: El paciente viaja el martes");

    expect(Bitacora::where('accion', BitacoraService::REPROGRAMAR_CITA)->where('registro_id', $this->cita->id)->exists())->toBeTrue();
});

it('permite reprogramar dentro de la propia franja de la cita', function () {
    // De 09:00-10:00 a 09:30-10:30: solo se cruza con ella misma.
    $this->actingAs($this->usuario)
        ->post(route('citas.reprogramar'), [
            'id' => $this->cita->id,
            'fecha' => '2026-10-13',
            'hora_inicio' => '09:30',
            'motivo_cambio' => 'Llega más tarde',
        ])
        ->assertSessionHasNoErrors();

    expect(Cita::count())->toBe(2);
});

it('no cambia nada al reprogramar a una franja ocupada', function () {
    $otra = crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '11:00', 'duracion_minutos' => 60]);

    $this->actingAs($this->usuario)
        ->from(route('citas.mostrar', $this->cita->id))
        ->post(route('citas.reprogramar'), [
            'formulario' => 'reprogramar',
            'id' => $this->cita->id,
            'fecha' => '2026-10-13',
            'hora_inicio' => '11:30',
            'motivo_cambio' => 'Cambio de horario',
        ])
        ->assertRedirect(route('citas.mostrar', $this->cita->id))
        ->assertSessionHasErrors('conflicto')
        ->assertSessionHas('franjas_libres');

    expect(Cita::count())->toBe(2)
        ->and(estadoDe($this->cita))->toBe(EstadoCita::PROGRAMADA)
        ->and($this->cita->fresh()->observaciones)->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::REPROGRAMAR_CITA)->exists())->toBeFalse();
});

it('exige el motivo del cambio', function () {
    $this->actingAs($this->usuario)
        ->post(route('citas.reprogramar'), [
            'id' => $this->cita->id,
            'fecha' => '2026-10-14',
            'hora_inicio' => '11:00',
        ])
        ->assertSessionHasErrors(['motivo_cambio' => 'Debe indicar el motivo del cambio.']);

    expect(Cita::count())->toBe(1);
});

it('cancela con motivo y libera la franja', function () {
    $this->actingAs($this->usuario)
        ->post(route('citas.cancelar'), ['id' => $this->cita->id, 'motivo' => 'Paciente enfermo'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    expect(estadoDe($this->cita))->toBe(EstadoCita::CANCELADA)
        ->and($this->cita->fresh()->observaciones)->toContain('Cancelada: Paciente enfermo')
        ->and(Bitacora::where('accion', BitacoraService::CANCELAR_CITA)->where('registro_id', $this->cita->id)->exists())->toBeTrue();

    // La franja de 09:00 a 10:00 quedó libre para otra cita.
    expect(app(CitaService::class)->buscarConflicto('2026-10-13', '09:00', 60))->toBeNull();

    $this->actingAs($this->usuario)
        ->post(route('citas.guardar'), [
            'paciente_id' => crearPaciente()->id,
            'odontologo_id' => $this->usuario->id,
            'tratamiento_id' => crearTratamiento()->id,
            'fecha' => '2026-10-13',
            'hora_inicio' => '09:00',
            'duracion_minutos' => 60,
        ])
        ->assertSessionHasNoErrors();
});

it('exige el motivo de la cancelación', function () {
    $this->actingAs($this->usuario)
        ->post(route('citas.cancelar'), ['id' => $this->cita->id])
        ->assertSessionHasErrors(['motivo' => 'Debe indicar el motivo de la cancelación.']);

    expect(estadoDe($this->cita))->toBe(EstadoCita::PROGRAMADA);
});

it('no permite reprogramar ni cancelar una cita atendida', function () {
    $atendida = crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '15:00', 'estado' => EstadoCita::ATENDIDA]);

    $this->actingAs($this->usuario)
        ->post(route('citas.reprogramar'), [
            'id' => $atendida->id,
            'fecha' => '2026-10-14',
            'hora_inicio' => '11:00',
            'motivo_cambio' => 'Cambio',
        ])
        ->assertSessionHasErrors(['operacion' => 'La cita ya tiene un estado final.']);

    $this->actingAs($this->usuario)
        ->post(route('citas.cancelar'), ['id' => $atendida->id, 'motivo' => 'Cambio'])
        ->assertSessionHasErrors(['operacion' => 'La cita ya tiene un estado final.']);

    expect(estadoDe($atendida))->toBe(EstadoCita::ATENDIDA)
        ->and(Cita::count())->toBe(2);
});

it('muestra la ficha con las acciones de reprogramar y cancelar', function () {
    $this->actingAs($this->usuario)
        ->get(route('citas.mostrar', $this->cita->id))
        ->assertOk()
        ->assertSee($this->cita->paciente->nombreCompleto())
        ->assertSee('09:00 – 10:00')
        ->assertSee(route('citas.reprogramar'))
        ->assertSee(route('citas.cancelar'));
});
