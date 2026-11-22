<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reportes por voz: forma alternativa de entrada de CU42, CU43 y CU44 (no es un caso de uso nuevo).
 *
 * Traduce una frase en español a {tipo, fecha_inicio, fecha_fin} de uno de los reportes
 * estáticos de ReporteService::ESTATICOS. Primero lo intenta con Gemini; si no hay clave,
 * la petición falla o la respuesta no pasa la validación, resuelve la frase con reglas
 * por palabras clave. Gemini no consulta la base de datos ni genera el reporte.
 */
class ReporteVozService
{
    private const ZONA = 'America/La_Paz';

    private const MESES = [
        'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'junio' => 6,
        'julio' => 7, 'agosto' => 8, 'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
        'noviembre' => 11, 'diciembre' => 12,
    ];

    // Respaldo: palabras clave de cada reporte, en orden de prioridad (la primera que coincide gana).
    private const PALABRAS = [
        'citas_no_asistidas' => ['no asist', 'inasist', 'faltaron', 'faltas', 'ausen', 'no vinieron', 'no se present'],
        'saldo_pendiente' => ['saldo', 'deuda', 'deben', 'adeud', 'por cobrar'],
        'sesiones_pendientes' => ['sesion'],
        'materiales_utilizados' => ['material', 'insumo'],
        'ingresos' => ['ingreso', 'cobr', 'pago', 'abono', 'recaud', 'factur', 'dinero', 'caja'],
        'agenda_citas' => ['agenda', 'cita', 'turno'],
        'tratamientos_odontologo' => ['tratamiento', 'odontologo', 'especialidad', 'doctor'],
        'pacientes_atendidos' => ['paciente', 'atendid', 'atencion'],
    ];

    /**
     * @return array{tipo: string, fecha_inicio: string, fecha_fin: string, interpretacion: string, origen: string}
     */
    public function interpretar(string $frase): array
    {
        $resultado = $this->conGemini($frase);
        $origen = 'gemini';

        if ($resultado === null) {
            $resultado = $this->conReglas($frase);
            $origen = 'reglas';
        }

        return $resultado + [
            'interpretacion' => $this->nombres()[$resultado['tipo']].', del '
                .Carbon::parse($resultado['fecha_inicio'])->format('d/m/Y').' al '
                .Carbon::parse($resultado['fecha_fin'])->format('d/m/Y'),
            'origen' => $origen,
        ];
    }

    // Los ocho reportes estáticos (clave => nombre), leídos de ReporteService.
    private function nombres(): array
    {
        return array_merge(...array_values(ReporteService::ESTATICOS));
    }

