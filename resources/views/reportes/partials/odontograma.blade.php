{{-- RF-84 y RF-85: odontograma de solo lectura, con el mismo esquema FDI y colores de odontogramas/show.
     Recibe $dibujo (ReporteService::dibujo: arcadas, piezas y leyenda) y opcionalmente $titulo. --}}

<div class="overflow-x-auto">
    <div class="mx-auto w-max space-y-4" role="img" aria-label="{{ $titulo ?? 'Odontograma' }}">
        @foreach ($dibujo['arcadas'] as $arcada => $cuadrantes)
            <div @class(['border-t border-dashed border-line pt-4' => $loop->index === 2])>
                <p class="mb-1.5 text-center text-[11px] font-semibold uppercase tracking-wide text-ink-soft">{{ $arcada }}</p>
                <div class="flex items-center justify-center gap-2">
                    @foreach ($cuadrantes as $piezas)
                        @if (! $loop->first)
                            {{-- Línea media --}}
                            <span class="h-8 w-px bg-ink-soft/40" aria-hidden="true"></span>
                        @endif
                        <div class="flex gap-0.5">
                            @foreach ($piezas as $fdi)
                                @php $p = $dibujo['piezas'][$fdi] ?? null; @endphp
                                @if ($p)
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md border border-black/10 text-[11px] font-bold tabular-nums"
                                          style="background-color: {{ $p['color'] }}; color: {{ \App\Services\ReporteService::textoSobre($p['color']) }};"
                                          title="{{ $fdi }} · {{ $p['estado'] }}">{{ $fdi }}</span>
                                @else
                                    <span class="flex h-8 w-8 items-center justify-center rounded-md border border-dashed border-line text-[11px] tabular-nums text-ink-mute"
                                          title="{{ $fdi }} · No registrada">{{ $fdi }}</span>
                                @endif
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

{{-- Leyenda: el color nunca va solo (RNF-26) --}}
<ul class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-ink-soft">
    @foreach ($dibujo['leyenda'] as $estado)
        <li class="flex items-center gap-1.5">
            <span class="h-3 w-3 rounded-sm border border-black/10" style="background-color: {{ $estado['color'] }};" aria-hidden="true"></span>
            {{ $estado['estado'] }}: <span class="font-semibold tabular-nums text-ink">{{ $estado['cantidad'] }}</span>
        </li>
    @endforeach
</ul>
