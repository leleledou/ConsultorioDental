<?php

use App\Services\ReporteVozService;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as PeticionHttp;
use Illuminate\Support\Facades\Http;

/*
 * Reportes por voz: entrada alternativa de CU42, CU43 y CU44. Gemini simulado con Http::fake().
 * Hoy fijo: miércoles 18/11/2026 en America/La_Paz.
 */

beforeEach(function () {
    Http::preventStrayRequests();
    $this->travelTo(Carbon::parse('2026-11-18 10:00:00', 'America/La_Paz'));

    config(['services.gemini.key' => 'clave-de-prueba', 'services.gemini.modelo' => 'gemini-flash-latest']);

    $this->usuario = crearUsuario('odontologo');
    $this->voz = app(ReporteVozService::class);
});

// Respuesta de generateContent con el JSON pedido en el texto del primer candidato.
function respuestaGemini(array $datos): array
{
    return ['candidates' => [['content' => ['parts' => [['text' => json_encode($datos)]]]]]];
}

function simularGemini(mixed $respuesta): void
{
    Http::fake(['generativelanguage.googleapis.com/*' => $respuesta]);
}

it('la pantalla carga para un usuario autenticado y redirige al login si no lo está', function () {
    $this->get(route('reportes.voz'))->assertRedirect(route('login'));

    $this->actingAs($this->usuario)
        ->get(route('reportes.voz'))
        ->assertOk()
        ->assertSee('Reportes por voz')
        ->assertSee('Por ejemplo: ingresos de octubre por método de pago')
        ->assertSee('Citas no asistidas de los últimos 90 días')
        ->assertSee('Leer el resultado en voz alta')
        ->assertSee('Generar reporte');
});

it('el menú de reportes queda activo en la pantalla de voz', function () {
    $html = $this->actingAs($this->usuario)->get(route('reportes.voz'))->getContent();

    expect($html)->toMatch('/<a[^>]*reportes\/clinicos"[^>]*aria-current="page"/s');
});

