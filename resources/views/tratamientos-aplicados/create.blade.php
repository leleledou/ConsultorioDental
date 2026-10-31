{{-- CU28 Registrar tratamiento aplicado (RF-27, RF-38, RF-39). --}}
@php
    $formularioInicial = [
        'tratamiento_id' => old('tratamiento_id', ''),
        'costo_acordado' => old('costo_acordado', ''),
        'sesiones_planificadas' => old('sesiones_planificadas', ''),
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Agregar tratamiento: {{ $plan->paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    {{-- seleccionarTratamiento(): propone costo y sesiones sin ir al servidor; se pueden ajustar. --}}
    <div class="py-12"
         x-data="{
            form: {{ Js::from($formularioInicial) }},
            precios: {{ Js::from($precios) }},
            sesiones: {{ Js::from($sesiones) }},
            faltantes: {{ Js::from($faltantes) }},
            seleccionarTratamiento(tratamientoId) {
                this.form.costo_acordado = this.precios[tratamientoId] ?? '';
                this.form.sesiones_planificadas = this.sesiones[tratamientoId] ?? '';
            },
            get advertencia() {
                const nombres = this.faltantes[this.form.tratamiento_id];

                return nombres ? 'Faltan estudios previos obligatorios: ' + nombres.join(', ') : '';
            },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Errores de negocio (el plan dejó de estar activo) --}}
            @error('operacion')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    {{ $message }}
                </div>
            @enderror

            {{-- RF-39: advertencia, no bloquea el registro --}}
            <div x-show="advertencia" x-cloak class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200"
                 x-text="advertencia"></div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('tratamientos-aplicados.guardar') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="plan_tratamiento_id" value="{{ $plan->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="tratamiento_id" value="Tratamiento" />
                            <select id="tratamiento_id" name="tratamiento_id" class="{{ $claseCampo }}" x-model="form.tratamiento_id"
                                    @change="seleccionarTratamiento($event.target.value)" required>
                                <option value="">Seleccione…</option>
                                @foreach ($tratamientos as $t)
                                    <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('tratamiento_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="pieza_dental_id" value="Pieza dental (opcional)" />
                            <select id="pieza_dental_id" name="pieza_dental_id" class="{{ $claseCampo }}">
                                <option value="">Sin pieza</option>
                                @foreach ($piezas as $pieza)
                                    <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id') == $pieza->id)>{{ $pieza->codigo_fdi }} - {{ $pieza->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('pieza_dental_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="diagnostico_id" value="Diagnóstico" />
                            <select id="diagnostico_id" name="diagnostico_id" class="{{ $claseCampo }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($diagnosticos as $d)
                                    <option value="{{ $d->id }}" @selected(old('diagnostico_id') == $d->id)>{{ $d->codigo }} - {{ $d->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('diagnostico_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fecha_inicio" value="Fecha de inicio" />
                            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio')" required />
                            <x-input-error :messages="$errors->get('fecha_inicio')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="costo_acordado" value="Costo acordado" />
                            <x-text-input id="costo_acordado" name="costo_acordado" type="number" min="0" max="99999999.99" step="0.01" class="mt-1 block w-full" x-model="form.costo_acordado" required />
                            <x-input-error :messages="$errors->get('costo_acordado')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="sesiones_planificadas" value="Sesiones planificadas" />
                            <x-text-input id="sesiones_planificadas" name="sesiones_planificadas" type="number" min="1" max="65535" step="1" class="mt-1 block w-full" x-model="form.sesiones_planificadas" required />
                            <x-input-error :messages="$errors->get('sesiones_planificadas')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="observacion_diagnostica" value="Observación diagnóstica (opcional)" />
                            <textarea id="observacion_diagnostica" name="observacion_diagnostica" rows="3" class="{{ $claseCampo }}">{{ old('observacion_diagnostica') }}</textarea>
                            <x-input-error :messages="$errors->get('observacion_diagnostica')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Registrar tratamiento</x-primary-button>
                        <a href="{{ route('planes.mostrar', $plan->id) }}" class="text-sm text-gray-600 dark:text-gray-400 underline">Volver al plan</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
