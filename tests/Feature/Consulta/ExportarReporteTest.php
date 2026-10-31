<?php

use App\Models\Bitacora;
use App\Services\ReporteService;
use Database\Seeders\EstadoCitaSeeder;
use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/*
 * CU45 Exportar reporte. Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed(EstadoCitaSeeder::class);

    $this->usuario = crearUsuario('odontologo');
    $this->hoy = today()->format('Y-m-d');
    $this->ingresos = ['reporte' => 'financiero', 'modo' => 'estatico', 'tipo' => 'ingresos', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'];
});

// Abre el .xlsx de la respuesta con PhpSpreadsheet.
function hojaExcel(TestResponse $respuesta): Worksheet
{
    $archivo = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($archivo, $respuesta->getContent());
    $hoja = IOFactory::load($archivo)->getActiveSheet();
    unlink($archivo);

    return $hoja;
}

it('exporta a PDF con el nombre y la fecha', function () {
    crearPago(crearTratamientoAplicado()->id, ['metodo_pago_id' => crearMetodoPago(['nombre' => 'Efectivo'])->id, 'fecha_pago' => '2026-03-02']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', $this->ingresos + ['formato' => 'pdf']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertDownload("ingresos_{$this->hoy}.pdf");

    expect(substr($respuesta->getContent(), 0, 5))->toBe('%PDF-');
});

it('exporta a Excel con el nombre y la fecha', function () {
    $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', $this->ingresos + ['formato' => 'excel']))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
        ->assertDownload("ingresos_{$this->hoy}.xlsx");
});

it('rechaza un formato inválido', function (array $formato) {
    $this->actingAs($this->usuario)
        ->from(route('reportes.financieros'))
        ->get(route('reportes.exportar', $this->ingresos + $formato))
        ->assertSessionHasErrors(['formato' => 'El formato debe ser pdf o excel.']);
})->with([
    'otro' => [['formato' => 'csv']],
    'sin formato' => [[]],
]);

it('valida los parámetros del reporte al exportar', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.financieros'))
        ->get(route('reportes.exportar', ['fecha_inicio' => '2026-04-01'] + $this->ingresos + ['formato' => 'pdf']))
        ->assertSessionHasErrors(['fecha_inicio' => 'La fecha inicial no puede ser posterior a la fecha final.']);
});

it('el Excel coincide con el reporte en pantalla y los números son números', function () {
    $tratamiento = crearTratamientoAplicado();
    crearPago($tratamiento->id, ['metodo_pago_id' => crearMetodoPago(['nombre' => 'Efectivo'])->id, 'monto' => 1250.5, 'fecha_pago' => '2026-03-02']);
    crearPago($tratamiento->id, ['metodo_pago_id' => crearMetodoPago(['nombre' => 'QR'])->id, 'monto' => 25, 'fecha_pago' => '2026-03-03']);

    $pantalla = $this->actingAs($this->usuario)
        ->get(route('reportes.financieros', $this->ingresos))
        ->viewData('resultado');

    $hoja = hojaExcel($this->get(route('reportes.exportar', $this->ingresos + ['formato' => 'excel'])));

    expect($hoja->getCell('A1')->getValue())->toBe($pantalla['titulo'])
        ->and($hoja->getCell('A2')->getValue())->toBe($pantalla['periodo'])
        ->and([$hoja->getCell('A3')->getValue(), $hoja->getCell('B3')->getValue(), $hoja->getCell('C3')->getValue()])->toBe(array_values($pantalla['columnas']));

    foreach ($pantalla['filas'] as $indice => $fila) {
        $numero = 4 + $indice;

        expect($hoja->getCell("A{$numero}")->getValue())->toBe($fila['metodo_pago'])
            ->and((float) $hoja->getCell("B{$numero}")->getValue())->toBe((float) $fila['cantidad'])
            ->and((float) $hoja->getCell("C{$numero}")->getValue())->toBe($fila['total'])
            ->and($hoja->getCell("B{$numero}")->getDataType())->toBe(DataType::TYPE_NUMERIC)
            ->and($hoja->getCell("C{$numero}")->getDataType())->toBe(DataType::TYPE_NUMERIC)
            ->and($hoja->getStyle("C{$numero}")->getNumberFormat()->getFormatCode())->toBe('#,##0.00');
    }

    expect($hoja->getCell('A6')->getValue())->toBe('Abonos')
        ->and((float) $hoja->getCell('B6')->getValue())->toBe(2.0)
        ->and($hoja->getCell('A7')->getValue())->toBe('Total ingresos')
        ->and((float) $hoja->getCell('B7')->getValue())->toBe(1275.5)
        ->and($hoja->getCell('B7')->getDataType())->toBe(DataType::TYPE_NUMERIC);
});

it('un dinámico exporta solo las columnas marcadas, con las fechas como texto', function () {
    crearPago(crearTratamientoAplicado()->id, ['monto' => 80, 'fecha_pago' => '2026-03-05', 'numero_comprobante' => 'REC-0001']);

    $parametros = [
        'reporte' => 'financiero',
        'modo' => 'dinamico',
        'columnas' => ['fecha_pago', 'comprobante', 'monto'],
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-03-31',
    ];

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', $parametros + ['formato' => 'excel']))
        ->assertDownload("reporte_financiero_{$this->hoy}.xlsx");

    $hoja = hojaExcel($respuesta);

    expect($hoja->getHighestColumn())->toBe('C')
        ->and($hoja->getCell('A3')->getValue())->toBe('Comprobante')
        ->and($hoja->getCell('B3')->getValue())->toBe('Fecha de pago')
        ->and($hoja->getCell('B4')->getValue())->toBe('05/03/2026')
        ->and($hoja->getCell('B4')->getDataType())->toBe(DataType::TYPE_STRING);
});

it('escribe como texto lo que parece una fórmula', function () {
    $datos = [
        'titulo' => 'T', 'nombre_archivo' => 'n', 'periodo' => 'p',
        'columnas' => ['motivo' => 'Motivo'], 'numericas' => [], 'montos' => [],
        'filas' => [['motivo' => '=1+1']], 'totales' => [],
    ];

    $archivo = tempnam(sys_get_temp_dir(), 'xlsx');
    file_put_contents($archivo, app(ReporteService::class)->exportarExcel($datos));
    $celda = IOFactory::load($archivo)->getActiveSheet()->getCell('A4');
    unlink($archivo);

    expect($celda->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($celda->getValue())->toBe('=1+1');
});

it('un reporte sin filas exporta Sin registros', function () {
    $hoja = hojaExcel($this->actingAs($this->usuario)->get(route('reportes.exportar', $this->ingresos + ['formato' => 'excel'])));

    expect($hoja->getCell('A4')->getValue())->toBe('Sin registros');

    $this->get(route('reportes.exportar', $this->ingresos + ['formato' => 'pdf']))->assertOk();

    $html = view('reportes.pdf', ['datos' => app(ReporteService::class)->estatico('ingresos', ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31'])])->render();

    expect($html)->toContain('Sin registros')
        ->toContain('Consultorio Odontológico DentalRox');
});

it('los montos del PDF usan Bs y formato boliviano', function () {
    crearPago(crearTratamientoAplicado()->id, ['metodo_pago_id' => crearMetodoPago(['nombre' => 'Efectivo'])->id, 'monto' => 1250, 'fecha_pago' => '2026-03-02']);

    $datos = app(ReporteService::class)->estatico('ingresos', ['fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31']);

    expect(view('reportes.pdf', ['datos' => $datos])->render())->toContain('Bs 1.250,00');
});

it('el reporte de más de 6 columnas va en horizontal', function () {
    crearCita(['fecha' => '2026-03-10']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('reportes.exportar', ['reporte' => 'agenda', 'modo' => 'estatico', 'tipo' => 'agenda_citas', 'fecha_inicio' => '2026-03-01', 'fecha_fin' => '2026-03-31', 'formato' => 'pdf']))
        ->assertOk();

    // Carta horizontal: 792 x 612 puntos.
    expect($respuesta->getContent())->toMatch('/MediaBox\s*\[\s*0\.0+\s+0\.0+\s+792\.0+\s+612\.0+/');
});

it('exportar no registra nada en la bitácora ni guarda archivos', function () {
    $antes = glob(storage_path('app/*')) ?: [];

    $this->actingAs($this->usuario)->get(route('reportes.exportar', $this->ingresos + ['formato' => 'excel']));
    $this->actingAs($this->usuario)->get(route('reportes.exportar', $this->ingresos + ['formato' => 'pdf']));

    expect(Bitacora::count())->toBe(0)
        ->and(glob(storage_path('app/*')) ?: [])->toBe($antes);
});
