<?php

use App\Models\Bitacora;
use App\Models\HistoriaAntecedente;
use App\Services\BitacoraService;

/*
 * CU23 Gestionar historia clínica. Pruebas transaccionales.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente(['nombres' => 'Xilofono']);
    $this->historia = crearHistoria($this->paciente->id);
    $this->antecedente = crearAntecedente(['nombre' => 'Hipertensión arterial']);
});

// Asigna el antecedente a la historia, con una observación.
function asignarAHistoria(int $historiaId, int $antecedenteId): HistoriaAntecedente
{
    return HistoriaAntecedente::create([
        'historia_clinica_id' => $historiaId,
        'antecedente_medico_id' => $antecedenteId,
        'observacion' => 'Texto original',
        'fecha_registro' => today(),
    ]);
}

it('muestra la historia del paciente a un odontólogo no administrador', function () {
    asignarAHistoria($this->historia->id, $this->antecedente->id);

    $this->actingAs($this->usuario)
        ->get(route('historias.mostrar', $this->paciente->id))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertSee('Hipertensión arterial')
        ->assertSee('Texto original')
        ->assertSee(today()->format('d/m/Y'));
});

it('responde 404 si el paciente no existe', function () {
    $this->actingAs($this->usuario)
        ->get(route('historias.mostrar', 999999))
        ->assertNotFound();
});

it('guarda hábitos y observaciones generales', function () {
    $this->actingAs($this->usuario)
        ->post(route('historias.modificar'), [
            'id' => $this->historia->id,
            'habitos' => 'Bruxismo',
            'observaciones_generales' => 'Sin novedades',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Historia clínica actualizada.');

    expect($this->historia->fresh()->habitos)->toBe('Bruxismo')
        ->and($this->historia->fresh()->observaciones_generales)->toBe('Sin novedades');
});

it('asigna un antecedente con la fecha de hoy', function () {
    $this->actingAs($this->usuario)
        ->post(route('historias.antecedentes.asignar'), [
            'historia_clinica_id' => $this->historia->id,
            'antecedente_medico_id' => $this->antecedente->id,
            'observacion' => 'Controlada',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Antecedente asignado.');

    $asignado = HistoriaAntecedente::first();

    expect($asignado->observacion)->toBe('Controlada')
        ->and($asignado->fecha_registro)->toBe(today()->toDateString());
});

it('A1: rechaza un antecedente repetido', function () {
    asignarAHistoria($this->historia->id, $this->antecedente->id);

    $this->actingAs($this->usuario)
        ->post(route('historias.antecedentes.asignar'), [
            'historia_clinica_id' => $this->historia->id,
            'antecedente_medico_id' => $this->antecedente->id,
            'observacion' => 'Otra',
        ])
        ->assertSessionHasErrors(['antecedente_medico_id' => 'Antecedente ya registrado en la historia clínica'])
        ->assertSessionHasInput('observacion', 'Otra');

    expect(HistoriaAntecedente::count())->toBe(1);
});

it('rechaza asignar sin antecedente', function () {
    $this->actingAs($this->usuario)
        ->post(route('historias.antecedentes.asignar'), [
            'historia_clinica_id' => $this->historia->id,
            'antecedente_medico_id' => '',
        ])
        ->assertSessionHasErrors(['antecedente_medico_id' => 'Debe seleccionar el antecedente.']);

    expect(HistoriaAntecedente::count())->toBe(0);
});

it('modifica la observación de un antecedente', function () {
    $asignado = asignarAHistoria($this->historia->id, $this->antecedente->id);

    $this->actingAs($this->usuario)
        ->post(route('historias.antecedentes.modificar'), ['id' => $asignado->id, 'observacion' => 'Texto nuevo'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Historia clínica actualizada.');

    expect($asignado->fresh()->observacion)->toBe('Texto nuevo');
});

it('quita un antecedente de la historia', function () {
    $asignado = asignarAHistoria($this->historia->id, $this->antecedente->id);

    $this->actingAs($this->usuario)
        ->post(route('historias.antecedentes.quitar'), ['id' => $asignado->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Historia clínica actualizada.');

    expect(HistoriaAntecedente::find($asignado->id))->toBeNull();
});

it('la vista usa el id de la fila intermedia para modificar y quitar', function () {
    $asignado = asignarAHistoria($this->historia->id, crearAntecedente()->id);
    $otro = asignarAHistoria($this->historia->id, $this->antecedente->id);

    $this->actingAs($this->usuario)
        ->get(route('historias.mostrar', $this->paciente->id))
        ->assertSee('name="id" value="'.$asignado->id.'"', false)
        ->assertSee('name="id" value="'.$otro->id.'"', false);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->usuario)->post(route('historias.modificar'), ['id' => $this->historia->id, 'habitos' => 'x']);

    expect(Bitacora::where('tabla_afectada', 'historias_clinicas')->where('registro_id', $this->historia->id)->pluck('accion')->all())
        ->toBe([BitacoraService::MODIFICAR_HISTORIA]);

    $this->actingAs($this->usuario)->post(route('historias.antecedentes.asignar'), [
        'historia_clinica_id' => $this->historia->id,
        'antecedente_medico_id' => $this->antecedente->id,
    ]);

    $asignado = HistoriaAntecedente::first();

    $this->actingAs($this->usuario)->post(route('historias.antecedentes.modificar'), ['id' => $asignado->id, 'observacion' => 'y']);
    $this->actingAs($this->usuario)->post(route('historias.antecedentes.quitar'), ['id' => $asignado->id]);

    $acciones = Bitacora::where('tabla_afectada', 'historias_antecedentes')
        ->where('registro_id', $asignado->id)
        ->where('usuario_id', $this->usuario->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::ASIGNAR_ANTECEDENTE,
        BitacoraService::MODIFICAR_ANTECEDENTE_HISTORIA,
        BitacoraService::QUITAR_ANTECEDENTE,
    ]);
});

it('la ficha del paciente enlaza la historia y los planes', function () {
    $this->actingAs($this->usuario)
        ->get(route('pacientes.mostrar', $this->paciente->id))
        ->assertSee(route('historias.mostrar', $this->paciente->id), false)
        ->assertSee(route('planes.listar', ['paciente_id' => $this->paciente->id]), false);
});
