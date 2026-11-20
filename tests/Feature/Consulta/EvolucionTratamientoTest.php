<?php

/*
 * CU31 Consultar evolución del tratamiento (RF-43, RF-44). Pruebas de consulta.
 */

beforeEach(function () {
    $this->usuario = crearUsuario('odontologo');
    $this->ta = crearTratamientoAplicado(['fecha_inicio' => today()->subMonths(2)]);
});

it('lista las sesiones en orden cronológico ascendente', function () {
    // Se registran desordenadas a propósito.
    crearSesion($this->ta->id, ['numero_sesion' => 2, 'fecha' => today()->subDays(5), 'avance_tecnico' => 'Segundo avance']);
    crearSesion($this->ta->id, ['numero_sesion' => 3, 'fecha' => today(), 'avance_tecnico' => 'Tercer avance']);
    crearSesion($this->ta->id, ['numero_sesion' => 1, 'fecha' => today()->subDays(20), 'avance_tecnico' => 'Primer avance']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('sesiones.evolucion', $this->ta->id))
        ->assertOk()
        ->assertSeeInOrder(['Primer avance', 'Segundo avance', 'Tercer avance']);

    expect($respuesta->viewData('sesiones')->pluck('numero_sesion')->all())->toBe([1, 2, 3]);
});

it('muestra los materiales de cada sesión', function () {
    $sesion = crearSesion($this->ta->id, ['numero_sesion' => 1]);
    crearSesionMaterial($sesion->id, crearMaterial(['nombre' => 'Gutapercha', 'unidad_medida' => 'puntas'])->id, 4);

    $this->actingAs($this->usuario)
        ->get(route('sesiones.evolucion', $this->ta->id))
        ->assertOk()
        ->assertSee('Gutapercha')
        ->assertSee('puntas');
});

it('sin sesiones muestra el mensaje exacto', function () {
    $this->actingAs($this->usuario)
        ->get(route('sesiones.evolucion', $this->ta->id))
        ->assertOk()
        ->assertSee('El tratamiento aún no tiene sesiones registradas.');
});
