<?php

use App\Models\Bitacora;
use App\Models\EstudioDiagnostico;
use App\Services\BitacoraService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * CU32 Gestionar estudios diagnósticos (RF-52 a RF-57, RF-59). Pruebas transaccionales.
 */

beforeEach(function () {
    Storage::fake('public');

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->tipo = crearTipoEstudio(['nombre' => 'Radiografía periapical']);
});

// Datos válidos de un estudio con enlace; $cambios reemplaza algunos.
function datosEstudio(int $pacienteId, int $tipoId, array $cambios = []): array
{
    return $cambios + [
        'paciente_id' => $pacienteId,
        'tipo_estudio_id' => $tipoId,
        'fecha_estudio' => today()->toDateString(),
        'centro_radiologico' => 'Centro Imagen Sur',
        'hallazgos' => 'Lesión periapical',
    ];
}

// PDF falso del tamaño indicado en KB.
function pdfFalso(string $nombre = 'estudio.pdf', int $kb = 300): UploadedFile
{
    return UploadedFile::fake()->create($nombre, $kb, 'application/pdf');
}

it('registra un estudio con archivo y lo guarda en el disco', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, ['archivo' => pdfFalso()]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estudio diagnóstico registrado correctamente.');

    $estudio = EstudioDiagnostico::first();

    expect($estudio->ruta_archivo)->toStartWith('estudios/')
        ->and($estudio->odontologo_id)->toBe($this->usuario->id)
        ->and($estudio->fecha_registro->toDateString())->toBe(today()->toDateString());

    Storage::disk('public')->assertExists($estudio->ruta_archivo);
});

it('registra un estudio con enlace y no toca el disco', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, ['enlace' => 'https://pacs.centro.bo/estudio/123']))
        ->assertSessionHasNoErrors();

    expect(EstudioDiagnostico::first()->ruta_archivo)->toBe('https://pacs.centro.bo/estudio/123')
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('rechaza sin archivo ni enlace, con el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id))
        ->assertSessionHasErrors(['archivo' => 'Debe adjuntar un archivo o registrar un enlace.']);

    expect(EstudioDiagnostico::count())->toBe(0);
});

it('rechaza archivo y enlace juntos, con el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, [
            'archivo' => pdfFalso(),
            'enlace' => 'https://pacs.centro.bo/estudio/123',
        ]))
        ->assertSessionHasErrors(['archivo' => 'Registre el archivo o el enlace, no ambos.']);

    expect(EstudioDiagnostico::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('rechaza un formato no admitido', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, [
            'archivo' => UploadedFile::fake()->create('informe.docx', 50, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]))
        ->assertSessionHasErrors(['archivo' => 'Formato no admitido. Use PDF, JPG o PNG.']);

    expect(EstudioDiagnostico::count())->toBe(0);
});

it('rechaza un archivo de más de 10 MB con el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, ['archivo' => pdfFalso('tomografia.pdf', 10241)]))
        ->assertSessionHasErrors(['archivo' => 'El archivo supera los 10 MB. Registre el estudio mediante un enlace.']);

    expect(EstudioDiagnostico::count())->toBe(0);
});

it('rechaza un enlace que no es URL válida', function (string $enlace) {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, ['enlace' => $enlace]))
        ->assertSessionHasErrors(['enlace' => 'El enlace debe ser una dirección web válida.']);
})->with(['texto' => 'no es una url', 'sin protocolo web' => 'ftp://centro.bo/estudio']);

it('rechaza fecha futura', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, [
            'fecha_estudio' => today()->addDay()->toDateString(),
            'enlace' => 'https://pacs.centro.bo/1',
        ]))
        ->assertSessionHasErrors(['fecha_estudio' => 'La fecha del estudio no puede ser futura.']);
});

it('rechaza un tratamiento aplicado de otro paciente', function () {
    $ajeno = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, [
            'archivo' => pdfFalso(),
            'tratamiento_aplicado_id' => $ajeno->id,
        ]))
        ->assertSessionHasErrors(['tratamiento_aplicado_id' => 'El tratamiento aplicado no pertenece a este paciente.']);

    expect(EstudioDiagnostico::count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('al modificar con archivo nuevo, borra el anterior del disco', function () {
    $anterior = pdfFalso('viejo.pdf')->store('estudios', 'public');
    $estudio = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => $anterior]);

    $this->actingAs($this->usuario)
        ->post(route('estudios.modificar'), [
            'id' => $estudio->id,
            'tipo_estudio_id' => $this->tipo->id,
            'fecha_estudio' => today()->toDateString(),
            'archivo' => pdfFalso('nuevo.pdf'),
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Estudio diagnóstico actualizado correctamente.');

    $nueva = $estudio->fresh()->ruta_archivo;

    expect($nueva)->not->toBe($anterior)
        ->and(Bitacora::where('accion', BitacoraService::MODIFICAR_ESTUDIO)->where('registro_id', $estudio->id)->exists())->toBeTrue();

    Storage::disk('public')->assertMissing($anterior);
    Storage::disk('public')->assertExists($nueva);
});

it('al modificar sin archivo ni enlace conserva el archivo actual', function () {
    $actual = pdfFalso()->store('estudios', 'public');
    $estudio = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => $actual]);

    $this->actingAs($this->usuario)
        ->post(route('estudios.modificar'), [
            'id' => $estudio->id,
            'tipo_estudio_id' => $this->tipo->id,
            'fecha_estudio' => today()->toDateString(),
            'hallazgos' => 'Corregido',
        ])
        ->assertSessionHasNoErrors();

    expect($estudio->fresh()->ruta_archivo)->toBe($actual)
        ->and($estudio->fresh()->hallazgos)->toBe('Corregido');

    Storage::disk('public')->assertExists($actual);
});

