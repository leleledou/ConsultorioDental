{{-- RF-85 Comparativo de odontogramas: documento por paciente (ReporteService::comparativoOdontograma) y exportación a PDF por CU45. --}}
@php
    $paciente = $datos['paciente'];
    $etiqueta = fn ($o) => $o->fecha_registro->format('d/m/Y').' · '.ucfirst($o->tipo).($o->estado === 'anulado' ? ' (anulado)' : '');
    $hayComparativo = $datos['aviso'] === null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Comparativo de odontogramas: '.$paciente->nombreCompleto()"
                           subtitulo="Estado de cada pieza en dos odontogramas, con el resumen de los cambios."
                           :volver="route('odontogramas.listar', $paciente->id)" volver-texto="Odontogramas" />
    </x-slot>

    <x-pagina>
        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        @unless ($hayComparativo)
            <x-alerta tipo="aviso">{{ $datos['aviso'] }}</x-alerta>
        @else
            @php
                $a = $datos['a'];
                $b = $datos['b'];
                $resumen = $datos['resumen'];
                $cambios = collect($datos['filas'])->where('cambio', true);
                $exportar = route('reportes.exportar', ['reporte' => 'odontogramas', 'paciente' => $paciente->id, 'a' => $a->id, 'b' => $b->id, 'formato' => 'pdf']);
            @endphp

            {{-- Elegir otros dos odontogramas; por defecto, el inicial y el último de evolución --}}
            <form method="GET" action="{{ route('reportes.odontogramas', $paciente->id) }}" class="dr-card overflow-hidden">
                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    @foreach (['a' => ['Odontograma A', $a], 'b' => ['Odontograma B', $b]] as $campo => [$texto, $elegido])
                        <x-campo :name="$campo" :label="$texto" :requerido="true">
                            <select id="{{ $campo }}" name="{{ $campo }}" class="dr-input" required>
                                @foreach ($datos['odontogramas'] as $o)
                                    <option value="{{ $o->id }}" @selected($elegido->id === $o->id)>{{ $etiqueta($o) }}</option>
                                @endforeach
                            </select>
                        </x-campo>
                    @endforeach
                </div>
                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <a href="{{ $exportar }}" class="btn btn-secondary w-full sm:w-auto"><x-icon name="download" class="h-4 w-4" /> Exportar a PDF</a>
                    <x-primary-button class="w-full sm:w-auto"><x-icon name="refresh" class="h-4 w-4" /> Comparar</x-primary-button>
                </div>
            </form>

            {{-- Resumen --}}
            <section class="dr-card p-5 sm:p-6" aria-labelledby="h-resumen">
                <h3 id="h-resumen" class="text-lg font-bold text-ink">Resumen</h3>
                <dl class="mt-4 grid grid-cols-1 gap-3 min-[420px]:grid-cols-3">
                    <div class="rounded-xl bg-surface p-4 ring-1 ring-line">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Piezas comparadas</dt>
                        <dd class="mt-1 font-display text-2xl font-bold tabular-nums text-ink">{{ $resumen['total_piezas'] }}</dd>
                    </div>
                    <div @class(['rounded-xl p-4 ring-1', 'bg-amber-50 ring-amber-300' => $resumen['cambiaron'] > 0, 'bg-surface ring-line' => $resumen['cambiaron'] === 0])>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Cambiaron</dt>
                        <dd class="mt-1 font-display text-2xl font-bold tabular-nums text-ink">{{ $resumen['cambiaron'] }}</dd>
                    </div>
                    <div class="rounded-xl bg-surface p-4 ring-1 ring-line">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Sin cambios</dt>
                        <dd class="mt-1 font-display text-2xl font-bold tabular-nums text-ink">{{ $resumen['sin_cambio'] }}</dd>
                    </div>
                </dl>

                @if ($resumen['por_estado'] !== [])
                    <p class="mt-5 text-xs font-semibold uppercase tracking-wide text-ink-soft">Cambios por estado de destino</p>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach ($resumen['por_estado'] as $grupo)
                            <li class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10" style="background-color: {{ $grupo['color'] ?? '#E1E8EB' }};" aria-hidden="true"></span>
                                <span class="font-semibold text-ink">{{ $grupo['estado'] }}: {{ $grupo['cantidad'] }}</span>
                                <span class="text-ink-soft">(piezas {{ implode(', ', $grupo['piezas']) }})</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Los dos odontogramas lado a lado (uno debajo del otro en pantallas angostas) --}}
            <div class="grid grid-cols-1 gap-6 2xl:grid-cols-2">
                @foreach (['A' => [$a, $datos['dibujo_a']], 'B' => [$b, $datos['dibujo_b']]] as $letra => [$odontograma, $dibujo])
                    <section class="dr-card min-w-0 overflow-hidden" aria-labelledby="h-odontograma-{{ $letra }}">
                        <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                            <h3 id="h-odontograma-{{ $letra }}" class="text-lg font-bold text-ink">Odontograma {{ $letra }}</h3>
                            <p class="mt-0.5 text-sm text-ink-soft">{{ $etiqueta($odontograma) }} · {{ $odontograma->odontologo->nombres }} {{ $odontograma->odontologo->apellidos }}</p>
                        </header>
                        <div class="p-5 sm:p-6">
                            @include('reportes.partials.odontograma', ['dibujo' => $dibujo, 'titulo' => 'Odontograma '.$letra])
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Tabla de cambios: solo las piezas que cambiaron --}}
            <section class="dr-card overflow-hidden" aria-labelledby="h-cambios">
                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="h-cambios" class="text-lg font-bold text-ink">Piezas que cambiaron</h3>
                </header>
                @if ($cambios->isEmpty())
                    <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">Ninguna pieza cambió de estado entre los dos odontogramas.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($cambios as $fila)
                            <li class="grid grid-cols-1 gap-1 px-5 py-3 text-sm sm:grid-cols-3 sm:items-center sm:px-6">
                                <p class="text-ink"><span class="font-display font-bold tabular-nums">{{ $fila['codigo_fdi'] }}</span> · {{ $fila['nombre'] }}</p>
                                @foreach (['a', 'b'] as $lado)
                                    <p class="flex items-center gap-2 text-ink">
                                        <span class="w-4 shrink-0 text-xs font-bold text-ink-soft">{{ strtoupper($lado) }}</span>
                                        @if ($fila['estado_'.$lado])
                                            <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10" style="background-color: {{ $fila['color_'.$lado] ?? '#E1E8EB' }};" aria-hidden="true"></span>
                                            {{ $fila['estado_'.$lado] }}
                                        @else
                                            <span class="text-ink-mute">No registrada</span>
                                        @endif
                                    </p>
                                @endforeach
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endunless

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a los odontogramas
            </a>
        </div>
    </x-pagina>
</x-app-layout>