    // Pide la traducción a Gemini. Devuelve null ante cualquier fallo: la pantalla nunca se rompe.
    private function conGemini(string $frase): ?array
    {
        $clave = config('services.gemini.key');

        if (blank($clave)) {
            return null;
        }

        $modelo = config('services.gemini.modelo') ?: 'gemini-flash-latest';

        try {
            $respuesta = Http::timeout(8)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$modelo}:generateContent?key={$clave}",
                [
                    'contents' => [['parts' => [['text' => $this->prompt($frase)]]]],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $this->esquema(),
                        'temperature' => 0,
                    ],
                ]
            );

            if (! $respuesta->successful()) {
                // Sin el cuerpo ni la URL: la URL lleva la clave.
                Log::warning('Reportes por voz: Gemini respondió con estado '.$respuesta->status().'.');

                return null;
            }

            $texto = $respuesta->json('candidates.0.content.parts.0.text');
            $datos = is_string($texto) ? json_decode($texto, true) : null;

            return is_array($datos) ? $this->validar($datos) : null;
        } catch (Throwable $e) {
            Log::warning('Reportes por voz: no se pudo consultar Gemini ('.class_basename($e).').');

            return null;
        }
    }

    // No se confía en la respuesta: tipo entre los ocho y fechas Y-m-d válidas con inicio <= fin.
    private function validar(array $datos): ?array
    {
        $tipo = $datos['tipo'] ?? null;
        $inicio = $datos['fecha_inicio'] ?? null;
        $fin = $datos['fecha_fin'] ?? null;

        if (! is_string($tipo) || ! array_key_exists($tipo, $this->nombres())) {
            return null;
        }

        if (! $this->fechaValida($inicio) || ! $this->fechaValida($fin) || $inicio > $fin) {
            return null;
        }

        return ['tipo' => $tipo, 'fecha_inicio' => $inicio, 'fecha_fin' => $fin];
    }

    private function fechaValida(mixed $fecha): bool
    {
        if (! is_string($fecha)) {
            return false;
        }

        $objeto = \DateTime::createFromFormat('!Y-m-d', $fecha);

        return $objeto !== false && $objeto->format('Y-m-d') === $fecha;
    }

    private function prompt(string $frase): string
    {
        $hoy = CarbonImmutable::now(self::ZONA);
        $lista = collect($this->nombres())->map(fn ($nombre, $clave) => "- {$clave}: {$nombre}")->implode("\n");

        return <<<TEXTO
        Eres el intérprete de consultas de reportes de un consultorio dental en Bolivia.
        Hoy es {$hoy->format('Y-m-d')} ({$hoy->locale('es')->isoFormat('dddd')}), zona horaria America/La_Paz.

        Reportes disponibles (clave: nombre):
        {$lista}

        Traduce la consulta del usuario a un único reporte y un periodo:
        - "tipo" debe ser exactamente una de las claves anteriores. Si la consulta no corresponde a ninguno, elige el más cercano.
        - "fecha_inicio" y "fecha_fin" en formato Y-m-d, con fecha_inicio menor o igual que fecha_fin.
        - Las semanas empiezan el lunes. Si se nombra un mes sin año, usa el año en curso.
        - Si la consulta no indica ningún periodo, usa el mes en curso completo.
        Responde solo con el JSON pedido, sin texto adicional.

        Consulta: "{$frase}"
        TEXTO;
    }

    private function esquema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tipo' => ['type' => 'string', 'enum' => array_keys($this->nombres())],
                'fecha_inicio' => ['type' => 'string'],
                'fecha_fin' => ['type' => 'string'],
            ],
            'required' => ['tipo', 'fecha_inicio', 'fecha_fin'],
        ];
    }

    // Respaldo por reglas: palabras clave para el reporte y expresiones comunes para el periodo.
    private function conReglas(string $frase): array
    {
        $texto = $this->normalizar($frase);
        [$inicio, $fin] = $this->periodo($texto);

        return [
            'tipo' => $this->tipo($texto),
            'fecha_inicio' => $inicio->format('Y-m-d'),
            'fecha_fin' => $fin->format('Y-m-d'),
        ];
    }

    // Minúsculas, sin tildes y con un solo espacio entre palabras.
    private function normalizar(string $frase): string
    {
        return trim(preg_replace('/\s+/', ' ', Str::lower(Str::ascii($frase))));
    }

    private function tipo(string $texto): string
    {
        foreach (self::PALABRAS as $tipo => $palabras) {
            if (Str::contains($texto, $palabras)) {
                return $tipo;
            }
        }

        // Ninguna coincidencia: el reporte clínico más general.
        return 'pacientes_atendidos';
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function periodo(string $texto): array
    {
        $hoy = CarbonImmutable::now(self::ZONA)->startOfDay();

        if (preg_match('/ultim[oa]s? (\d{1,4}) (dia|semana|mes)/', $texto, $m)) {
            $n = max(1, (int) $m[1]);
            $inicio = match ($m[2]) {
                'dia' => $hoy->subDays($n - 1),
                'semana' => $hoy->subWeeks($n)->addDay(),
                'mes' => $hoy->subMonthsNoOverflow($n)->addDay(),
            };

            return [$inicio, $hoy];
        }

        if (preg_match('/\bhoy\b/', $texto)) {
            return [$hoy, $hoy];
        }

        if (preg_match('/\bayer\b/', $texto)) {
            return [$hoy->subDay(), $hoy->subDay()];
        }

        if (preg_match('/semana pasada|semana anterior/', $texto)) {
            $semana = $hoy->subWeek();

            return [$semana->startOfWeek(Carbon::MONDAY), $semana->endOfWeek(Carbon::SUNDAY)->startOfDay()];
        }

        if (preg_match('/(esta|la) semana|semana actual/', $texto)) {
            return [$hoy->startOfWeek(Carbon::MONDAY), $hoy->endOfWeek(Carbon::SUNDAY)->startOfDay()];
        }

        if (preg_match('/mes pasado|mes anterior/', $texto)) {
            $mes = $hoy->subMonthNoOverflow();

            return [$mes->startOfMonth(), $mes->endOfMonth()->startOfDay()];
        }

        if (preg_match('/\b(ano|anio) (pasado|anterior)\b/', $texto)) {
            $anio = $hoy->subYear();

            return [$anio->startOfYear(), $anio->endOfYear()->startOfDay()];
        }

        if (preg_match('/\b(este|el) (ano|anio)\b|\b(ano|anio) actual\b/', $texto)) {
            return [$hoy->startOfYear(), $hoy->endOfYear()->startOfDay()];
        }

        // Nombres de meses (uno o un rango «de marzo a junio») con un año opcional de cuatro dígitos.
        $anio = preg_match('/\b(19|20)\d{2}\b/', $texto, $a) ? (int) $a[0] : null;
        preg_match_all('/\b('.implode('|', array_keys(self::MESES)).')\b/', $texto, $meses);

        if ($meses[1] !== []) {
            $numeros = array_map(fn ($mes) => self::MESES[$mes], $meses[1]);
            $base = $hoy->setDate($anio ?? $hoy->year, 1, 1);

            return [
                $base->month(min($numeros))->startOfMonth(),
                $base->month(max($numeros))->endOfMonth()->startOfDay(),
            ];
        }

        if ($anio !== null) {
            $base = $hoy->setDate($anio, 1, 1);

            return [$base->startOfYear(), $base->endOfYear()->startOfDay()];
        }

        // «este mes», «mes actual» o ningún periodo reconocido: el mes en curso.
        return [$hoy->startOfMonth(), $hoy->endOfMonth()->startOfDay()];
    }
}
