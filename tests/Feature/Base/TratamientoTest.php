<?php

use App\Models\Bitacora;
use App\Models\Especialidad;
use App\Models\Tratamiento;
use App\Services\BitacoraService;

/*
 * CU15 Gestionar catálogo de tratamientos (RF-25). Pruebas base.
 */

beforeEach(function () {
    $this->admin = crearUsuario('administrador');
    $this->especialidad = Especialidad::factory()->create(['nombre' => 'Endodoncia']);
});

// Datos válidos de un tratamiento; $cambios reemplaza algunos.
function datosTratamiento(int $especialidadId, array $cambios = []): array
{
    return $cambios + [
        'nombre' => 'Endodoncia unirradicular',
        'descripcion' => 'Tratamiento de conducto.',
        'especialidad_id' => $especialidadId,
        'duracion_estimada_minutos' => 90,
        'numero_sesiones_previstas' => 2,
        'requiere_sesiones' => 1,
    ];
}

it('registra un tratamiento válido como disponible', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.guardar'), datosTratamiento($this->especialidad->id))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    $tratamiento = Tratamiento::where('nombre', 'Endodoncia unirradicular')->first();

    expect($tratamiento)->not->toBeNull()
        ->and($tratamiento->estado)->toBe('disponible')
        ->and($tratamiento->duracion_estimada_minutos)->toBe(90)
        ->and($tratamiento->requiere_sesiones)->toBeTrue();
});

it('rechaza un nombre duplicado', function () {
    crearTratamiento(['nombre' => 'Endodoncia unirradicular', 'especialidad_id' => $this->especialidad->id]);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.guardar'), datosTratamiento($this->especialidad->id))
        ->assertSessionHasErrors(['nombre' => 'Ya existe un tratamiento con ese nombre.']);

    expect(Tratamiento::count())->toBe(1);
});

it('permite modificar un tratamiento conservando su propio nombre', function () {
    $tratamiento = crearTratamiento(['nombre' => 'Endodoncia unirradicular', 'especialidad_id' => $this->especialidad->id]);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.modificar'), datosTratamiento($this->especialidad->id, [
            'id' => $tratamiento->id,
            'duracion_estimada_minutos' => 100,
        ]))
        ->assertSessionHasNoErrors();

    expect($tratamiento->fresh()->duracion_estimada_minutos)->toBe(100);
});

it('rechaza una duración de 0 minutos', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.guardar'), datosTratamiento($this->especialidad->id, ['duracion_estimada_minutos' => 0]))
        ->assertSessionHasErrors(['duracion_estimada_minutos' => 'La duración estimada debe ser mayor a 0 minutos.']);

    expect(Tratamiento::count())->toBe(0);
});

it('da de baja cambiando el estado sin borrar el registro', function () {
    $tratamiento = crearTratamiento(['especialidad_id' => $this->especialidad->id]);

    $this->actingAs($this->admin)
        ->post(route('tratamientos.eliminar'), ['id' => $tratamiento->id])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('exito');

    expect(Tratamiento::find($tratamiento->id))->not->toBeNull()
        ->and($tratamiento->fresh()->estado)->toBe('no disponible');

    // Habilitar lo devuelve al catálogo.
    $this->actingAs($this->admin)
        ->post(route('tratamientos.habilitar'), ['id' => $tratamiento->id])
        ->assertSessionHasNoErrors();

    expect($tratamiento->fresh()->estado)->toBe('disponible');
});

it('responde 403 a un odontólogo que no es administrador', function () {
    $odontologo = crearUsuario('odontologo');

    $this->actingAs($odontologo)->get(route('tratamientos.listar'))->assertForbidden();
    $this->actingAs($odontologo)
        ->post(route('tratamientos.guardar'), datosTratamiento($this->especialidad->id))
        ->assertForbidden();

    expect(Tratamiento::count())->toBe(0);
});

it('registra las operaciones en la bitácora', function () {
    $this->actingAs($this->admin)
        ->post(route('tratamientos.guardar'), datosTratamiento($this->especialidad->id));

    $tratamiento = Tratamiento::first();

    $this->actingAs($this->admin)->post(route('tratamientos.eliminar'), ['id' => $tratamiento->id]);

    $acciones = Bitacora::where('tabla_afectada', 'tratamientos')
        ->where('registro_id', $tratamiento->id)
        ->where('usuario_id', $this->admin->id)
        ->pluck('accion')
        ->all();

    expect($acciones)->toBe([BitacoraService::CREAR_TRATAMIENTO, BitacoraService::BAJA_TRATAMIENTO]);
});

it('muestra la lista y busca por especialidad', function () {
    crearTratamiento(['nombre' => 'Conducto molar', 'especialidad_id' => $this->especialidad->id]);
    crearTratamiento(['nombre' => 'Profilaxis']);

    $this->actingAs($this->admin)
        ->get(route('tratamientos.listar', ['buscar' => 'Endodon']))
        ->assertOk()
        ->assertSee('Conducto molar')
        ->assertDontSee('Profilaxis');
});
