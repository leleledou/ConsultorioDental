{{-- CU10 Programar cita (RF-66 a RF-69). Los dos roles. --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';

    // Paciente elegido: el anterior (si volvió con errores) o el único resultado de la búsqueda.
    $pacienteElegido = old('paciente_id', $buscar && $pacientes->count() === 1 ? $pacientes->first()->id : '');

    // Franjas libres más cercanas (flujo A2), si la franja pedida estaba ocupada.
    $franjasLibres = session('franjas_libres', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Programar cita
        </h2>
    </x-slot>

    {{-- RF-67: al elegir el tratamiento se propone su duración. RF-68: la hora de fin se calcula en pantalla. --}}
    <div class="py-12"
         x-data="{
            duracion: {{ Js::from(old('duracion_minutos', '')) }},
            fecha: {{ Js::from(old('fecha', $fecha)) }},
            hora: {{ Js::from(old('hora_inicio', $horaInicio)) }},
            elegirTratamiento(evento) {
                const propuesta = evento.target.selectedOptions[0]?.dataset.duracion;
                if (propuesta) this.duracion = propuesta;
            },
            horaFin() {
                const minutos = parseInt(this.duracion, 10);
                if (! this.hora || ! minutos || minutos < 1) return '—';
                const [h, m] = this.hora.split(':').map(Number);
                const total = h * 60 + m + minutos;
                if (total >= 24 * 60) return '—';
                return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
            },
         }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200 space-y-3">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                    {{-- Flujo A2: franjas libres más cercanas de ese día --}}
                    @if ($franjasLibres)
                        <div>
                            <p>Franjas libres más cercanas ese día:</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($franjasLibres as $franja)
                                    <button type="button" @click="hora = {{ Js::from($franja['sugerida']) }}"
                                            class="rounded-md border border-red-300 dark:border-red-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        Libre de {{ $franja['inicio'] }} a {{ $franja['fin'] }} · usar {{ $franja['sugerida'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Paso 1: buscar al paciente (misma búsqueda de CU09, solo pacientes activos) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">1. Buscar paciente</h3>

                <form method="GET" action="{{ route('citas.crear') }}" class="mt-4 flex flex-col sm:flex-row gap-2 sm:max-w-xl">
                    {{-- Se conservan la fecha y la hora ya elegidas --}}
                    <input type="hidden" name="fecha" :value="fecha">
                    <input type="hidden" name="hora_inicio" :value="hora" :disabled="! hora">
                    <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                  placeholder="Buscar por CI, nombres o apellidos" />
                    <div class="flex gap-2">
                        <x-primary-button>Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('citas.crear', ['fecha' => $fecha]) }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                        @endif
                    </div>
                </form>

                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    @if ($pacientes->isEmpty())
                        No se encontraron pacientes activos con ese criterio de búsqueda.
                    @else
                        {{ $pacientes->count() }} paciente(s) activo(s) en la lista{{ $buscar ? ' para «'.$buscar.'»' : '' }}.
                    @endif
                </p>
            </div>

            {{-- Pasos 2 a 4: datos de la cita --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">2. Datos de la cita</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Horario de atención: {{ $apertura }} a {{ $cierre }}. Hay un solo sillón: no puede haber dos citas programadas a la misma hora.
                </p>

                <form method="POST" action="{{ route('citas.guardar') }}" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <x-input-label for="paciente_id" value="Paciente" />
                            <select id="paciente_id" name="paciente_id" class="{{ $claseCampo }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($pacientes as $p)
                                    <option value="{{ $p->id }}" @selected((string) $pacienteElegido === (string) $p->id)>
                                        {{ $p->apellidos }}, {{ $p->nombres }} — CI {{ $p->ci }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('paciente_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="odontologo_id" value="Odontólogo responsable" />
                            <select id="odontologo_id" name="odontologo_id" class="{{ $claseCampo }}" required>
                                @foreach ($odontologos as $o)
                                    <option value="{{ $o->id }}" @selected((string) old('odontologo_id', Auth::id()) === (string) $o->id)>
                                        {{ $o->nombres }} {{ $o->apellidos }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('odontologo_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="tratamiento_id" value="Tratamiento" />
                            <select id="tratamiento_id" name="tratamiento_id" class="{{ $claseCampo }}" @change="elegirTratamiento($event)" required>
                                <option value="">Seleccione…</option>
                                @foreach ($tratamientos as $t)
                                    {{-- data-duracion: duración que se propone (RF-67 / RN-08) --}}
                                    <option value="{{ $t->id }}" data-duracion="{{ $t->duracion_estimada_minutos }}" @selected((string) old('tratamiento_id') === (string) $t->id)>
                                        {{ $t->nombre }} ({{ $t->especialidad->nombre }}, {{ $t->duracion_estimada_minutos }} min)
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('tratamiento_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fecha" value="Fecha" />
                            <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full" x-model="fecha" min="{{ today()->toDateString() }}" required />
                            <x-input-error :messages="$errors->get('fecha')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="hora_inicio" value="Hora de inicio" />
                            <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full" x-model="hora" min="{{ $apertura }}" max="{{ $cierre }}" required />
                            <x-input-error :messages="$errors->get('hora_inicio')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="duracion_minutos" value="Duración (minutos)" />
                            <x-text-input id="duracion_minutos" name="duracion_minutos" type="number" min="1" max="600" step="1" class="mt-1 block w-full" x-model="duracion" required />
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Se propone la duración estimada del tratamiento; puede cambiarla.</p>
                            <x-input-error :messages="$errors->get('duracion_minutos')" class="mt-2" />
                        </div>

                        <div>
                            {{-- RF-68 / RN-09: solo se muestra; la base de datos calcula la hora de fin al guardar --}}
                            <x-input-label for="hora_fin" value="Hora de fin (calculada)" />
                            <x-text-input id="hora_fin" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" x-bind:value="horaFin()" readonly tabindex="-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="motivo" value="Motivo (opcional; si lo deja vacío se usa el nombre del tratamiento)" />
                            <x-text-input id="motivo" name="motivo" type="text" class="mt-1 block w-full" :value="old('motivo')" maxlength="150" />
                            <x-input-error :messages="$errors->get('motivo')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <x-primary-button>Programar cita</x-primary-button>
                        <a href="{{ route('agenda') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">Volver a la agenda</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
