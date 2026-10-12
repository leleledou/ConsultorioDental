<?php

use App\Models\Bitacora;
use App\Models\Cita;
use App\Models\EstadoCita;
use App\Services\BitacoraService;
use App\Services\CitaService;
use Carbon\Carbon;
use Database\Seeders\EstadoCitaSeeder;

/*
 * CU10 Programar cita (RF-66 a RF-69). Pruebas transaccionales: regla del sillón único.
 */

beforeEach(function () {
    // Lunes 12/10/2026 a las 08:00 (hora de La Paz): "mañana" es el martes 13.
    $this->travelTo(Carbon::parse('2026-10-12 08:00'));
    $this->seed(EstadoCitaSeeder::class);

    $this->odontologo = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->tratamiento = crearTratamiento(['duracion_estimada_minutos' => 90]);
});

// Datos válidos del formulario de CU10; $cambios reemplaza algunos.
function datosCita(array $cambios = []): array
{
    return $cambios + [
        'paciente_id' => test()->paciente->id,
        'odontologo_id' => test()->odontologo->id,
        'tratamiento_id' => test()->tratamiento->id,
        'fecha' => '2026-10-13',
        'hora_inicio' => '09:00',
        'duracion_minutos' => 90,
        'motivo' => 'Dolor en molar inferior',
    ];
}

it('programa una cita válida con la hora de fin calculada por la base', function () {
    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('agenda', ['fecha' => '2026-10-13']))
        ->assertSessionHas('exito');

    $cita = Cita::with('estado')->sole();

    expect($cita->estado->nombre)->toBe(EstadoCita::PROGRAMADA)
        ->and($cita->hora_inicio)->toBe('09:00:00')
        ->and($cita->hora_fin)->toBe('10:30:00')
        ->and($cita->tratamiento_aplicado_id)->toBeNull()
        ->and($cita->fecha_registro->toDateString())->toBe('2026-10-12');

    expect(Bitacora::where('accion', BitacoraService::PROGRAMAR_CITA)->where('registro_id', $cita->id)->exists())->toBeTrue();
});

it('rechaza una superposición parcial aunque sea con otro odontólogo', function () {
    // Cita existente de 09:00 a 10:00 con OTRO odontólogo.
    $existente = crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '09:00', 'duracion_minutos' => 60]);

    $this->actingAs($this->odontologo)
        ->from(route('citas.crear'))
        ->post(route('citas.guardar'), datosCita(['hora_inicio' => '09:30', 'duracion_minutos' => 60]))
        ->assertRedirect(route('citas.crear'))
        ->assertSessionHasErrors('conflicto')
        ->assertSessionHasInput('hora_inicio', '09:30')
        ->assertSessionHas('franjas_libres');

    // El mensaje nombra al paciente y el horario en conflicto.
    $mensaje = session('errors')->first('conflicto');
    expect($mensaje)->toContain($existente->paciente->nombreCompleto())->toContain('09:00 a 10:00');

    // La franja libre más cercana empieza justo cuando termina la otra cita.
    expect(session('franjas_libres')[0])->toMatchArray(['inicio' => '10:00', 'sugerida' => '10:00']);

    expect(Cita::count())->toBe(1);
});

it('rechaza una cita que contiene por completo a otra', function () {
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '10:00', 'duracion_minutos' => 30]);

    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['hora_inicio' => '09:30', 'duracion_minutos' => 120]))
        ->assertSessionHasErrors('conflicto');

    expect(Cita::count())->toBe(1);
});

it('permite una cita que empieza justo cuando termina otra', function () {
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '08:00', 'duracion_minutos' => 60]);

    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['hora_inicio' => '09:00']))
        ->assertSessionHasNoErrors();

    expect(Cita::count())->toBe(2);
});

it('no bloquea la franja con una cita cancelada', function () {
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '09:00', 'duracion_minutos' => 90, 'estado' => EstadoCita::CANCELADA]);

    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita())
        ->assertSessionHasNoErrors();

    expect(Cita::count())->toBe(2);
});

it('rechaza una fecha pasada', function () {
    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['fecha' => '2026-10-11']))
        ->assertSessionHasErrors(['fecha' => 'La fecha de la cita no puede ser anterior a hoy.']);

    expect(Cita::count())->toBe(0);
});

it('rechaza una hora de hoy que ya pasó', function () {
    $this->travelTo(Carbon::parse('2026-10-12 11:00'));

    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['fecha' => '2026-10-12', 'hora_inicio' => '10:00']))
        ->assertSessionHasErrors(['hora_inicio' => 'La fecha y hora de la cita no pueden ser anteriores al momento actual.']);

    expect(Cita::count())->toBe(0);
});

it('rechaza una cita fuera del horario de atención', function () {
    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['hora_inicio' => '19:30', 'duracion_minutos' => 60]))
        ->assertSessionHasErrors('hora_inicio');

    expect(Cita::count())->toBe(0);
});

it('rechaza un paciente inhabilitado y una duración de 0', function () {
    $inactivo = crearPaciente(['estado' => 'inactivo']);

    $this->actingAs($this->odontologo)
        ->post(route('citas.guardar'), datosCita(['paciente_id' => $inactivo->id, 'duracion_minutos' => 0]))
        ->assertSessionHasErrors([
            'paciente_id' => 'El paciente no existe o está inhabilitado.',
            'duracion_minutos' => 'La duración debe ser mayor a 0 minutos.',
        ]);

    expect(Cita::count())->toBe(0);
});

it('propone como duración la duración estimada del tratamiento', function () {
    // Prueba del servicio (RF-67 / RN-08).
    expect(app(CitaService::class)->duracionPropuesta($this->tratamiento))->toBe(90);

    // El formulario lleva la duración en cada opción, solo con tratamientos disponibles.
    $deBaja = crearTratamiento(['nombre' => 'Tratamiento de baja', 'estado' => 'no disponible']);

    $this->actingAs($this->odontologo)
        ->get(route('citas.crear'))
        ->assertOk()
        ->assertSee('data-duracion="90"', false)
        ->assertDontSee($deBaja->nombre);
});

it('busca solo pacientes activos en el formulario', function () {
    crearPaciente(['apellidos' => 'Quispe', 'nombres' => 'Ana']);
    crearPaciente(['apellidos' => 'Quispe', 'nombres' => 'Beto', 'estado' => 'inactivo']);

    $this->actingAs($this->odontologo)
        ->get(route('citas.crear', ['buscar' => 'Quispe']))
        ->assertOk()
        ->assertSee('Quispe, Ana')
        ->assertDontSee('Quispe, Beto');
});

it('calcula las franjas libres del día sin contar las canceladas', function () {
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '09:00', 'duracion_minutos' => 60]);
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '12:00', 'duracion_minutos' => 30, 'estado' => EstadoCita::CANCELADA]);
    crearCita(['fecha' => '2026-10-13', 'hora_inicio' => '14:00', 'duracion_minutos' => 120]);

    $libres = app(CitaService::class)->franjasLibres('2026-10-13');

    expect(array_map(fn ($f) => $f['inicio'].'-'.$f['fin'], $libres))
        ->toBe(['08:00-09:00', '10:00-14:00', '16:00-20:00']);
});
