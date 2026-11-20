{{-- CU33 Registrar valoración para implante: valoraciones de un paciente, con alta en un modal. --}}
@php
    $abrirAlta = $errors->any() && old('formulario') === 'crear';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Valoraciones para implante: '.$paciente->nombreCompleto()"
                           subtitulo="Estado del hueso, necesidad de injerto y resultado de cada pieza valorada."
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

        <section class="dr-card overflow-hidden" aria-labelledby="lista-valoraciones">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="lista-valoraciones" class="text-lg font-bold text-ink">Valoraciones <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $valoraciones->count() }})</span></h3>
                <x-primary-button type="button" class="btn-sm self-start sm:self-auto" x-data @click="$dispatch('open-modal', 'nueva-valoracion')">
                    <x-icon name="plus" class="h-4 w-4" /> Registrar valoración
                </x-primary-button>
            </header>

            @if ($valoraciones->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">El paciente no tiene valoraciones para implante.</p>
                </div>
            @else
                {{-- Tabla (desde 1280 px: 7 columnas con acción) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Valoraciones para implante de {{ $paciente->nombreCompleto() }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Pieza</th>
                                <th scope="col">Hueso</th>
                                <th scope="col">Injerto</th>
                                <th scope="col">Resultado</th>
                                <th scope="col">Odontólogo</th>
                                <th scope="col"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($valoraciones as $v)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap font-semibold tabular-nums text-ink">{{ $v->fecha_valoracion->format('d/m/Y') }}</td>
                                    <td class="text-ink"><span class="font-semibold tabular-nums">{{ $v->pieza->codigo_fdi }}</span> <span class="text-ink-soft">· {{ $v->pieza->nombre }}</span></td>
                                    <td><x-valoraciones.hueso :estado="$v->estado_hueso" /></td>
                                    <td class="text-ink-soft">{{ $v->requiere_injerto ? 'Sí' : 'No' }}</td>
                                    <td><x-valoraciones.resultado :resultado="$v->resultado" /></td>
                                    <td class="text-ink-soft">{{ $v->odontologo->nombres }} {{ $v->odontologo->apellidos }}</td>
                                    <td class="text-right"><a href="{{ route('valoraciones.mostrar', $v->id) }}" class="btn btn-secondary btn-sm">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas: 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($valoraciones as $v)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 break-words font-display text-base font-bold text-ink">Pieza {{ $v->pieza->codigo_fdi }}</p>
                                <x-valoraciones.resultado :resultado="$v->resultado" />
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Fecha</dt><dd class="font-medium tabular-nums text-ink">{{ $v->fecha_valoracion->format('d/m/Y') }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Injerto</dt><dd class="font-medium text-ink">{{ $v->requiere_injerto ? 'Sí' : 'No' }}</dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Hueso</dt><dd class="mt-0.5"><x-valoraciones.hueso :estado="$v->estado_hueso" /></dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Odontólogo</dt><dd class="break-words font-medium text-ink">{{ $v->odontologo->nombres }} {{ $v->odontologo->apellidos }}</dd></div>
                            </dl>
                            <a href="{{ route('valoraciones.mostrar', $v->id) }}" class="btn btn-secondary btn-sm w-full">Abrir valoración</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
            <a href="{{ route('estudios.listar', $paciente->id) }}" class="btn btn-secondary">Estudios diagnósticos</a>
        </div>

        {{-- Alta de valoración --}}
        <x-modal name="nueva-valoracion" :show="$abrirAlta" focusable>
            <div class="p-6">
                <header class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="plus" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Registrar valoración para implante</h2>
                        <p class="mt-0.5 text-sm text-ink-soft">{{ $paciente->nombreCompleto() }}</p>
                    </div>
                </header>

                <x-valoraciones.formulario :paciente="$paciente" :piezas="$piezas" :estudios="$estudios" :tratamientos="$tratamientos" />
            </div>
        </x-modal>
    </x-pagina>
</x-app-layout>
