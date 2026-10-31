{{-- CU45 Exportar reporte: plantilla del PDF. DejaVu Sans: tildes y ñ correctas. --}}
@php
    use App\Services\ReporteService;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $datos['titulo'] }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 14px; margin: 0; }
        h2 { font-size: 12px; margin: 8px 0 2px; }
        p { margin: 2px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #9ca3af; padding: 4px; text-align: left; vertical-align: top; }
        th { background-color: #e5e7eb; }
        .numero { text-align: right; white-space: nowrap; }
        .totales { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Consultorio Odontológico DentalRox</h1>
    <h2>{{ $datos['titulo'] }}</h2>
    <p>Periodo: {{ $datos['periodo'] }}</p>
    <p>Fecha de generación: {{ now()->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                @foreach ($datos['columnas'] as $clave => $encabezado)
                    <th @class(['numero' => in_array($clave, $datos['numericas'], true)])>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($datos['filas'] as $fila)
                <tr>
                    @foreach ($datos['columnas'] as $clave => $encabezado)
                        <td @class(['numero' => in_array($clave, $datos['numericas'], true)])>{{ ReporteService::formatear($datos, $clave, $fila[$clave]) }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($datos['columnas']) }}">Sin registros</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totales">
        @foreach ($datos['totales'] as $etiqueta => $valor)
            <p><strong>{{ $etiqueta }}:</strong> {{ ReporteService::formatear($datos, $etiqueta, $valor) }}</p>
        @endforeach
    </div>
</body>
</html>
