<?php

use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use App\Services\ReporteService;
use Database\Seeders\EstadoPiezaSeeder;
use Database\Seeders\PiezaDentalSeeder;
use Illuminate\Support\Carbon;

/*
 * RF-84 Historia clínica imprimible y su exportación por CU45. Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed([PiezaDentalSeeder::class, EstadoPiezaSeeder::class]);
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente(['nombres' => 'Zenobia', 'apellidos' => 'Quispe Mamani']);
    $this->historia = crearHistoria($this->paciente->id);

    $this->historia->antecedentes()->attach(crearAntecedente(['nombre' => 'Hipertensión arterial'])->id, ['fecha_registro' => '2026-10-01']);

    $odontograma = crearOdontograma($this->historia->id, ['fecha_registro' => '2026-10-05']);
    DetalleOdontograma::create(['odontograma_id' => $odontograma->id, 'pieza_dental_id' => 36, 'estado_pieza_id' => EstadoPieza::where('codigo', 'CARIES')->value('id')]);

    $this->ta = crearTratamientoAplicado([
        'plan_tratamiento_id' => crearPlan(['paciente_id' => $this->paciente->id])->id,
        'tratamiento_id' => crearTratamiento(['nombre' => 'Endodoncia molar'])->id,
        'costo_acordado' => 1200,
        'sesiones_planificadas' => 3,
        'fecha_inicio' => '2026-10-06',
        'estado' => 'en proceso',
    ]);
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => '2026-10-06', 'avance_tecnico' => 'Apertura cameral y conductometría']);
    crearPago($this->ta->id, ['monto' => 500]);

    crearEstudioDiagnostico($this->paciente->id, crearTipoEstudio(['nombre' => 'Radiografía periapical'])->id);
    crearValoracionImplante($this->paciente->id, 46, ['resultado' => 'apto con condiciones']);
    crearControl($this->ta->id, ['motivo' => 'Control postendodoncia']);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('RF-84: el documento incluye antecedentes, odontograma, tratamientos, estudios, valoraciones, controles y el saldo', function () {
    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.historia', $this->paciente->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Zenobia Quispe Mamani',
            'Antecedentes médicos', 'Hipertensión arterial',
            'Odontograma', 'title="36 · Caries"', 'Caries: ',
            'Planes de tratamiento',
            'Tratamientos aplicados y sesiones', 'Endodoncia molar', 'Sesión 1 de 3', 'Apertura cameral y conductometría',
            'Estudios diagnósticos', 'Radiografía periapical',
            'Valoraciones para implante', 'Pieza 46',
            'Controles', 'Control postendodoncia',
            'Estado de cuenta', 'Bs 1.200,00', 'Bs 500,00', 'Bs 700,00',
        ], false)
        ->assertSee('Exportar a PDF');

    expect($respuesta->viewData('datos')['cuenta'])->toBe(['costo_total' => 1200.0, 'abonado' => 500.0, 'saldo' => 700.0])
        ->and($respuesta->viewData('datos')['dibujo']['piezas'][36]['estado'])->toBe('Caries');
});

it('exporta a PDF con el nombre correcto', function () {
    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', ['reporte' => 'historia', 'paciente' => $this->paciente->id, 'formato' => 'pdf']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('historia_clinica_quispe_mamani_2026-11-20.pdf');

    expect(substr($respuesta->getContent(), 0, 5))->toBe('%PDF-');
});

it('rechaza exportar a Excel con el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.historia', $this->paciente->id))
        ->get(route('reportes.exportar', ['reporte' => 'historia', 'paciente' => $this->paciente->id, 'formato' => 'excel']))
        ->assertRedirect(route('reportes.historia', $this->paciente->id))
        ->assertSessionHasErrors(['formato' => 'Este documento solo se puede exportar a PDF.']);
});

it('reutiliza la historia consolidada y avisa si el paciente no tiene historia', function () {
    $sinHistoria = crearPaciente();

    expect(app(ReporteService::class)->historiaClinica($sinHistoria->id))->toBeNull();

    $this->actingAs($this->usuario)
        ->get(route('reportes.historia', $sinHistoria->id))
        ->assertRedirect(route('pacientes.mostrar', $sinHistoria->id))
        ->assertSessionHasErrors(['operacion' => 'El paciente aún no tiene historia clínica registrada.']);
});
