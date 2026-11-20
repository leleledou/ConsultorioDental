<?php

use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use App\Services\ReporteService;
use Database\Seeders\EstadoPiezaSeeder;
use Database\Seeders\PiezaDentalSeeder;
use Illuminate\Support\Carbon;

/*
 * RF-85 Comparativo de odontogramas y su exportación por CU45. Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed([PiezaDentalSeeder::class, EstadoPiezaSeeder::class]);
    Carbon::setTestNow('2026-11-20 10:00:00');

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente(['apellidos' => 'Fernández']);
    $this->historia = crearHistoria($this->paciente->id);
    $this->servicio = app(ReporteService::class);
});

afterEach(function () {
    Carbon::setTestNow();
});

// Registra en el odontograma cada pieza (código FDI => código de estado).
function cargarPiezasReporte(int $odontogramaId, array $piezas): void
{
    foreach ($piezas as $fdi => $codigo) {
        DetalleOdontograma::create([
            'odontograma_id' => $odontogramaId,
            'pieza_dental_id' => $fdi,
            'estado_pieza_id' => EstadoPieza::where('codigo', $codigo)->value('id'),
        ]);
    }
}

// Inicial, dos de evolución y uno de evolución anulado (el más reciente).
function odontogramasDelPaciente($test): array
{
    $inicial = crearOdontograma($test->historia->id, ['fecha_registro' => '2026-01-10']);
    $intermedio = crearOdontograma($test->historia->id, ['tipo' => 'evolución', 'fecha_registro' => '2026-05-10']);
    $ultimo = crearOdontograma($test->historia->id, ['tipo' => 'evolución', 'fecha_registro' => '2026-10-10']);
    $anulado = crearOdontograma($test->historia->id, ['tipo' => 'evolución', 'fecha_registro' => '2026-11-01', 'estado' => 'anulado']);

    cargarPiezasReporte($inicial->id, [11 => 'SANO', 12 => 'CARIES', 16 => 'CARIES', 26 => 'SANO', 36 => 'SANO']);
    cargarPiezasReporte($intermedio->id, [11 => 'SANO', 12 => 'RESINA', 16 => 'CARIES', 26 => 'SANO', 36 => 'SANO']);
    // 12 y 16 pasan a resina, 36 a ausente, 26 desaparece y 46 aparece: 5 cambios de 6 piezas.
    cargarPiezasReporte($ultimo->id, [11 => 'SANO', 12 => 'RESINA', 16 => 'RESINA', 36 => 'AUSENTE', 46 => 'IMPLANTE']);
    cargarPiezasReporte($anulado->id, [11 => 'CARIES']);

    return compact('inicial', 'intermedio', 'ultimo', 'anulado');
}

it('RF-85: sin parámetros toma el inicial y el último de evolución', function () {
    $o = odontogramasDelPaciente($this);

    $datos = $this->servicio->comparativoOdontograma($this->paciente->id, null, null);

    expect($datos['aviso'])->toBeNull()
        ->and($datos['a']->id)->toBe($o['inicial']->id)
        ->and($datos['b']->id)->toBe($o['ultimo']->id);

    $this->actingAs($this->usuario)
        ->get(route('reportes.odontogramas', $this->paciente->id))
        ->assertOk()
        ->assertSee('Odontograma A')
        ->assertSee('Odontograma B')
        ->assertSee('Piezas que cambiaron')
        ->assertSee('Exportar a PDF');
});

it('con a y b compara esos dos, y rechaza uno solo o el mismo dos veces', function () {
    $o = odontogramasDelPaciente($this);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.odontogramas', [$this->paciente->id, 'a' => $o['inicial']->id, 'b' => $o['intermedio']->id]))
        ->assertOk();

    expect($respuesta->viewData('datos')['resumen']['cambiaron'])->toBe(1);

    $this->actingAs($this->usuario)
        ->get(route('reportes.odontogramas', [$this->paciente->id, 'a' => $o['inicial']->id, 'b' => $o['inicial']->id]))
        ->assertSessionHasErrors(['operacion' => 'Seleccione dos odontogramas diferentes para comparar.']);

    $ajeno = crearOdontograma(crearHistoria(crearPaciente()->id)->id);

    $this->actingAs($this->usuario)
        ->get(route('reportes.odontogramas', [$this->paciente->id, 'a' => $o['inicial']->id, 'b' => $ajeno->id]))
        ->assertSessionHasErrors(['b' => 'El odontograma B no pertenece al paciente.']);
});

it('el resumen cuenta bien las piezas que cambiaron', function () {
    odontogramasDelPaciente($this);

    $resumen = $this->servicio->comparativoOdontograma($this->paciente->id, null, null)['resumen'];

    expect($resumen['total_piezas'])->toBe(6)
        ->and($resumen['cambiaron'])->toBe(5)
        ->and($resumen['sin_cambio'])->toBe(1)
        ->and(collect($resumen['por_estado'])->mapWithKeys(fn ($g) => [$g['estado'] => $g['piezas']])->all())->toBe([
            'Obturado con resina' => [12, 16],
            'No registrada' => [26],
            'Ausente o perdido' => [36],
            'Implante' => [46],
        ]);
});

it('un paciente con menos de dos odontogramas devuelve el mensaje exacto', function () {
    crearOdontograma($this->historia->id);

    expect($this->servicio->comparativoOdontograma($this->paciente->id, null, null)['aviso'])
        ->toBe('El paciente no tiene dos odontogramas para comparar.');

    $this->actingAs($this->usuario)
        ->get(route('reportes.odontogramas', $this->paciente->id))
        ->assertOk()
        ->assertSee('El paciente no tiene dos odontogramas para comparar.');

    $this->actingAs($this->usuario)
        ->from(route('reportes.odontogramas', $this->paciente->id))
        ->get(route('reportes.exportar', ['reporte' => 'odontogramas', 'paciente' => $this->paciente->id, 'formato' => 'pdf']))
        ->assertSessionHasErrors(['operacion' => 'El paciente no tiene dos odontogramas para comparar.']);
});

it('exporta a PDF', function () {
    $o = odontogramasDelPaciente($this);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', ['reporte' => 'odontogramas', 'paciente' => $this->paciente->id, 'a' => $o['inicial']->id, 'b' => $o['ultimo']->id, 'formato' => 'pdf']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload('comparativo_odontograma_fernandez_2026-11-20.pdf');

    expect(substr($respuesta->getContent(), 0, 5))->toBe('%PDF-');

    $this->actingAs($this->usuario)
        ->from(route('reportes.odontogramas', $this->paciente->id))
        ->get(route('reportes.exportar', ['reporte' => 'odontogramas', 'paciente' => $this->paciente->id, 'formato' => 'excel']))
        ->assertSessionHasErrors(['formato' => 'Este documento solo se puede exportar a PDF.']);
});
