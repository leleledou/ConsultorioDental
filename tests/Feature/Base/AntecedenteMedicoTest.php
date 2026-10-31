<?php

use App\Models\AntecedenteMedico;
use App\Models\Bitacora;
use App\Models\HistoriaAntecedente;
use App\Models\HistoriaClinica;
use App\Services\BitacoraService;
use Illuminate\Support\Facades\Route;

/*
 * CU21 Gestionar antecedentes médicos. Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
});

// Datos válidos de un antecedente; $cambios reemplaza algunos.
function datosAntecedente(array $cambios = []): array
{
    return $cambios + [
        'nombre' => 'Hipertensión arterial',
        'descripcion' => 'Presión arterial elevada.',
    ];
}

// Registra el antecedente en la historia clínica de un paciente nuevo.
function asignarAntecedente(AntecedenteMedico $antecedente): void
{
    $historia = HistoriaClinica::create([
        'paciente_id' => crearPaciente()->id,
        'fecha_apertura' => today(),
    ]);

    HistoriaAntecedente::create([
        'historia_clinica_id' => $historia->id,
        'antecedente_medico_id' => $antecedente->id,
        'fecha_registro' => today(),
    ]);
}

it('registra un antecedente válido', function () {
    $this->actingAs($this->admin)
        ->post(route('antecedentes.guardar'), datosAntecedente())
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Antecedente registrado correctamente.');

    expect(AntecedenteMedico::where('nombre', 'Hipertensión arterial')->exists())->toBeTrue();
});

it('rechaza un nombre duplicado', function () {
    crearAntecedente(['nombre' => 'Hipertensión arterial']);

    $this->actingAs($this->admin)
        ->post(route('antecedentes.guardar'), datosAntecedente())
        ->assertSessionHasErrors(['nombre' => 'Ya existe un antecedente con ese nombre.']);

    expect(AntecedenteMedico::count())->toBe(1);
});

it('permite modificar un antecedente conservando su propio nombre', function () {
    $antecedente = crearAntecedente(['nombre' => 'Hipertensión arterial']);

    $this->actingAs($this->admin)
        ->post(route('antecedentes.modificar'), datosAntecedente(['id' => $antecedente->id, 'descripcion' => 'Texto nuevo']))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito', 'Antecedente actualizado correctamente.');

    expect($antecedente->fresh()->descripcion)->toBe('Texto nuevo');
});

it('rechaza nombre de más de 50 caracteres', function () {
    $this->actingAs($this->admin)
        ->post(route('antecedentes.guardar'), datosAntecedente(['nombre' => str_repeat('x', 51)]))
        ->assertSessionHasErrors(['nombre' => 'El nombre no puede superar los 50 caracteres.']);

    expect(AntecedenteMedico::count())->toBe(0);
});

it('elimina un antecedente sin historias y vuelve al listado', function () {
    $antecedente = crearAntecedente();

    $this->actingAs($this->admin)
        ->post(route('antecedentes.eliminar'), ['id' => $antecedente->id])
        ->assertRedirect(route('antecedentes.listar'))
        ->assertSessionHas('exito', 'Antecedente eliminado.');

    expect(AntecedenteMedico::find($antecedente->id))->toBeNull();
});

it('no elimina un antecedente registrado en historias clínicas', function () {
    $antecedente = crearAntecedente();
    asignarAntecedente($antecedente);
    asignarAntecedente($antecedente);

    $this->actingAs($this->admin)
        ->post(route('antecedentes.eliminar'), ['id' => $antecedente->id])
        ->assertSessionHasErrors(['operacion' => 'No se puede eliminar: el antecedente está registrado en 2 historias clínicas']);

    expect(AntecedenteMedico::find($antecedente->id))->not->toBeNull()
        ->and(Bitacora::where('accion', BitacoraService::ELIMINAR_ANTECEDENTE)->count())->toBe(0);
});

it('no tiene estado ni acción de habilitar', function () {
    expect(Route::has('antecedentes.habilitar'))->toBeFalse();
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)->post(route('antecedentes.guardar'), datosAntecedente());

    $antecedente = AntecedenteMedico::first();

    $this->actingAs($this->admin)->post(route('antecedentes.modificar'), datosAntecedente(['id' => $antecedente->id]));
    $this->actingAs($this->admin)->post(route('antecedentes.eliminar'), ['id' => $antecedente->id]);

    $acciones = Bitacora::where('tabla_afectada', 'antecedentes_medicos')
        ->where('registro_id', $antecedente->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([
        BitacoraService::CREAR_ANTECEDENTE,
        BitacoraService::MODIFICAR_ANTECEDENTE,
        BitacoraService::ELIMINAR_ANTECEDENTE,
    ]);
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('antecedentes.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('antecedentes.guardar'), datosAntecedente())
        ->assertForbidden();

    expect(AntecedenteMedico::count())->toBe(0);
});

it('muestra la lista con el número de historias y busca por nombre', function () {
    $antecedente = crearAntecedente(['nombre' => 'Xilofono']);
    asignarAntecedente($antecedente);
    crearAntecedente(['nombre' => 'Quetzal']);

    $respuesta = $this->actingAs($this->admin)
        ->get(route('antecedentes.listar', ['buscar' => 'Xilo']))
        ->assertOk()
        ->assertSee('Xilofono')
        ->assertDontSee('Quetzal');

    expect($respuesta->viewData('antecedentes')->first()->historias_count)->toBe(1);
});

it('muestra el detalle', function () {
    $antecedente = crearAntecedente(['nombre' => 'Xilofono']);

    $this->actingAs($this->admin)
        ->get(route('antecedentes.mostrar', $antecedente->id))
        ->assertOk()
        ->assertSee('Xilofono');
});