it('con una respuesta válida de Gemini devuelve el tipo y el periodo esperados', function () {
    simularGemini(Http::response(respuestaGemini(['tipo' => 'ingresos', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31'])));

    expect($this->voz->interpretar('cuánto entró en octubre por método de pago'))->toBe([
        'tipo' => 'ingresos',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
        'interpretacion' => 'Ingresos por periodo y método de pago, del 01/10/2026 al 31/10/2026',
        'origen' => 'gemini',
    ]);

    Http::assertSent(function (PeticionHttp $peticion) {
        return str_contains($peticion->url(), 'models/gemini-flash-latest:generateContent?key=clave-de-prueba')
            && $peticion['generationConfig']['temperature'] === 0
            && $peticion['generationConfig']['responseMimeType'] === 'application/json'
            && count($peticion['generationConfig']['responseSchema']['properties']['tipo']['enum']) === 8
            && str_contains($peticion['contents'][0]['parts'][0]['text'], '2026-11-18');
    });
});

it('interpretar redirige a la pantalla con el reporte generado y lo que entendió el sistema', function () {
    simularGemini(Http::response(respuestaGemini(['tipo' => 'ingresos', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31'])));

    $this->actingAs($this->usuario)
        ->post(route('reportes.voz.interpretar'), ['frase' => 'ingresos de octubre'])
        ->assertRedirect(route('reportes.voz', ['tipo' => 'ingresos', 'fecha_inicio' => '2026-10-01', 'fecha_fin' => '2026-10-31']))
        ->assertSessionHas('interpretacion', 'Ingresos por periodo y método de pago, del 01/10/2026 al 31/10/2026');

    $this->followRedirects($this->post(route('reportes.voz.interpretar'), ['frase' => 'ingresos de octubre']))
        ->assertOk()
        ->assertSee('El sistema entendió:')
        ->assertSee('Ingresos por periodo y método de pago')
        ->assertSee('01/10/2026 al 31/10/2026')
        ->assertSee('Consulta: «ingresos de octubre»', false);
});

it('la pantalla genera el reporte por query string y exporta los mismos parámetros', function () {
    $html = $this->actingAs($this->usuario)
        ->get(route('reportes.voz', ['tipo' => 'saldo_pendiente', 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']))
        ->assertOk()
        ->assertSee('Tratamientos con saldo pendiente')
        ->assertSee('Exportar PDF')
        ->getContent();

    // CU45 recibe la familia y el modo, como en las demás pantallas de reportes.
    expect(html_entity_decode($html))->toContain('reportes/exportar?')
        ->toContain('modo=estatico')
        ->toContain('reporte=financiero');

    $this->get(route('reportes.exportar', [
        'reporte' => 'financiero', 'modo' => 'estatico', 'tipo' => 'saldo_pendiente',
        'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30', 'formato' => 'excel',
    ]))->assertOk();
});

it('la pantalla rechaza un tipo o un periodo inválido por query string', function () {
    $this->actingAs($this->usuario)
        ->from(route('reportes.voz'))
        ->get(route('reportes.voz', ['tipo' => 'inventado', 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']))
        ->assertSessionHasErrors(['tipo' => 'El reporte indicado no es válido.']);

    $this->from(route('reportes.voz'))
        ->get(route('reportes.voz', ['tipo' => 'ingresos', 'fecha_inicio' => '2026-11-30', 'fecha_fin' => '2026-11-01']))
        ->assertSessionHasErrors(['fecha_inicio' => 'La fecha inicial no puede ser posterior a la fecha final.']);
});

it('si Gemini devuelve un tipo que no está entre los ocho, responde el respaldo por reglas', function () {
    simularGemini(Http::response(respuestaGemini(['tipo' => 'facturas', 'fecha_inicio' => '2026-01-01', 'fecha_fin' => '2026-01-31'])));

    expect($this->voz->interpretar('ingresos de octubre'))->toMatchArray([
        'tipo' => 'ingresos',
        'fecha_inicio' => '2026-10-01',
        'fecha_fin' => '2026-10-31',
        'origen' => 'reglas',
    ]);
});

it('si Gemini devuelve fechas inválidas o invertidas, responde el respaldo por reglas', function (string $inicio, string $fin) {
    simularGemini(Http::response(respuestaGemini(['tipo' => 'agenda_citas', 'fecha_inicio' => $inicio, 'fecha_fin' => $fin])));

    expect($this->voz->interpretar('agenda de esta semana'))->toMatchArray([
        'tipo' => 'agenda_citas',
        'fecha_inicio' => '2026-11-16',
        'fecha_fin' => '2026-11-22',
        'origen' => 'reglas',
    ]);
})->with([
    'día inexistente' => ['2026-02-30', '2026-03-05'],
    'otro formato' => ['16/11/2026', '22/11/2026'],
    'texto' => ['esta semana', '2026-11-22'],
    'invertidas' => ['2026-11-22', '2026-11-16'],
]);

it('si Gemini responde algo que no es el JSON pedido, responde el respaldo por reglas', function () {
    simularGemini(Http::response(['candidates' => [['content' => ['parts' => [['text' => 'No entendí la consulta']]]]]]));

    expect($this->voz->interpretar('tratamientos con saldo pendiente'))->toMatchArray(['tipo' => 'saldo_pendiente', 'origen' => 'reglas']);
});

it('si la petición falla o se agota el tiempo, responde el respaldo por reglas', function (Closure $respuesta) {
    simularGemini($respuesta);

    expect($this->voz->interpretar('citas no asistidas de los últimos 90 días'))->toMatchArray([
        'tipo' => 'citas_no_asistidas',
        'fecha_inicio' => '2026-08-21',
        'fecha_fin' => '2026-11-18',
        'origen' => 'reglas',
    ]);
})->with([
    'error 500' => fn () => fn () => Http::response(['error' => 'interno'], 500),
    'clave rechazada' => fn () => fn () => Http::response(['error' => 'clave'], 403),
    'tiempo agotado' => fn () => fn () => throw new ConnectionException('cURL error 28: Operation timed out'),
]);

it('la pantalla no se rompe si Gemini falla', function () {
    simularGemini(fn () => throw new ConnectionException('cURL error 28: Operation timed out'));

    $this->actingAs($this->usuario)
        ->post(route('reportes.voz.interpretar'), ['frase' => 'pacientes atendidos este mes'])
        ->assertRedirect(route('reportes.voz', ['tipo' => 'pacientes_atendidos', 'fecha_inicio' => '2026-11-01', 'fecha_fin' => '2026-11-30']));
});

it('sin GEMINI_API_KEY no se hace ninguna petición y responde el respaldo', function () {
    config(['services.gemini.key' => null]);
    Http::fake();

    expect($this->voz->interpretar('ingresos de octubre'))->toMatchArray(['tipo' => 'ingresos', 'origen' => 'reglas']);

    Http::assertNothingSent();
});

it('el respaldo por reglas resuelve las frases habituales', function (string $frase, string $tipo, string $inicio, string $fin) {
    config(['services.gemini.key' => null]);

    expect($this->voz->interpretar($frase))->toMatchArray([
        'tipo' => $tipo,
        'fecha_inicio' => $inicio,
        'fecha_fin' => $fin,
        'origen' => 'reglas',
    ]);
})->with([
    ['ingresos de octubre', 'ingresos', '2026-10-01', '2026-10-31'],
    ['Ingresos de octubre por método de pago', 'ingresos', '2026-10-01', '2026-10-31'],
    ['tratamientos con saldo pendiente', 'saldo_pendiente', '2026-11-01', '2026-11-30'],
    ['agenda de esta semana', 'agenda_citas', '2026-11-16', '2026-11-22'],
    ['citas no asistidas de los últimos 90 días', 'citas_no_asistidas', '2026-08-21', '2026-11-18'],
    ['pacientes atendidos hoy', 'pacientes_atendidos', '2026-11-18', '2026-11-18'],
    ['materiales utilizados en julio de 2025', 'materiales_utilizados', '2025-07-01', '2025-07-31'],
    ['tratamientos con sesiones pendientes del mes pasado', 'sesiones_pendientes', '2026-10-01', '2026-10-31'],
    ['tratamientos por odontólogo y especialidad de marzo a junio', 'tratamientos_odontologo', '2026-03-01', '2026-06-30'],
]);

it('la frase vacía o de más de 200 caracteres devuelve error de validación', function (string $frase, string $mensaje) {
    Http::fake();

    $this->actingAs($this->usuario)
        ->from(route('reportes.voz'))
        ->post(route('reportes.voz.interpretar'), ['frase' => $frase])
        ->assertRedirect(route('reportes.voz'))
        ->assertSessionHasErrors(['frase' => $mensaje]);

    Http::assertNothingSent();
})->with([
    'vacía' => ['', 'Diga o escriba la consulta del reporte.'],
    'larga' => [str_repeat('a', 201), 'La consulta no puede superar los 200 caracteres.'],
]);
