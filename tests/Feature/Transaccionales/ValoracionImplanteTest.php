<?php

use App\Models\Bitacora;
use App\Models\ValoracionImplante;
use App\Services\BitacoraService;

/*
 * CU33 Registrar valoración para implante (RF-60 a RF-65). Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->pieza = crearPiezaDental(36);
});

// Datos válidos de una valoración; $cambios reemplaza algunos.
function datosValoracion(int $pacienteId, int $piezaId, array $cambios = []): array
{
    return $cambios + [
        'paciente_id' => $pacienteId,
        'pieza_dental_id' => $piezaId,
        'fecha_valoracion' => today()->toDateString(),
        'estado_hueso' => 'insuficiente',
        'requiere_injerto' => '1',
        'observaciones_clinicas' => 'Reabsorción vestibular',
        'resultado' => 'apto con condiciones',
    ];
}

it('registra una valoración con sus tres enumerados', function () {
    $estudio = crearEstudioDiagnostico($this->paciente->id, crearTipoEstudio()->id);
    $ta = crearTratamientoAplicado(['plan_tratamiento_id' => crearPlan(['paciente_id' => $this->paciente->id])->id]);

    $this->actingAs($this->usuario)
        ->post(route('valoraciones.guardar'), datosValoracion($this->paciente->id, $this->pieza->id, [
            'estudio_diagnostico_id' => $estudio->id,
            'tratamiento_aplicado_id' => $ta->id,
        ]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Valoración registrada correctamente.');

    $valoracion = ValoracionImplante::first();

    expect($valoracion->estado_hueso)->toBe('insuficiente')
        ->and($valoracion->requiere_injerto)->toBeTrue()
        ->and($valoracion->resultado)->toBe('apto con condiciones')
        ->and($valoracion->odontologo_id)->toBe($this->usuario->id)
        ->and($valoracion->estudio_diagnostico_id)->toBe($estudio->id)
        ->and($valoracion->tratamiento_aplicado_id)->toBe($ta->id)
        ->and(Bitacora::where('accion', BitacoraService::REGISTRAR_VALORACION)->where('registro_id', $valoracion->id)->exists())->toBeTrue();
});

it('con hueso insuficiente se puede desmarcar el injerto', function () {
    $this->actingAs($this->usuario)
        ->post(route('valoraciones.guardar'), datosValoracion($this->paciente->id, $this->pieza->id, ['requiere_injerto' => '0']))
        ->assertSessionHasNoErrors();

    expect(ValoracionImplante::first()->requiere_injerto)->toBeFalse();
});

it('rechaza fecha futura', function () {
    $this->actingAs($this->usuario)
        ->post(route('valoraciones.guardar'), datosValoracion($this->paciente->id, $this->pieza->id, [
            'fecha_valoracion' => today()->addDay()->toDateString(),
        ]))
        ->assertSessionHasErrors(['fecha_valoracion' => 'La fecha de la valoración no puede ser futura.']);

    expect(ValoracionImplante::count())->toBe(0);
});

it('rechaza un estudio diagnóstico de otro paciente', function () {
    $ajeno = crearEstudioDiagnostico(crearPaciente()->id, crearTipoEstudio()->id);

    $this->actingAs($this->usuario)
        ->post(route('valoraciones.guardar'), datosValoracion($this->paciente->id, $this->pieza->id, ['estudio_diagnostico_id' => $ajeno->id]))
        ->assertSessionHasErrors(['estudio_diagnostico_id' => 'El estudio diagnóstico no pertenece a este paciente.']);

    expect(ValoracionImplante::count())->toBe(0);
});

it('rechaza un tratamiento aplicado de otro paciente', function () {
    $ajeno = crearTratamientoAplicado();

    $this->actingAs($this->usuario)
        ->post(route('valoraciones.guardar'), datosValoracion($this->paciente->id, $this->pieza->id, ['tratamiento_aplicado_id' => $ajeno->id]))
        ->assertSessionHasErrors(['tratamiento_aplicado_id' => 'El tratamiento aplicado no pertenece a este paciente.']);

    expect(ValoracionImplante::count())->toBe(0);
});

it('modifica el resultado y queda en bitácora', function () {
    $valoracion = crearValoracionImplante($this->paciente->id, $this->pieza->id, ['resultado' => 'apto con condiciones', 'estado_hueso' => 'regular']);

    $this->actingAs($this->usuario)
        ->post(route('valoraciones.modificar'), [
            'id' => $valoracion->id,
            'pieza_dental_id' => $this->pieza->id,
            'fecha_valoracion' => today()->toDateString(),
            'estado_hueso' => 'regular',
            'requiere_injerto' => '0',
            'resultado' => 'no apto',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Valoración actualizada correctamente.');

    expect($valoracion->fresh()->resultado)->toBe('no apto')
        ->and($valoracion->fresh()->paciente_id)->toBe($this->paciente->id)
        ->and(Bitacora::where('accion', BitacoraService::MODIFICAR_VALORACION)
            ->where('tabla_afectada', 'valoraciones_implante')
            ->where('registro_id', $valoracion->id)
            ->exists())->toBeTrue();
});

it('lista las valoraciones del paciente por fecha descendente', function () {
    crearValoracionImplante($this->paciente->id, $this->pieza->id, ['fecha_valoracion' => today()->subMonths(3), 'observaciones_clinicas' => 'Primera']);
    crearValoracionImplante($this->paciente->id, $this->pieza->id, ['fecha_valoracion' => today(), 'observaciones_clinicas' => 'Tercera']);
    crearValoracionImplante($this->paciente->id, $this->pieza->id, ['fecha_valoracion' => today()->subMonth(), 'observaciones_clinicas' => 'Segunda']);
    crearValoracionImplante(crearPaciente()->id, $this->pieza->id, ['observaciones_clinicas' => 'De otro paciente']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('valoraciones.listar', $this->paciente->id))
        ->assertOk()
        ->assertSee('Apto');

    expect($respuesta->viewData('valoraciones')->pluck('observaciones_clinicas')->all())->toBe(['Tercera', 'Segunda', 'Primera']);
});

it('muestra la ficha de la valoración con sus insignias', function () {
    $valoracion = crearValoracionImplante($this->paciente->id, $this->pieza->id, ['estado_hueso' => 'insuficiente', 'requiere_injerto' => true, 'resultado' => 'no apto']);

    $this->actingAs($this->usuario)
        ->get(route('valoraciones.mostrar', $valoracion->id))
        ->assertOk()
        ->assertSee('No apto')
        ->assertSee('Hueso insuficiente')
        ->assertSee('Requiere injerto');
});
