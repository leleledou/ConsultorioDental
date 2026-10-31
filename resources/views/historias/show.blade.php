{{-- CU23 Gestionar historia clínica. No existe eliminar historia clínica (RF-10). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Historia clínica: {{ $historia->paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

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
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos de la historia</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $historia->paciente->nombreCompleto() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">CI</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $historia->paciente->ci }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de apertura</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $historia->fecha_apertura->format('d/m/Y') }}</dd>
                    </div>
                </dl>

                {{-- Hábitos y observaciones generales (el id viaja oculto) --}}
                <form method="POST" action="{{ route('historias.modificar') }}" class="mt-6 space-y-6">
                    @csrf
                    <input type="hidden" name="id" value="{{ $historia->id }}">

                    <div>
                        <x-input-label for="habitos" value="Hábitos (opcional)" />
                        <textarea id="habitos" name="habitos" rows="3" class="{{ $claseCampo }}">{{ old('habitos', $historia->habitos) }}</textarea>
                    </div>

                    <div>
                        <x-input-label for="observaciones_generales" value="Observaciones generales (opcional)" />
                        <textarea id="observaciones_generales" name="observaciones_generales" rows="3" class="{{ $claseCampo }}">{{ old('observaciones_generales', $historia->observaciones_generales) }}</textarea>
                    </div>

                    <x-primary-button>Guardar</x-primary-button>
                </form>
            </div>

            {{-- Antecedentes registrados en la historia --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Antecedentes médicos</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Antecedente</th>
                                    <th class="px-3 py-2">Observación</th>
                                    <th class="px-3 py-2">Fecha de registro</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($historia->antecedentes as $a)
                                    {{-- editando: muestra el formulario de Modificar observación de la fila --}}
                                    <tr class="align-top" x-data="{ editando: false }">
                                        <td class="px-3 py-3 font-medium">{{ $a->nombre }}</td>
                                        <td class="px-3 py-3">
                                            <span x-show="! editando">{{ $a->pivot->observacion ?? '—' }}</span>
                                            <form x-show="editando" x-cloak method="POST" action="{{ route('historias.antecedentes.modificar') }}" class="space-y-2">
                                                @csrf
                                                <input type="hidden" name="id" value="{{ $a->pivot->id }}">
                                                <textarea name="observacion" rows="2" class="{{ $claseCampo }}">{{ $a->pivot->observacion }}</textarea>
                                                <div class="flex items-center gap-2">
                                                    <x-primary-button>Guardar</x-primary-button>
                                                    <x-secondary-button @click="editando = false">Cancelar</x-secondary-button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ \Carbon\Carbon::parse($a->pivot->fecha_registro)->format('d/m/Y') }}</td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-secondary-button @click="editando = true">Modificar observación</x-secondary-button>

                                                {{-- Quitar (el id viaja oculto) --}}
                                                <form method="POST" action="{{ route('historias.antecedentes.quitar') }}"
                                                      onsubmit="return confirm({{ Js::from('¿Quitar el antecedente '.$a->nombre.' de la historia clínica?') }});">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $a->pivot->id }}">
                                                    <x-danger-button>Quitar</x-danger-button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            La historia no tiene antecedentes registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Asignar antecedente --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Asignar antecedente</h2>
                    </header>

                    <form method="POST" action="{{ route('historias.antecedentes.asignar') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="historia_clinica_id" value="{{ $historia->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="antecedente_medico_id" value="Antecedente" />
                                <select id="antecedente_medico_id" name="antecedente_medico_id" class="{{ $claseCampo }}" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($catalogo as $antecedente)
                                        <option value="{{ $antecedente->id }}" @selected(old('antecedente_medico_id') == $antecedente->id)>{{ $antecedente->nombre }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('antecedente_medico_id')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="observacion" value="Observación (opcional)" />
                                <textarea id="observacion" name="observacion" rows="2" class="{{ $claseCampo }}">{{ old('observacion') }}</textarea>
                                <x-input-error :messages="$errors->get('observacion')" class="mt-2" />
                            </div>
                        </div>

                        <x-primary-button>Asignar</x-primary-button>
                    </form>
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('pacientes.mostrar', $historia->paciente_id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver al paciente
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
