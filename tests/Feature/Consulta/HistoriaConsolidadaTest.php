<?php

use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use App\Services\PagoService;
use Database\Seeders\EstadoPiezaSeeder;
use Database\Seeders\PiezaDentalSeeder;

/*
 * CU24 Consultar historia clínica consolidada (RF-13). Pruebas de consulta.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente(['nombres' => 'Zenobia']);
    $this->historia = crearHistoria($this->paciente->id);
    $this->plan = crearPlan(['paciente_id' => $this->paciente->id]);
    $this->ta = crearTratamientoAplicado([
        'plan_tratamiento_id' => $this->plan->id,
        'tratamiento_id' => crearTratamiento(['nombre' => 'Endodoncia molar'])->id,
        'costo_acordado' => 1250,
        'sesiones_planificadas' => 3,
    ]);
});

it('muestra las secciones con datos del paciente', function () {
    $this->seed([PiezaDentalSeeder::class, EstadoPiezaSeeder::class]);
    $odontograma = crearOdontograma($this->historia->id);
    DetalleOdontograma::create(['odontograma_id' => $odontograma->id, 'pieza_dental_id' => 16, 'estado_pieza_id' => EstadoPieza::where('codigo', 'CARIES')->value('id')]);

    $this->historia->antecedentes()->attach(crearAntecedente(['nombre' => 'Diabetes tipo 2'])->id, ['fecha_registro' => today()]);
    crearEstudioDiagnostico($this->paciente->id, crearTipoEstudio(['nombre' => 'Radiografía panorámica'])->id);

    $this->actingAs($this->usuario)
        ->get(route('historias.consolidada', $this->paciente->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Zenobia',
            'Antecedentes médicos', 'Diabetes tipo 2',
            'Odontograma', '1 odontograma registrado', 'Caries: ',
            'Planes de tratamiento',
            'Tratamientos aplicados', 'Endodoncia molar',
            'Estudios diagnósticos', 'Radiografía panorámica',
            'Valoraciones para implante', 'El paciente no tiene valoraciones para implante.',
            'Estado de cuenta',
        ]);
});

it('el saldo mostrado coincide con el de PagoService', function () {
    crearPago($this->ta->id, ['monto' => 400]);
    crearPago($this->ta->id, ['monto' => 100, 'estado' => 'anulado', 'motivo_anulacion' => 'Error de digitación']);

    $saldo = app(PagoService::class)->saldoPendiente($this->ta);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('historias.consolidada', $this->paciente->id))
        ->assertOk();

    expect($saldo)->toBe(850.0)
        ->and($respuesta->viewData('cuenta'))->toBe(['costo_total' => 1250.0, 'abonado' => 400.0, 'saldo' => 850.0]);

    $respuesta->assertSee('Bs '.number_format($saldo, 2, ',', '.'));
});

it('un paciente sin historia clínica devuelve el mensaje exacto', function () {
    $sinHistoria = crearPaciente();

    $this->actingAs($this->usuario)
        ->get(route('historias.consolidada', $sinHistoria->id))
        ->assertRedirect(route('pacientes.mostrar', $sinHistoria->id))
        ->assertSessionHasErrors(['operacion' => 'El paciente aún no tiene historia clínica registrada.']);
});

it('el avance de cada tratamiento muestra «Sesión n de m» correctamente', function () {
    crearSesion($this->ta->id, ['numero_sesion' => 1]);
    crearSesion($this->ta->id, ['numero_sesion' => 2]);
    // Una sesión anulada no cuenta como avance.
    crearSesion($this->ta->id, ['numero_sesion' => 3, 'estado' => 'anulada']);

    $this->actingAs($this->usuario)
        ->get(route('historias.consolidada', $this->paciente->id))
        ->assertOk()
        ->assertSee('Sesión 2 de 3');
});
