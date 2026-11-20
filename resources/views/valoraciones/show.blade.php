{{-- CU33 Registrar valoración para implante: ficha de la valoración, con modificar en un modal. --}}
@php
    $paciente = $valoracion->paciente;
    $abrirModificar = $errors->any() && old('formulario') === 'modificar';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Valoración para implante: pieza '.$valoracion->pieza->codigo_fdi"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="route('valoraciones.listar', $paciente->id)" volver-texto="Valoraciones del paciente" />
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

        {{-- Resumen --}}
        <section class="dr-card flex flex-col gap-4 p-5 sm:p-6" aria-label="Resumen de la valoración">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="break-words font-display text-xl font-bold text-ink">Pieza {{ $valoracion->pieza->codigo_fdi }} · {{ $valoracion->pieza->nombre }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">Valorada el {{ $valoracion->fecha_valoracion->format('d/m/Y') }} por {{ $valoracion->odontologo->nombres }} {{ $valoracion->odontologo->apellidos }}</p>
                </div>
                <x-valoraciones.resultado :resultado="$valoracion->resultado" class="self-start" />
            </div>

            <div class="flex flex-wrap gap-2 border-t border-line pt-4">
                <x-valoraciones.hueso :estado="$valoracion->estado_hueso" />
                <x-insignia :tono="$valoracion->requiere_injerto ? 'aviso' : 'neutro'">{{ $valoracion->requiere_injerto ? 'Requiere injerto' : 'Sin injerto' }}</x-insignia>
            </div>
        </section>

        {{-- Datos --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-valoracion">
            <h3 id="datos-valoracion" class="text-lg font-bold text-ink">Datos de la valoración</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Paciente">
                    <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $paciente->nombreCompleto() }}</a>
                </x-dato>
                <x-dato etiqueta="Estudio que la respalda">
                    @if ($valoracion->estudio)
                        <a href="{{ route('estudios.mostrar', $valoracion->estudio_diagnostico_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $valoracion->estudio->tipoEstudio->nombre }} ({{ $valoracion->estudio->fecha_estudio->format('d/m/Y') }})</a>
                    @else
                        —
                    @endif
                </x-dato>
                <x-dato etiqueta="Tratamiento derivado">
                    @if ($valoracion->tratamientoAplicado)
                        <a href="{{ route('tratamientos-aplicados.mostrar', $valoracion->tratamiento_aplicado_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $valoracion->tratamientoAplicado->tratamiento->nombre }}</a>
                    @else
                        —
                    @endif
                </x-dato>
                <x-dato etiqueta="Observaciones clínicas" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $valoracion->observaciones_clinicas ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('valoraciones.listar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a las valoraciones
            </a>
            <x-secondary-button x-data @click="$dispatch('open-modal', 'modificar-valoracion')"><x-icon name="edit" class="h-4 w-4" /> Modificar</x-secondary-button>
        </div>

        {{-- Modificar valoración --}}
        <x-modal name="modificar-valoracion" :show="$abrirModificar" focusable>
            <div class="p-6">
                <header class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="edit" class="h-5 w-5" /></span>
                    <h2 class="text-lg font-bold text-ink">Modificar valoración</h2>
                </header>

                <x-valoraciones.formulario :paciente="$paciente" :piezas="$piezas" :estudios="$estudios" :tratamientos="$tratamientos" :valoracion="$valoracion" />
            </div>
        </x-modal>
    </x-pagina>
</x-app-layout>
