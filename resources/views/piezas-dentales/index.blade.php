{{-- CU22 Consultar piezas dentales (solo administrador). Catálogo fijo: solo se consulta, sin buscador ni paginación. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Catálogo de piezas dentales" subtitulo="Piezas identificadas con la numeración FDI. Es un catálogo fijo: solo se consulta." />
    </x-slot>

    <x-pagina>
        <x-catalogos.pestanas activa="piezas" />

        <section class="dr-card overflow-hidden" aria-label="Listado de piezas dentales">
            <div class="border-b border-line bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                <span class="font-semibold text-ink">{{ $piezas->count() }}</span> pieza(s) dental(es)
            </div>

            @if ($piezas->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">No hay piezas dentales registradas.</p>
                </div>
            @else
                {{-- Tabla (desde 768 px) --}}
                <div class="hidden md:block">
                    <table class="dr-table">
                        <caption class="sr-only">Catálogo de piezas dentales</caption>
                        <thead>
                            <tr>
                                <th scope="col">Código FDI</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Cuadrante</th>
                                <th scope="col">Tipo de pieza</th>
                                <th scope="col">Dentición</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($piezas as $p)
                                <tr>
                                    <td class="whitespace-nowrap font-semibold tabular-nums">
                                        <a href="{{ route('piezas-dentales.mostrar', $p->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $p->codigo_fdi }}</a>
                                    </td>
                                    <td class="font-semibold text-ink">{{ $p->nombre }}</td>
                                    <td class="tabular-nums text-ink-soft">{{ $p->cuadrante }}</td>
                                    <td class="text-ink-soft">{{ ucfirst($p->tipo_pieza) }}</td>
                                    <td class="text-ink-soft">{{ ucfirst($p->tipo_denticion) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 min-[480px]:grid-cols-2 sm:p-4 md:hidden">
                    @foreach ($piezas as $p)
                        <li class="dr-item">
                            <div class="flex items-baseline justify-between gap-3">
                                <a href="{{ route('piezas-dentales.mostrar', $p->id) }}" class="font-display text-lg font-bold tabular-nums text-brand-700 hover:text-brand-900">{{ $p->codigo_fdi }}</a>
                                <span class="text-xs text-ink-soft">Cuadrante <span class="font-semibold tabular-nums text-ink">{{ $p->cuadrante }}</span></span>
                            </div>
                            <p class="break-words text-sm font-semibold text-ink">{{ $p->nombre }}</p>
                            <p class="text-sm text-ink-soft">{{ ucfirst($p->tipo_pieza) }} · {{ ucfirst($p->tipo_denticion) }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </x-pagina>
</x-app-layout>
