{{-- CU38 Programar control: lista de controles posteriores, y disparo manual de CU39 Actualizar controles vencidos.
     El paciente se muestra derivado del tratamiento aplicado (la tabla no tiene paciente_id). --}}
@php
    $cifras = [
        ['estado' => 'pendiente', 'texto' => 'Pendientes', 'clase' => 'bg-blue-50 ring-blue-200 text-blue-900'],
        ['estado' => 'vencido', 'texto' => 'Vencidos', 'clase' => 'bg-red-50 ring-red-200 text-red-900'],
        ['estado' => 'cumplido', 'texto' => 'Cumplidos', 'clase' => 'bg-green-50 ring-green-200 text-green-900'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Controles y seguimiento"
                           subtitulo="Controles posteriores de los tratamientos: los pendientes primero, por fecha prevista." />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        {{-- Contadores (enlazan al filtro por estado) --}}
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach ($cifras as $c)
                <a href="{{ route('controles.listar', array_filter(['estado' => $c['estado'], 'odontologo_id' => $odontologoId])) }}"
                   @class(['rounded-xl p-4 ring-1 transition hover:shadow-card', $c['clase'], 'ring-2' => $estado === $c['estado']])>
                    <dt class="text-xs font-semibold uppercase tracking-wide">{{ $c['texto'] }}</dt>
                    <dd class="mt-1 font-display text-3xl font-bold tabular-nums">{{ $contadores[$c['estado']] ?? 0 }}</dd>
                </a>
            @endforeach
        </dl>

        {{-- CU39, flujo alternativo: el Temporizador lo hace cada día a las 00:05 --}}
        <section class="dr-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-labelledby="vencer-controles">
            <div class="min-w-0">
                <h3 id="vencer-controles" class="font-bold text-ink">Actualizar controles vencidos</h3>
                <p class="mt-0.5 text-sm text-ink-soft">El sistema lo hace solo todos los días a las 00:05. Con este botón se ejecuta ahora: los pendientes con fecha prevista pasada quedan como vencidos.</p>
            </div>
            <form method="POST" action="{{ route('controles.vencer') }}" class="shrink-0">
                @csrf
                <x-secondary-button type="submit" class="w-full sm:w-auto"><x-icon name="refresh" class="h-4 w-4" /> Actualizar controles vencidos</x-secondary-button>
            </form>
        </section>

        <section class="dr-card overflow-hidden" aria-labelledby="lista-controles">
            <header class="flex flex-col gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6 lg:flex-row lg:items-end lg:justify-between">
                <h3 id="lista-controles" class="text-lg font-bold text-ink">Controles <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $controles->total() }})</span></h3>

                {{-- Filtros (GET) --}}
                <form method="GET" action="{{ route('controles.listar') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] sm:items-end" role="search">
                    <div>
                        <label for="filtro_estado" class="block text-xs font-semibold uppercase tracking-wide text-ink-soft">Estado</label>
                        <select id="filtro_estado" name="estado" class="dr-input mt-1">
                            <option value="">Todos</option>
                            @foreach (['pendiente' => 'Pendiente', 'vencido' => 'Vencido', 'cumplido' => 'Cumplido'] as $valor => $texto)
                                <option value="{{ $valor }}" @selected($estado === $valor)>{{ $texto }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="filtro_odontologo" class="block text-xs font-semibold uppercase tracking-wide text-ink-soft">Odontólogo</label>
                        <select id="filtro_odontologo" name="odontologo_id" class="dr-input mt-1">
                            <option value="">Todos</option>
                            @foreach ($odontologos as $o)
                                <option value="{{ $o->id }}" @selected((int) $odontologoId === $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button class="btn btn-secondary btn-sm"><x-icon name="search" class="h-4 w-4" /> Filtrar</button>
                        @if ($estado || $odontologoId)
                            <a href="{{ route('controles.listar') }}" class="btn btn-secondary btn-sm">Quitar</a>
                        @endif
                    </div>
                </form>
            </header>

            @if ($controles->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="calendar" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">{{ $estado || $odontologoId ? 'No hay controles con ese filtro.' : 'No hay controles programados.' }}</p>
                    <p class="mt-1 text-sm text-ink-soft">Los controles se programan desde la ficha de cada tratamiento aplicado.</p>
                </div>
            @else
                {{-- Cada fila lleva su formulario de cumplir: una sola lista en todos los anchos --}}
                <ul class="divide-y divide-line">
                    @foreach ($controles as $control)
                        @php $ta = $control->tratamientoAplicado; @endphp
                        <li @class(['px-5 py-4 sm:px-6', 'bg-red-50/40' => $control->estado === 'vencido'])>
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('pacientes.mostrar', $ta->plan->paciente_id) }}" class="break-words font-semibold text-brand-700 hover:text-brand-900 hover:underline">{{ $ta->plan->paciente->nombreCompleto() }}</a>
                                        <x-controles.estado :estado="$control->estado" />
                                    </div>
                                    <p class="text-sm text-ink-soft">
                                        <a href="{{ route('tratamientos-aplicados.mostrar', $ta->id) }}#controles" class="text-ink hover:underline">{{ $ta->tratamiento->nombre }}</a>
                                        · {{ $ta->odontologo->nombres }} {{ $ta->odontologo->apellidos }}
                                    </p>
                                    <p class="text-sm text-ink">
                                        <span class="font-semibold tabular-nums">Previsto el {{ $control->fecha_prevista->format('d/m/Y') }}</span>
                                        @if ($control->motivo) · {{ $control->motivo }} @endif
                                        @if ($control->fecha_cumplimiento)
                                            · <span class="text-green-800">cumplido el <span class="tabular-nums">{{ $control->fecha_cumplimiento->format('d/m/Y') }}</span></span>
                                        @endif
                                    </p>
                                    @if ($control->observaciones)
                                        <p class="whitespace-pre-line break-words text-sm text-ink-soft">{{ $control->observaciones }}</p>
                                    @endif
                                </div>
                                <x-controles.cumplir :control="$control" class="shrink-0 lg:max-w-md" />
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($controles->hasPages())
                    <div class="border-t border-line px-5 py-4 sm:px-6">{{ $controles->links() }}</div>
                @endif
            @endif
        </section>
    </x-pagina>
</x-app-layout>
