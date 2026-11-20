{{-- CU25 Registrar odontograma. Las piezas se cargan solas en estado Sano (RF-15). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Registrar odontograma: {{ $paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del odontograma</h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Se cargan todas las piezas del tipo de dentición en estado «Sano»; después se cambia el estado de cada una.
                            @if ($tieneInicial)
                                El paciente ya tiene un odontograma inicial: corresponde uno de evolución.
                            @endif
                        </p>
                    </header>

                    <form method="POST" action="{{ route('odontogramas.guardar') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="historia_clinica_id" value="{{ $historia->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                            <div>
                                <x-input-label for="fecha_registro" value="Fecha" />
                                <x-text-input id="fecha_registro" name="fecha_registro" type="date" max="{{ today()->toDateString() }}" class="mt-1 block w-full" :value="old('fecha_registro', today()->toDateString())" required />
                                <x-input-error :messages="$errors->get('fecha_registro')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tipo" value="Tipo" />
                                <select id="tipo" name="tipo" class="{{ $claseCampo }}" required>
                                    <option value="inicial" @selected(old('tipo', $tipoPropuesto) === 'inicial') @disabled($tieneInicial)>Inicial</option>
                                    <option value="evolución" @selected(old('tipo', $tipoPropuesto) === 'evolución')>Evolución</option>
                                </select>
                                <x-input-error :messages="$errors->get('tipo')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="tipo_denticion" value="Tipo de dentición" />
                                <select id="tipo_denticion" name="tipo_denticion" class="{{ $claseCampo }}" required>
                                    @foreach (['permanente' => 'Permanente (32 piezas)', 'temporal' => 'Temporal (20 piezas)', 'mixta' => 'Mixta (52 piezas)'] as $valor => $texto)
                                        <option value="{{ $valor }}" @selected(old('tipo_denticion', $denticionPropuesta) === $valor)>{{ $texto }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('tipo_denticion')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="observaciones" value="Observaciones (opcional)" />
                            <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo }}">{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Registrar</x-primary-button>
                            <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
