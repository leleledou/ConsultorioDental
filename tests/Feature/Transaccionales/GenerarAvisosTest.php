<?php

use App\Models\Aviso;
use App\Models\Bitacora;
use App\Services\AvisoService;
use App\Services\BitacoraService;
use Database\Seeders\EstadoCitaSeeder;
use Illuminate\Support\Carbon;

/*
 * CU40 Generar avisos internos (RF-81). Actor: Temporizador (comando avisos:generar),
 * con disparo manual desde la lista de avisos. Pruebas transaccionales.
 */

beforeEach(function () {
    $this->seed(EstadoCitaSeeder::class);
    Carbon::setTestNow('2026-11-20 09:00:00');

    // Un caso de cada tipo; los tratamientos sin deuda tienen costo 0 para no generar avisos de saldo.
    $this->odontologoCita = crearUsuario('odontologo');
    $this->cita = crearCita([
        'paciente_id' => crearPaciente(['nombres' => 'Juan', 'apellidos' => 'Pérez'])->id,
        'odontologo_id' => $this->odontologoCita->id,
        'fecha' => '2026-11-21',
        'hora_inicio' => '10:30',
    ]);

    // Control vencido de un tratamiento ya concluido.
    $this->taControl = crearTratamientoAplicado(['estado' => 'concluido', 'costo_acordado' => 0, 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-10-01']);
    $this->control = crearControl($this->taControl->id, ['fecha_prevista' => '2026-11-15', 'estado' => 'vencido']);

    // Saldo: costo 300, abonado 100.
    $this->taSaldo = crearTratamientoAplicado(['estado' => 'concluido', 'costo_acordado' => 300, 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-10-01']);
    crearPago($this->taSaldo->id, ['monto' => 100]);

    // Estudio obligatorio previo sin cargar.
    $implante = crearTratamiento(['nombre' => 'Implante unitario']);
    crearEstudioRequerido($implante->id, crearTipoEstudio(['nombre' => 'Tomografía cone beam'])->id);
    $this->taEstudio = crearTratamientoAplicado(['tratamiento_id' => $implante->id, 'costo_acordado' => 0, 'estado' => 'planificado']);

    // En proceso, 1 de 3 sesiones y sin cita futura.
    $this->taSesion = crearTratamientoAplicado(['estado' => 'en proceso', 'costo_acordado' => 0, 'sesiones_planificadas' => 3]);
    crearSesion($this->taSesion->id, ['numero_sesion' => 1, 'fecha' => '2026-11-10']);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('genera un aviso por cada uno de los cinco tipos, con sus datos de prueba', function () {
    $this->artisan('avisos:generar')
        ->expectsOutputToContain('Se generaron 5 avisos.')
        ->assertSuccessful();

    expect(Aviso::count())->toBe(5)
        ->and(Aviso::pluck('tipo')->sort()->values()->all())->toBe(collect(Aviso::TIPOS)->sort()->values()->all());

    $porTipo = Aviso::all()->keyBy('tipo');

    expect($porTipo[Aviso::CITA_PROXIMA]->referencia_id)->toBe($this->cita->id)
        ->and($porTipo[Aviso::CITA_PROXIMA]->mensaje)->toBe('Cita con Juan Pérez el 21/11/2026 a las 10:30.')
        ->and($porTipo[Aviso::CONTROL_PENDIENTE]->referencia_id)->toBe($this->control->id)
        ->and($porTipo[Aviso::SALDO_PENDIENTE]->referencia_id)->toBe($this->taSaldo->id)
        ->and($porTipo[Aviso::SALDO_PENDIENTE]->mensaje)->toContain('Bs 200,00')
        ->and($porTipo[Aviso::ESTUDIO_PENDIENTE]->referencia_id)->toBe($this->taEstudio->id)
        ->and($porTipo[Aviso::ESTUDIO_PENDIENTE]->mensaje)->toContain('Tomografía cone beam')
        ->and($porTipo[Aviso::SESION_SIN_CITA]->referencia_id)->toBe($this->taSesion->id)
        ->and($porTipo[Aviso::SESION_SIN_CITA]->mensaje)->toContain('2 sesiones pendientes')
        ->and(Aviso::where('leido', true)->count())->toBe(0);
});

it('el aviso va al odontólogo correcto', function () {
    app(AvisoService::class)->generar();

    $porTipo = Aviso::all()->keyBy('tipo');

    expect($porTipo[Aviso::CITA_PROXIMA]->odontologo_id)->toBe($this->odontologoCita->id)
        ->and($porTipo[Aviso::CONTROL_PENDIENTE]->odontologo_id)->toBe($this->taControl->odontologo_id)
        ->and($porTipo[Aviso::SALDO_PENDIENTE]->odontologo_id)->toBe($this->taSaldo->odontologo_id)
        ->and($porTipo[Aviso::ESTUDIO_PENDIENTE]->odontologo_id)->toBe($this->taEstudio->odontologo_id)
        ->and($porTipo[Aviso::SESION_SIN_CITA]->odontologo_id)->toBe($this->taSesion->odontologo_id);
});

it('NO duplica si ya existe uno no leído del mismo tipo y referencia', function () {
    app(AvisoService::class)->generar();

    $segunda = app(AvisoService::class)->generar();

    expect(array_sum($segunda))->toBe(0)
        ->and(Aviso::count())->toBe(5);

    $this->artisan('avisos:generar')
        ->expectsOutputToContain('No hay avisos nuevos que generar.')
        ->assertSuccessful();
});

it('SÍ genera uno nuevo si el anterior ya fue leído', function () {
    app(AvisoService::class)->generar();
    Aviso::where('tipo', Aviso::SALDO_PENDIENTE)->update(['leido' => true]);

    $segunda = app(AvisoService::class)->generar();

    expect($segunda[Aviso::SALDO_PENDIENTE])->toBe(1)
        ->and(array_sum($segunda))->toBe(1)
        ->and(Aviso::where('tipo', Aviso::SALDO_PENDIENTE)->where('referencia_id', $this->taSaldo->id)->count())->toBe(2);
});

it('registra GENERAR_AVISOS con usuario nulo y usuario_digitado sistema', function () {
    $this->artisan('avisos:generar')->assertSuccessful();
    // Sin avisos nuevos no se vuelve a escribir.
    $this->artisan('avisos:generar')->assertSuccessful();

    $registro = Bitacora::where('accion', BitacoraService::GENERAR_AVISOS)->sole();

    expect($registro->usuario_id)->toBeNull()
        ->and($registro->usuario_digitado)->toBe('sistema');
});

it('no avisa por una cita a más de 48 horas, ni por sesiones con cita futura', function () {
    $this->cita->update(['fecha' => '2026-11-23']);
    crearCita(['paciente_id' => $this->taSesion->plan->paciente_id, 'tratamiento_aplicado_id' => $this->taSesion->id, 'fecha' => '2026-11-27']);

    $detalle = app(AvisoService::class)->generar();

    expect($detalle[Aviso::CITA_PROXIMA])->toBe(0)
        ->and($detalle[Aviso::SESION_SIN_CITA])->toBe(0);
});

it('el botón manual genera los avisos con el usuario autenticado', function () {
    $usuario = crearUsuario('odontologo');

    $this->actingAs($usuario)
        ->post(route('avisos.generar'))
        ->assertSessionHas('exito', 'Se generaron 5 avisos.');

    expect(Bitacora::where('accion', BitacoraService::GENERAR_AVISOS)->sole()->usuario_id)->toBe($usuario->id);

    $this->actingAs($usuario)
        ->post(route('avisos.generar'))
        ->assertSessionHas('exito', 'No hay avisos nuevos que generar.');
});
