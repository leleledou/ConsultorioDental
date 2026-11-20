{{-- CU25 Registrar odontograma. Las piezas se cargan solas en estado Sano (RF-15). --}}
@php
    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Registrar odontograma: '.$paciente->nombreCompleto()"
                           subtitulo="Se cargan todas las piezas del tipo de dentición en estado «Sano»; después se cambia el estado de cada una."
                           :volver="route('odontogramas.listar', $paciente->id)" volver-texto="Odontogramas" />
    </x-slot>

    <x-pagina ancho="4xl">
        {{-- Errores de negocio (inicial repetido, fecha repetida) --}}
        @error('operacion')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        @if ($tieneInicial)
            <x-alerta tipo="aviso">El paciente ya tiene un odontograma inicial: corresponde registrar uno de evolución.</x-alerta>
        @endif

        <form method="POST" action="{{ route('odontogramas.guardar') }}" class="dr-card overflow-hidden">
            @csrf
            <input type="hidden" name="historia_clinica_id" value="{{ $historia->id }}">

            <div class="space-y-8 p-5 sm:p-6">
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos del odontograma</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <x-campo name="fecha_registro" label="Fecha" :requerido="true" ayuda="No puede ser futura." :errores="$errors->get('fecha_registro')">
                            <x-text-input id="fecha_registro" name="fecha_registro" type="date" max="{{ today()->toDateString() }}" :value="old('fecha_registro', today()->toDateString())" required :error="$errors->has('fecha_registro')" />
                        </x-campo>

                        <x-campo name="tipo" label="Tipo" :requerido="true" :errores="$errors->get('tipo')">
                            <select id="tipo" name="tipo" class="{{ $claseCampo('tipo') }}" required>
                                <option value="inicial" @selected(old('tipo', $tipoPropuesto) === 'inicial') @disabled($tieneInicial)>Inicial</option>
                                <option value="evolución" @selected(old('tipo', $tipoPropuesto) === 'evolución')>Evolución</option>
                            </select>
                        </x-campo>

                        <x-campo name="tipo_denticion" label="Tipo de dentición" :requerido="true" ayuda="Propuesto según la edad del paciente." :errores="$errors->get('tipo_denticion')">
                            <select id="tipo_denticion" name="tipo_denticion" class="{{ $claseCampo('tipo_denticion') }}" required>
                                @foreach (['permanente' => 'Permanente (32 piezas)', 'temporal' => 'Temporal (20 piezas)', 'mixta' => 'Mixta (52 piezas)'] as $valor => $texto)
                                    <option value="{{ $valor }}" @selected(old('tipo_denticion', $denticionPropuesta) === $valor)>{{ $texto }}</option>
                                @endforeach
                            </select>
                        </x-campo>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Observaciones</legend>
                    <x-campo name="observaciones" label="Observaciones (opcional)" class="mt-4" :errores="$errors->get('observaciones')">
                        <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo('observaciones') }}">{{ old('observaciones') }}</textarea>
                    </x-campo>
                </fieldset>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="btn btn-secondary">Cancelar</a>
                    <x-primary-button>Registrar odontograma</x-primary-button>
                </div>
            </div>
        </form>
    </x-pagina>
</x-app-layout>
