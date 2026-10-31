{{-- CU19 Definir estudios requeridos por tratamiento (RF-31, solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'tipo_estudio_id' => $volvioFormulario ? old('tipo_estudio_id', '') : '',
        'obligatorio' => $volvioFormulario ? (bool) old('obligatorio') : false,
        'momento_clinico' => $volvioFormulario ? old('momento_clinico', '') : '',
        'observacion' => $volvioFormulario ? old('observacion', '') : '',
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Estudios requeridos: {{ $tratamiento->nombre }}
        </h2>
    </x-slot>

    {{-- nuevo(): formulario vacío para agregar. editar(): formulario con los datos del requerido. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, tipo_estudio_id: '', obligatorio: false, momento_clinico: '', observacion: '' };
                this.mostrarFormulario();
            },
            editar(requerido) {
                this.form = { ...requerido, obligatorio: Boolean(requerido.obligatorio), observacion: requerido.observacion ?? '' };
                this.mostrarFormulario();
            },
            mostrarFormulario() {
                this.abierto = true;
                this.$nextTick(() => this.$refs.formulario.scrollIntoView({ behavior: 'smooth' }));
            },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            {{-- Errores de las acciones por fila --}}
            @if ($errors->any() && ! $volvioFormulario)
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <div class="flex justify-end">
                        <x-primary-button type="button" @click="nuevo()">Agregar estudio</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Tipo de estudio</th>
                                    <th class="px-3 py-2">Obligatorio/Recomendado</th>
                                    <th class="px-3 py-2">Momento clínico</th>
                                    <th class="px-3 py-2">Observación</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($requeridos as $r)
                                    <tr class="align-top">
                                        <td class="px-3 py-3 font-medium">{{ $r->tipoEstudio->nombre }}</td>
                                        <td class="px-3 py-3">{{ $r->obligatorio ? 'Obligatorio' : 'Recomendado' }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($r->momento_clinico) }}</td>
                                        <td class="px-3 py-3">{{ $r->observacion ?? '—' }}</td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-secondary-button
                                                    @click="editar({{ Js::from($r->only(['id', 'tipo_estudio_id', 'obligatorio', 'momento_clinico', 'observacion'])) }})">
                                                    Modificar
                                                </x-secondary-button>

                                                {{-- Quitar (el id viaja oculto) --}}
                                                <form method="POST" action="{{ route('tratamientos.estudios.quitar') }}"
                                                      onsubmit="return confirm({{ Js::from('¿Quitar el estudio requerido '.$r->tipoEstudio->nombre.'?') }});">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $r->id }}">
                                                    <x-danger-button>Quitar</x-danger-button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            Este tratamiento no tiene estudios requeridos.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Formulario de registro / modificación --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="form.id ? 'Modificar estudio requerido' : 'Agregar estudio requerido'"></h2>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" :action="form.id ? {{ Js::from(route('tratamientos.estudios.modificar')) }} : {{ Js::from(route('tratamientos.estudios.guardar')) }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        <input type="hidden" name="tratamiento_id" value="{{ $tratamiento->id }}">
                        {{-- El id viaja oculto solo al modificar --}}
                        <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="tipo_estudio_id" value="Tipo de estudio" />
                                <select id="tipo_estudio_id" name="tipo_estudio_id" class="{{ $claseCampo }}" x-model="form.tipo_estudio_id" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($tipos as $tipo)
                                        <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                    @endforeach
                                </select>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('tipo_estudio_id')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="momento_clinico" value="Momento clínico" />
                                <select id="momento_clinico" name="momento_clinico" class="{{ $claseCampo }}" x-model="form.momento_clinico" required>
                                    <option value="">Seleccione…</option>
                                    @foreach (\App\Models\TratamientoTipoEstudio::MOMENTOS_CLINICOS as $momento)
                                        <option value="{{ $momento }}">{{ ucfirst($momento) }}</option>
                                    @endforeach
                                </select>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('momento_clinico')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                {{-- El oculto envía 0 cuando la casilla no está marcada --}}
                                <input type="hidden" name="obligatorio" value="0">
                                <label for="obligatorio" class="inline-flex items-center gap-2">
                                    <input id="obligatorio" name="obligatorio" type="checkbox" value="1" x-model="form.obligatorio"
                                           class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Obligatorio</span>
                                </label>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('obligatorio')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="observacion" value="Observación (opcional)" />
                                <textarea id="observacion" name="observacion" rows="3" class="{{ $claseCampo }}" x-model="form.observacion"></textarea>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('observacion')" class="mt-2" /> @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Agregar'">Guardar</x-primary-button>
                            <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('tratamientos.mostrar', $tratamiento->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver al tratamiento
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
