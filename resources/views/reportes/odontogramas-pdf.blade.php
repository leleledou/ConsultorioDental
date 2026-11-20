{{-- RF-85 Comparativo de odontogramas: plantilla del PDF, con el encabezado común de reportes/pdf. DejaVu Sans: tildes y ñ correctas. --}}
@php
    $paciente = $datos['paciente'];
    $resumen = $datos['resumen'];
    $etiqueta = fn ($o) => $o->fecha_registro->format('d/m/Y').' · '.ucfirst($o->tipo).' · '.$o->odontologo->nombres.' '.$o->odontologo->apellidos;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $datos['titulo'] }}</title>
    @include('reportes.partials.estilos-pdf')
</head>
<body>
    @include('reportes.partials.encabezado-pdf', ['titulo' => $datos['titulo'], 'detalle' => 'Paciente: '.$paciente->nombreCompleto().' · CI '.$paciente->ci])

    <h3>Resumen</h3>
    <table>
        <tr><th>Piezas comparadas</th><td>{{ $resumen['total_piezas'] }}</td><th>Cambiaron</th><td>{{ $resumen['cambiaron'] }}</td><th>Sin cambios</th><td>{{ $resumen['sin_cambio'] }}</td></tr>
    </table>
    @foreach ($resumen['por_estado'] as $grupo)
        <p><span class="muestra" style="background-color: {{ $grupo['color'] ?? '#E1E8EB' }};"></span> <strong>{{ $grupo['estado'] }}: {{ $grupo['cantidad'] }}</strong> (piezas {{ implode(', ', $grupo['piezas']) }})</p>
    @endforeach

    {{-- Los dos odontogramas lado a lado (hoja apaisada) --}}
    <table>
        <tr>
            <td class="lado">
                <h3>Odontograma A: {{ $etiqueta($datos['a']) }}</h3>
                @include('reportes.partials.odontograma-pdf', ['dibujo' => $datos['dibujo_a']])
            </td>
            <td class="lado">
                <h3>Odontograma B: {{ $etiqueta($datos['b']) }}</h3>
                @include('reportes.partials.odontograma-pdf', ['dibujo' => $datos['dibujo_b']])
            </td>
        </tr>
    </table>

    <h3>Piezas que cambiaron</h3>
    @php $cambios = collect($datos['filas'])->where('cambio', true); @endphp
    @if ($cambios->isEmpty())
        <p>Ninguna pieza cambió de estado entre los dos odontogramas.</p>
    @else
        <table>
            <thead><tr><th>Pieza</th><th>Nombre</th><th>Estado en A</th><th>Estado en B</th></tr></thead>
            <tbody>
                @foreach ($cambios as $fila)
                    <tr>
                        <td>{{ $fila['codigo_fdi'] }}</td>
                        <td>{{ $fila['nombre'] }}</td>
                        <td>{{ $fila['estado_a'] ?? 'No registrada' }}</td>
                        <td>{{ $fila['estado_b'] ?? 'No registrada' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
