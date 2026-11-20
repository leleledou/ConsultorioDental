{{-- CU38 Programar control (RF-79): control posterior de un tratamiento aplicado. --}}
@php
    $paciente = $tratamiento->plan->paciente;
    $cancelado = $tratamiento->estado === 'cancelado';
    $clase = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');
    // Primera fecha válida: el día siguiente al inicio del tratamiento.
    $minima = $tratamiento->fecha_inicio->copy()->addDay()->toDateString();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Programar control: '.$tratamiento->tratamiento->nombre"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="route('tratamientos-aplicados.mostrar', $tratamiento->id)" volver-texto="Tratamiento aplicado" />
    </x-slot>

    <x-pagina ancho="4xl">
        @error('operacion')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        <section class="dr-card flex flex-col gap-1 p-4 text-sm sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-label="Tratamiento">
            <p class="min-w-0 text-ink-soft">
                <span class="font-semibold text-ink">{{ $tratamiento->tratamiento->nombre }}</span> · iniciado el
                <span class="font-semibold text-ink">{{ $tratamiento->fecha_inicio->format('d/m/Y') }}</span> ·
                {{ $tratamiento->controles->count() }} {{ $tratamiento->controles->count() === 1 ? 'control programado' : 'controles programados' }}
            </p>
            <x-estado-clinico :estado="$tratamiento->estado" class="self-start" />
        </section>

        @if ($cancelado)
            <x-alerta tipo="error">No se pueden programar controles en un tratamiento cancelado.</x-alerta>
        @else
            <form method="POST" action="{{ route('controles.guardar') }}" class="dr-card overflow-hidden">
                @csrf
                <input type="hidden" name="tratamiento_aplicado_id" value="{{ $tratamiento->id }}">

                <div class="space-y-8 p-5 sm:p-6">
                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Control</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="fecha_prevista" label="Fecha prevista" :requerido="true"
                                     :ayuda="'Posterior al inicio del tratamiento ('.$tratamiento->fecha_inicio->format('d/m/Y').').'" :errores="$errors->get('fecha_prevista')">
                                <x-text-input id="fecha_prevista" name="fecha_prevista" type="date" min="{{ $minima }}"
                                              :value="old('fecha_prevista')" required :error="$errors->has('fecha_prevista')" />
                            </x-campo>

                            <x-campo name="motivo" label="Motivo (opcional)" ayuda="Hasta 150 caracteres." :errores="$errors->get('motivo')">
                                <x-text-input id="motivo" name="motivo" type="text" maxlength="150" placeholder="Ej.: Revisión de oseointegración" :value="old('motivo')" :error="$errors->has('motivo')" />
                            </x-campo>

                            <x-campo name="observaciones" label="Observaciones (opcional)" class="sm:col-span-2" :errores="$errors->get('observaciones')">
                                <textarea id="observaciones" name="observaciones" rows="3" class="{{ $clase('observaciones') }}">{{ old('observaciones') }}</textarea>
                            </x-campo>
                        </div>
                    </fieldset>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <a href="{{ route('tratamientos-aplicados.mostrar', $tratamiento->id) }}" class="btn btn-secondary">Cancelar</a>
                        <x-primary-button>Programar control</x-primary-button>
                    </div>
                </div>
            </form>
        @endif
    </x-pagina>
</x-app-layout>
