{{-- RF-84 y RF-85: odontograma para DomPDF (tablas, sin flex). Recibe $dibujo de ReporteService::dibujo. --}}

@foreach ($dibujo['arcadas'] as $arcada => $cuadrantes)
    <p class="arcada">{{ $arcada }}</p>
    <table class="odontograma">
        <tr>
            @foreach ($cuadrantes as $piezas)
                @if (! $loop->first)
                    <td class="linea-media"></td>
                @endif
                @foreach ($piezas as $fdi)
                    @php $p = $dibujo['piezas'][$fdi] ?? null; @endphp
                    @if ($p)
                        <td class="pieza" style="background-color: {{ $p['color'] }}; color: {{ \App\Services\ReporteService::textoSobre($p['color']) }};">{{ $fdi }}</td>
                    @else
                        <td class="pieza vacia">{{ $fdi }}</td>
                    @endif
                @endforeach
            @endforeach
        </tr>
    </table>
@endforeach

<p class="leyenda">
    @foreach ($dibujo['leyenda'] as $estado)
        <span class="muestra" style="background-color: {{ $estado['color'] }};"></span> {{ $estado['estado'] }}: {{ $estado['cantidad'] }}@if (! $loop->last) &nbsp;&nbsp; @endif
    @endforeach
</p>