it('al eliminar, borra el archivo del disco', function () {
    $ruta = pdfFalso()->store('estudios', 'public');
    $estudio = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => $ruta]);

    $this->actingAs($this->usuario)
        ->post(route('estudios.eliminar'), ['id' => $estudio->id])
        ->assertRedirect(route('estudios.listar', $this->paciente->id))
        ->assertSessionHas('exito', 'Estudio diagnóstico eliminado.');

    expect(EstudioDiagnostico::find($estudio->id))->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::ELIMINAR_ESTUDIO)->exists())->toBeTrue();

    Storage::disk('public')->assertMissing($ruta);
});

it('no elimina un estudio que respalda una valoración para implante', function () {
    $estudio = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => 'https://pacs.centro.bo/9']);
    crearValoracionImplante($this->paciente->id, crearPiezaDental(36)->id, ['estudio_diagnostico_id' => $estudio->id]);

    $this->actingAs($this->usuario)
        ->post(route('estudios.eliminar'), ['id' => $estudio->id])
        ->assertSessionHasErrors(['operacion' => 'No se puede eliminar: el estudio respalda una valoración para implante.']);

    expect(EstudioDiagnostico::find($estudio->id))->not->toBeNull();
});

it('registra REGISTRAR_ESTUDIO en la bitácora', function () {
    $this->actingAs($this->usuario)
        ->post(route('estudios.guardar'), datosEstudio($this->paciente->id, $this->tipo->id, ['enlace' => 'https://pacs.centro.bo/1']))
        ->assertSessionHasNoErrors();

    expect(Bitacora::where('accion', BitacoraService::REGISTRAR_ESTUDIO)
        ->where('usuario_id', $this->usuario->id)
        ->where('tabla_afectada', 'estudios_diagnosticos')
        ->where('registro_id', EstudioDiagnostico::first()->id)
        ->exists())->toBeTrue();
});

it('descarga el archivo y redirige si es un enlace', function () {
    $conArchivo = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => pdfFalso()->store('estudios', 'public')]);
    $conEnlace = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => 'https://pacs.centro.bo/7']);

    $this->actingAs($this->usuario)->get(route('estudios.descargar', $conArchivo->id))->assertOk()->assertDownload();
    $this->actingAs($this->usuario)->get(route('estudios.descargar', $conEnlace->id))->assertRedirect('https://pacs.centro.bo/7');

    // La ficha muestra la descarga del PDF y el enlace externo.
    $this->actingAs($this->usuario)->get(route('estudios.mostrar', $conArchivo->id))->assertOk()->assertSee('Descargar PDF');
    $this->actingAs($this->usuario)->get(route('estudios.mostrar', $conEnlace->id))->assertOk()->assertSee('Abrir enlace externo');
});

it('lista los estudios del paciente y filtra por tipo', function () {
    $otroTipo = crearTipoEstudio(['nombre' => 'Tomografía cone beam']);
    crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => 'https://a.bo/1', 'fecha_estudio' => today()->subMonth()]);
    crearEstudioDiagnostico($this->paciente->id, $otroTipo->id, ['ruta_archivo' => 'https://a.bo/2']);

    $todos = $this->actingAs($this->usuario)->get(route('estudios.listar', $this->paciente->id))->assertOk();
    expect($todos->viewData('estudios')->pluck('tipo_estudio_id')->all())->toBe([$otroTipo->id, $this->tipo->id]);

    $filtrados = $this->actingAs($this->usuario)->get(route('estudios.listar', ['paciente' => $this->paciente->id, 'tipo_estudio_id' => $otroTipo->id]))->assertOk();
    expect($filtrados->viewData('estudios'))->toHaveCount(1);
});

it('RF-59: la ficha del tratamiento muestra los requeridos cargados y los pendientes', function () {
    $tratamiento = crearTratamiento(['nombre' => 'Implante unitario']);
    $tomografia = crearTipoEstudio(['nombre' => 'Tomografía cone beam']);
    crearEstudioRequerido($tratamiento->id, $this->tipo->id, ['obligatorio' => true, 'momento_clinico' => 'previo al tratamiento']);
    crearEstudioRequerido($tratamiento->id, $tomografia->id, ['obligatorio' => false, 'momento_clinico' => 'control posterior']);

    $ta = crearTratamientoAplicado([
        'plan_tratamiento_id' => crearPlan(['paciente_id' => $this->paciente->id])->id,
        'tratamiento_id' => $tratamiento->id,
    ]);
    $cargado = crearEstudioDiagnostico($this->paciente->id, $this->tipo->id, ['ruta_archivo' => 'https://a.bo/1']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('tratamientos-aplicados.mostrar', $ta->id))
        ->assertOk()
        ->assertSee('Estudios requeridos')
        ->assertSee('Radiografía periapical')
        ->assertSee('Tomografía cone beam')
        ->assertSee('Cargado')
        ->assertSee('Pendiente')
        ->assertSee('Obligatorio')
        ->assertSee('Control posterior')
        ->assertSee(route('estudios.mostrar', $cargado->id));

    $requeridos = $respuesta->viewData('requeridos');

    expect(collect($requeridos['cargados'])->pluck('tipo_estudio_id')->all())->toBe([$this->tipo->id])
        ->and(collect($requeridos['pendientes'])->pluck('tipo_estudio_id')->all())->toBe([$tomografia->id])
        ->and($requeridos['cargados'][0]['obligatorio'])->toBeTrue()
        ->and($requeridos['pendientes'][0]['momento_clinico'])->toBe('control posterior');
});
