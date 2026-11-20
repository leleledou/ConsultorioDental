{{-- CU32 Gestionar estudios diagnósticos: estudios de un paciente, con filtro por tipo y alta en un modal. --}}
@php
    // El modal de alta se abre si volvió con errores o si se llegó desde la ficha de un tratamiento (RF-59).
    $abrirAlta = ($errors->any() && old('formulario') === 'crear') || $preseleccion['abrir'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Estudios diagnósticos: '.$paciente->nombreCompleto()"
                           subtitulo="Radiografías, tomografías y demás estudios, con su archivo o el enlace del centro radiológico."
                           :volver="route('pacientes.mostrar', $paciente->id)" volver-texto="Paciente" />
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

        <section class="dr-card overflow-hidden" aria-labelledby="lista-estudios">
            <header class="flex flex-col gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h3 id="lista-estudios" class="text-lg font-bold text-ink">Estudios <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $estudios->count() }})</span></h3>
                    @if ($tipoFiltro)
                        <p class="mt-0.5 text-sm text-ink-soft">Filtrado por tipo de estudio.</p>
                    @endif
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    {{-- Filtro por tipo de estudio (GET) --}}
                    <form method="GET" action="{{ route('estudios.listar', $paciente->id) }}" class="flex items-end gap-2" role="search">
                        <div class="min-w-0 flex-1 sm:w-56">
                            <label for="filtro_tipo" class="block text-xs font-semibold uppercase tracking-wide text-ink-soft">Tipo de estudio</label>
                            <select id="filtro_tipo" name="tipo_estudio_id" class="dr-input mt-1" onchange="this.form.submit()">
                                <option value="">Todos</option>
                                @foreach ($tipos as $tipo)
                                    <option value="{{ $tipo->id }}" @selected($tipoFiltro == $tipo->id)>{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <noscript><button class="btn btn-secondary btn-sm">Filtrar</button></noscript>
                        @if ($tipoFiltro)
                            <a href="{{ route('estudios.listar', $paciente->id) }}" class="btn btn-secondary btn-sm">Quitar filtro</a>
                        @endif
                    </form>

                    <x-primary-button type="button" class="btn-sm" x-data @click="$dispatch('open-modal', 'nuevo-estudio')">
                        <x-icon name="plus" class="h-4 w-4" /> Registrar estudio
                    </x-primary-button>
                </div>
            </header>

            @if ($estudios->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="search" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">
                        {{ $tipoFiltro ? 'El paciente no tiene estudios de ese tipo.' : 'El paciente no tiene estudios diagnósticos registrados.' }}
                    </p>
                </div>
            @else
                {{-- Tabla (desde 1280 px: 7 columnas con acción) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Estudios diagnósticos de {{ $paciente->nombreCompleto() }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Tipo de estudio</th>
                                <th scope="col">Pieza</th>
                                <th scope="col">Tratamiento</th>
                                <th scope="col">Odontólogo</th>
                                <th scope="col">Resultado</th>
                                <th scope="col"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estudios as $e)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap font-semibold tabular-nums text-ink">{{ $e->fecha_estudio->format('d/m/Y') }}</td>
                                    <td class="text-ink">{{ $e->tipoEstudio->nombre }}</td>
                                    <td class="tabular-nums text-ink-soft">{{ $e->pieza?->codigo_fdi ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $e->tratamientoAplicado?->tratamiento->nombre ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $e->odontologo->nombres }} {{ $e->odontologo->apellidos }}</td>
                                    <td><x-estudios.formato :ruta="$e->ruta_archivo" /></td>
                                    <td class="text-right"><a href="{{ route('estudios.mostrar', $e->id) }}" class="btn btn-secondary btn-sm">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas: 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($estudios as $e)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 break-words font-display text-base font-bold text-ink">{{ $e->tipoEstudio->nombre }}</p>
                                <x-estudios.formato :ruta="$e->ruta_archivo" />
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Fecha</dt><dd class="font-medium tabular-nums text-ink">{{ $e->fecha_estudio->format('d/m/Y') }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Pieza</dt><dd class="font-medium tabular-nums text-ink">{{ $e->pieza?->codigo_fdi ?? '—' }}</dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Tratamiento</dt><dd class="break-words font-medium text-ink">{{ $e->tratamientoAplicado?->tratamiento->nombre ?? '—' }}</dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Odontólogo</dt><dd class="break-words font-medium text-ink">{{ $e->odontologo->nombres }} {{ $e->odontologo->apellidos }}</dd></div>
                            </dl>
                            <a href="{{ route('estudios.mostrar', $e->id) }}" class="btn btn-secondary btn-sm w-full">Abrir estudio</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
            <a href="{{ route('valoraciones.listar', $paciente->id) }}" class="btn btn-secondary">Valoraciones para implante</a>
        </div>

        {{-- Alta de estudio --}}
        <x-modal name="nuevo-estudio" :show="$abrirAlta" focusable>
            <div class="p-6">
                <header class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="plus" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Registrar estudio diagnóstico</h2>
                        <p class="mt-0.5 text-sm text-ink-soft">{{ $paciente->nombreCompleto() }}</p>
                    </div>
                </header>

                <x-estudios.formulario :paciente="$paciente" :tipos="$tipos" :piezas="$piezas" :tratamientos="$tratamientos" :preseleccion="$preseleccion" />
            </div>
        </x-modal>
    </x-pagina>
</x-app-layout>
