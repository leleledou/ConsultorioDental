{{-- CU07 Consultar bitácora (solo lectura, RN-02). --}}
@php
    $hayFiltros = collect(['fecha_desde', 'fecha_hasta', 'usuario_id', 'accion', 'ip'])->contains(fn ($c) => filled(request($c)));

@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Bitácora" subtitulo="Registro de las acciones realizadas en el sistema. Solo se puede consultar." />
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

        {{-- CU07 pasos 2 y 3: filtros --}}
        <section class="dr-card overflow-hidden" aria-labelledby="filtros-bitacora">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="filtros-bitacora" class="text-lg font-bold text-ink">Filtros</h3>
            </header>

            <form method="GET" action="{{ route('bitacora.listar') }}" class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-6">
                <x-campo name="fecha_desde" label="Fecha desde" class="lg:col-span-1">
                    <x-text-input id="fecha_desde" name="fecha_desde" type="date" :value="request('fecha_desde')" :error="$errors->has('fecha_desde')" />
                </x-campo>

                <x-campo name="fecha_hasta" label="Fecha hasta" class="lg:col-span-1">
                    <x-text-input id="fecha_hasta" name="fecha_hasta" type="date" :value="request('fecha_hasta')" :error="$errors->has('fecha_hasta')" />
                </x-campo>

                <x-campo name="usuario_id" label="Usuario" class="lg:col-span-2">
                    <select id="usuario_id" name="usuario_id" class="dr-input">
                        <option value="">Todos</option>
                        @foreach ($usuarios as $u)
                            <option value="{{ $u->id }}" @selected((string) request('usuario_id') === (string) $u->id)>
                                {{ $u->apellidos }}, {{ $u->nombres }} ({{ $u->usuario }})
                            </option>
                        @endforeach
                    </select>
                </x-campo>

                <x-campo name="accion" label="Acción" class="lg:col-span-2">
                    <select id="accion" name="accion" class="dr-input">
                        <option value="">Todas</option>
                        @foreach ($acciones as $accion)
                            <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
                        @endforeach
                    </select>
                </x-campo>

                <x-campo name="ip" label="IP" class="lg:col-span-2">
                    <x-text-input id="ip" name="ip" type="text" :value="request('ip')" maxlength="45" :error="$errors->has('ip')" />
                </x-campo>

                <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-end lg:col-span-4 lg:justify-end">
                    <x-primary-button class="w-full sm:w-auto">Filtrar</x-primary-button>
                    <a href="{{ route('bitacora.listar') }}" class="btn btn-secondary w-full sm:w-auto">Limpiar filtros</a>
                </div>
            </form>
        </section>

        {{-- CU07 pasos 4 y 5: resultados --}}
        <section class="dr-card overflow-hidden" aria-label="Registros de la bitácora">
            <div class="border-b border-line bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                <span class="font-semibold text-ink">{{ $registros->total() }}</span> registro(s) encontrado(s).
            </div>

            @if ($registros->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="search" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">No existen registros para los filtros aplicados.</p>
                    @if ($hayFiltros)
                        <p class="mt-1 text-sm text-ink-soft">Pruebe con otro periodo o quite algún filtro.</p>
                    @endif
                </div>
            @else
                {{-- Tabla (escritorio ancho, desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Registros de la bitácora</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Hora</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Acción</th>
                                <th scope="col">Tabla</th>
                                <th scope="col">Registro</th>
                                <th scope="col">IP</th>
                                <th scope="col"><span class="sr-only">Detalle</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($registros as $registro)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap font-medium tabular-nums text-ink">{{ $registro->fecha_hora->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $registro->fecha_hora->format('H:i:s') }}</td>
                                    <td class="text-ink">
                                        @if ($registro->usuario)
                                            {{ $registro->usuario->usuario }}
                                        @elseif ($registro->usuario_digitado)
                                            <span class="text-ink-soft">«{{ $registro->usuario_digitado }}» (digitado)</span>
                                        @else
                                            <span class="text-ink-mute">—</span>
                                        @endif
                                    </td>
                                    <td><x-bitacora.accion :accion="$registro->accion" /></td>
                                    <td class="text-ink-soft">{{ $registro->tabla_afectada ?? '—' }}</td>
                                    <td class="tabular-nums text-ink-soft">{{ $registro->registro_id ?? '—' }}</td>
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $registro->ip }}</td>
                                    <td class="text-right"><a href="{{ route('bitacora.mostrar', $registro->id) }}" class="btn btn-secondary btn-sm">Ver</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($registros as $registro)
                        <li class="dr-item">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <p class="font-semibold tabular-nums text-ink">{{ $registro->fecha_hora->format('d/m/Y') }}
                                    <span class="font-medium text-ink-soft">{{ $registro->fecha_hora->format('H:i:s') }}</span></p>
                                <p class="text-xs font-semibold text-ink-soft">N.º {{ $registro->id }}</p>
                            </div>

                            <x-bitacora.accion :accion="$registro->accion" class="max-w-full" />

                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="col-span-2 min-w-0">
                                    <dt class="text-xs text-ink-soft">Usuario</dt>
                                    <dd class="break-words font-medium text-ink">
                                        @if ($registro->usuario)
                                            {{ $registro->usuario->usuario }}
                                        @elseif ($registro->usuario_digitado)
                                            <span class="text-ink-soft">«{{ $registro->usuario_digitado }}» (digitado)</span>
                                        @else
                                            <span class="text-ink-mute">—</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Tabla</dt><dd class="break-words font-medium text-ink">{{ $registro->tabla_afectada ?? '—' }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Registro</dt><dd class="font-medium tabular-nums text-ink">{{ $registro->registro_id ?? '—' }}</dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">IP</dt><dd class="break-all font-medium tabular-nums text-ink">{{ $registro->ip }}</dd></div>
                            </dl>

                            <a href="{{ route('bitacora.mostrar', $registro->id) }}" class="btn btn-secondary btn-sm w-full">Ver detalle</a>
                        </li>
                    @endforeach
                </ul>

                @if ($registros->hasPages())
                    <div class="border-t border-line p-4 sm:px-5">
                        {{ $registros->links() }}
                    </div>
                @endif
            @endif
        </section>
    </x-pagina>
</x-app-layout>
