{{-- CU26 Comparar odontogramas: estado de cada pieza en dos fechas. --}}
@php
    $etiqueta = fn ($o) => $o->fecha_registro->format('d/m/Y').' · '.ucfirst($o->tipo).($o->estado === 'anulado' ? ' (anulado)' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Comparar odontogramas: '.$paciente->nombreCompleto()"
                           subtitulo="Elija dos odontogramas para ver qué piezas cambiaron de estado."
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

        @isset($aviso)
            {{-- Flujo A2: menos de dos odontogramas --}}
            <x-alerta tipo="aviso">{{ $aviso }}</x-alerta>
        @else
            {{-- Elegir los dos odontogramas (parámetros GET a y b) --}}
            <form method="GET" action="{{ route('odontogramas.comparar', $paciente->id) }}" class="dr-card overflow-hidden">
                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                    <x-campo name="a" label="Odontograma A" :requerido="true">
                        <select id="a" name="a" class="dr-input" required>
                            <option value="">Seleccione…</option>
                            @foreach ($odontogramas as $o)
                                <option value="{{ $o->id }}" @selected($a?->id === $o->id)>{{ $etiqueta($o) }}</option>
                            @endforeach
                        </select>
                    </x-campo>

                    <x-campo name="b" label="Odontograma B" :requerido="true">
                        <select id="b" name="b" class="dr-input" required>
                            <option value="">Seleccione…</option>
                            @foreach ($odontogramas as $o)
                                <option value="{{ $o->id }}" @selected($b?->id === $o->id)>{{ $etiqueta($o) }}</option>
                            @endforeach
                        </select>
                    </x-campo>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-end sm:px-6">
                    <x-primary-button class="w-full sm:w-auto"><x-icon name="refresh" class="h-4 w-4" /> Comparar</x-primary-button>
                </div>
            </form>

            @if ($filas !== null)
                @php $cambios = collect($filas)->where('cambio', true)->count(); @endphp

                <section class="dr-card overflow-hidden" aria-labelledby="resultado-comparacion">
                    <header class="flex flex-col gap-2 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                        <div class="min-w-0">
                            <h3 id="resultado-comparacion" class="text-lg font-bold text-ink">Resultado</h3>
                            <p class="mt-0.5 text-sm text-ink-soft">A: {{ $etiqueta($a) }} · B: {{ $etiqueta($b) }}</p>
                        </div>
                        <x-insignia :tono="$cambios > 0 ? 'aviso' : 'exito'" class="self-start sm:self-center">
                            {{ $cambios }} {{ $cambios === 1 ? 'pieza cambió' : 'piezas cambiaron' }} de {{ count($filas) }}
                        </x-insignia>
                    </header>

                    {{-- Tabla (desde 1024 px) --}}
                    <div class="hidden lg:block">
                        <table class="dr-table">
                            <caption class="sr-only">Estado de cada pieza en los dos odontogramas</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Pieza</th>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Estado en A</th>
                                    <th scope="col">Estado en B</th>
                                    <th scope="col">Cambio</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($filas as $fila)
                                    <tr @class(['bg-amber-50/70' => $fila['cambio']])>
                                        <td class="font-semibold tabular-nums text-ink">{{ $fila['codigo_fdi'] }}</td>
                                        <td class="text-ink-soft">{{ $fila['nombre'] }}</td>
                                        @foreach (['a', 'b'] as $lado)
                                            <td>
                                                @if ($fila['estado_'.$lado])
                                                    <span class="inline-flex items-center gap-2 text-ink">
                                                        <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10" style="background-color: {{ $fila['color_'.$lado] ?? '#E1E8EB' }};" aria-hidden="true"></span>
                                                        {{ $fila['estado_'.$lado] }}
                                                    </span>
                                                @else
                                                    <span class="text-ink-mute">No registrada</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>
                                            @if ($fila['cambio'])
                                                <x-insignia tono="aviso">Cambió</x-insignia>
                                            @else
                                                <span class="text-ink-mute">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tarjetas: 1 columna, 2 desde 768 px --}}
                    <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 lg:hidden">
                        @foreach ($filas as $fila)
                            <li @class(['dr-item', '!border-amber-300 !bg-amber-50/70' => $fila['cambio']])>
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 break-words text-ink"><span class="font-display font-bold tabular-nums">{{ $fila['codigo_fdi'] }}</span> · {{ $fila['nombre'] }}</p>
                                    @if ($fila['cambio'])
                                        <x-insignia tono="aviso">Cambió</x-insignia>
                                    @endif
                                </div>
                                <dl class="grid grid-cols-2 gap-x-3 text-sm">
                                    @foreach (['a' => 'Estado en A', 'b' => 'Estado en B'] as $lado => $titulo)
                                        <div class="min-w-0">
                                            <dt class="text-xs text-ink-soft">{{ $titulo }}</dt>
                                            <dd class="font-medium text-ink">
                                                @if ($fila['estado_'.$lado])
                                                    <span class="inline-flex items-center gap-2">
                                                        <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10" style="background-color: {{ $fila['color_'.$lado] ?? '#E1E8EB' }};" aria-hidden="true"></span>
                                                        {{ $fila['estado_'.$lado] }}
                                                    </span>
                                                @else
                                                    <span class="text-ink-mute">No registrada</span>
                                                @endif
                                            </dd>
                                        </div>
                                    @endforeach
                                </dl>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        @endisset

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a los odontogramas
            </a>
            {{-- RF-85: con la pareja elegida; sin elegir, el inicial y el último de evolución --}}
            @unless (isset($aviso))
                <a href="{{ route('reportes.odontogramas', ($a && $b) ? [$paciente->id, 'a' => $a->id, 'b' => $b->id] : $paciente->id) }}" class="btn btn-primary">
                    <x-icon name="printer" class="h-4 w-4" /> Ver comparativo imprimible
                </a>
            @endunless
        </div>
    </x-pagina>
</x-app-layout>
