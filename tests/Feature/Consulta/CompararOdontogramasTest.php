<?php

use App\Models\DetalleOdontograma;
use App\Models\EstadoPieza;
use Database\Seeders\EstadoPiezaSeeder;
use Database\Seeders\PiezaDentalSeeder;

/*
 * CU26 Comparar odontogramas. Pruebas de consulta.
 */

beforeEach(function () {
    $this->seed([PiezaDentalSeeder::class, EstadoPiezaSeeder::class]);

    $this->usuario = crearUsuario('odontologo');
    $this->paciente = crearPaciente();
    $this->historia = crearHistoria($this->paciente->id);
});

// Registra en el odontograma cada pieza (código FDI => código de estado).
function cargarPiezasComparar(int $odontogramaId, array $piezas): void
{
    foreach ($piezas as $fdi => $codigo) {
        DetalleOdontograma::create([
            'odontograma_id' => $odontogramaId,
            'pieza_dental_id' => $fdi,
            'estado_pieza_id' => EstadoPieza::where('codigo', $codigo)->value('id'),
        ]);
    }
}

it('compara dos odontogramas y marca como cambio solo las piezas con estado distinto', function () {
    $a = crearOdontograma($this->historia->id, ['fecha_registro' => today()->subMonths(6)]);
    $b = crearOdontograma($this->historia->id, ['tipo' => 'evolución']);
    cargarPiezasComparar($a->id, [11 => 'SANO', 12 => 'CARIES', 16 => 'SANO']);
    cargarPiezasComparar($b->id, [11 => 'SANO', 12 => 'RESINA', 16 => 'SANO']);

    $respuesta = $this->actingAs($this->usuario)
        ->get(route('odontogramas.comparar', ['paciente' => $this->paciente->id, 'a' => $a->id, 'b' => $b->id]))
        ->assertOk()
        ->assertSee('Obturado con resina');

    $filas = collect($respuesta->viewData('filas'))->keyBy('codigo_fdi');

    expect($filas)->toHaveCount(3)
        ->and($filas[11]['cambio'])->toBeFalse()
        ->and($filas[12]['cambio'])->toBeTrue()
        ->and($filas[12]['estado_a'])->toBe('Caries')
        ->and($filas[12]['color_b'])->toBe('#1E88E5')
        ->and($filas[16]['cambio'])->toBeFalse()
        ->and($filas->keys()->all())->toBe([11, 12, 16]);
});

it('una pieza presente en uno y ausente en el otro se marca como cambio', function () {
    $a = crearOdontograma($this->historia->id, ['fecha_registro' => today()->subMonths(6)]);
    $b = crearOdontograma($this->historia->id, ['tipo' => 'evolución']);
    cargarPiezasComparar($a->id, [11 => 'SANO', 18 => 'SANO']);
    cargarPiezasComparar($b->id, [11 => 'SANO', 21 => 'SANO']);

    $filas = collect($this->actingAs($this->usuario)
        ->get(route('odontogramas.comparar', ['paciente' => $this->paciente->id, 'a' => $a->id, 'b' => $b->id]))
        ->assertOk()
        ->viewData('filas'))->keyBy('codigo_fdi');

    expect($filas)->toHaveCount(3)
        ->and($filas[11]['cambio'])->toBeFalse()
        ->and($filas[18]['cambio'])->toBeTrue()
        ->and($filas[18]['estado_b'])->toBeNull()
        ->and($filas[21]['cambio'])->toBeTrue()
        ->and($filas[21]['estado_a'])->toBeNull();
});

it('elegir el mismo odontograma dos veces devuelve el mensaje exacto', function () {
    $a = crearOdontograma($this->historia->id, ['fecha_registro' => today()->subMonths(6)]);
    crearOdontograma($this->historia->id, ['tipo' => 'evolución']);

    $this->actingAs($this->usuario)
        ->get(route('odontogramas.comparar', ['paciente' => $this->paciente->id, 'a' => $a->id, 'b' => $a->id]))
        ->assertRedirect(route('odontogramas.comparar', $this->paciente->id))
        ->assertSessionHasErrors(['operacion' => 'Seleccione dos odontogramas diferentes para comparar.']);
});

it('con un solo odontograma muestra el mensaje de que no hay con qué comparar', function () {
    crearOdontograma($this->historia->id);

    $this->actingAs($this->usuario)
        ->get(route('odontogramas.comparar', $this->paciente->id))
        ->assertOk()
        ->assertSee('El paciente tiene un solo odontograma registrado; no hay con qué compararlo.');
});
