{{-- Resultado del reporte y botones de CU45, con los mismos parámetros del reporte en pantalla --}}
@php
    $parametros = request()->query() + ['reporte' => $reporte];

    // Con muchas columnas la tabla solo cabe desde 1280 px; con pocas, desde 1024 px.
    $anchas = $resultado ? count($resultado['columnas']) > 5 : false;
@endphp

@if (! $resultado)
    <section class="dr-card px-6 py-12 text-center" aria-label="Resultado">
        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
            <x-icon name="P5" class="h-7 w-7" />
        </span>
        <p class="mt-4 text-base font-semibold text-ink">Aquí aparecerá el resultado</p>
        <p class="mt-1 text-sm text-ink-soft">Genere un reporte predefinido o uno personalizado para verlo y exportarlo.</p>
    </section>
@else
    <section class="dr-card overflow-hidden" aria-labelledby="resultado-titulo">
        <header class="flex flex-col gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
            <div class="min-w-0">
                <h3 id="resultado-titulo" class="text-lg font-bold text-ink">{{ $resultado['titulo'] }}</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Periodo: <span class="font-semibold text-ink">{{ $resultado['periodo'] }}</span></p>
            </div>

            {{-- CU45: exportar el mismo reporte (mismos parámetros) --}}
            <div class="flex flex-col gap-2 min-[420px]:flex-row sm:shrink-0">
                <a href="{{ route('reportes.exportar', $parametros + ['formato' => 'pdf']) }}" class="btn btn-secondary btn-sm">
                    <x-icon name="download" class="h-4 w-4" /> Exportar PDF
                </a>
                <a href="{{ route('reportes.exportar', $parametros + ['formato' => 'excel']) }}" class="btn btn-secondary btn-sm">
                    <x-icon name="download" class="h-4 w-4" /> Exportar Excel
                </a>
            </div>
        </header>

        {{-- Totales calculados por el backend --}}
        @if ($resultado['totales'])
            <dl class="grid grid-cols-1 gap-3 border-b border-line p-4 min-[420px]:grid-cols-2 sm:p-5 lg:grid-cols-4">
                @foreach ($resultado['totales'] as $etiqueta => $valor)
                    <div class="min-w-0 rounded-xl bg-surface px-4 py-3">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">{{ $etiqueta }}</dt>
                        <dd class="mt-1 font-display text-xl font-bold text-ink"><x-reportes.valor :resultado="$resultado" :clave="$etiqueta" :valor="$valor" /></dd>
                    </div>
                @endforeach
            </dl>
        @endif

        @if ($resultado['filas'] === [])
            <div class="px-6 py-12 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                    <x-icon name="search" class="h-7 w-7" />
                </span>
                <p class="mt-4 text-base font-semibold text-ink">No hay registros para los filtros elegidos</p>
                <p class="mt-1 text-sm text-ink-soft">Pruebe con otro periodo o con menos filtros.</p>
            </div>
        @else
            {{-- Tabla --}}
            <div @class(['hidden overflow-x-auto', 'xl:block' => $anchas, 'lg:block' => ! $anchas])>
                <table class="dr-table">
                    <caption class="sr-only">{{ $resultado['titulo'] }}, {{ $resultado['periodo'] }}</caption>
                    <thead>
                        <tr>
                            @foreach ($resultado['columnas'] as $clave => $encabezado)
                                <th scope="col" @class(['text-right' => in_array($clave, $resultado['numericas'], true)])>{{ $encabezado }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($resultado['filas'] as $fila)
                            <tr class="align-top">
                                @foreach ($resultado['columnas'] as $clave => $encabezado)
                                    <td @class(['text-ink', 'text-right tabular-nums' => in_array($clave, $resultado['numericas'], true), 'whitespace-nowrap' => in_array($clave, $resultado['numericas'], true) || str_contains($clave, 'fecha') || str_contains($clave, 'hora')])>
                                        <x-reportes.valor :resultado="$resultado" :clave="$clave" :valor="$fila[$clave]" />
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Tarjetas (móvil y tablet): la primera columna es el título y las demás, pares etiqueta/valor --}}
            <ul @class(['grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2', 'xl:hidden' => $anchas, 'lg:hidden' => ! $anchas])>
                @foreach ($resultado['filas'] as $fila)
                    <li class="dr-item">
                        @foreach ($resultado['columnas'] as $clave => $encabezado)
                            @if ($loop->first)
                                <p class="break-words font-display text-base font-bold text-ink"><x-reportes.valor :resultado="$resultado" :clave="$clave" :valor="$fila[$clave]" /></p>
                                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                            @else
                                <div class="min-w-0">
                                    <dt class="text-xs text-ink-soft">{{ $encabezado }}</dt>
                                    <dd class="break-words font-medium text-ink"><x-reportes.valor :resultado="$resultado" :clave="$clave" :valor="$fila[$clave]" /></dd>
                                </div>
                            @endif
                            @if ($loop->last)
                                </dl>
                            @endif
                        @endforeach
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endif
