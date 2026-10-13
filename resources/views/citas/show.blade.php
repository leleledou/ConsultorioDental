{{-- Ficha de una cita (CU10). Desde aquí se reprograma, cancela (CU11) o cambia el estado (CU12). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';

    // Cada modal envía un campo oculto "formulario"; si vuelve con errores, se vuelve a abrir
    // ese modal y los errores se muestran dentro de él.
    $volvioReprogramar = old('formulario') === 'reprogramar' && $errors->any();
    $volvioCancelar = old('formulario') === 'cancelar' && $errors->any();

    // CU11 flujo A2: franjas libres más cercanas, si el nuevo horario estaba ocupado.
    $franjasLibres = session('franjas_libres', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Cita N.º {{ $cita->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            @if ($errors->any() && ! $volvioReprogramar && ! $volvioCancelar)
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Datos de la cita --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos de la cita</h3>
                    <x-estado-cita :nombre="$cita->estado->nombre" />
                </div>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium">
                            <a href="{{ route('pacientes.mostrar', $cita->paciente->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $cita->paciente->nombreCompleto() }}
                            </a>
                            <span class="text-gray-500 dark:text-gray-400">· CI {{ $cita->paciente->ci }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Odontólogo responsable</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->odontologo->nombres }} {{ $cita->odontologo->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Horario</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->horaInicioCorta() }} – {{ $cita->horaFinCorta() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Duración</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->duracion_minutos }} minutos</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de registro</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->fecha_registro->format('d/m/Y') }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Motivo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->motivo ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Observaciones</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $cita->observaciones ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- CU12 Actualizar estado (RF-71): solo para una cita programada --}}
            @if ($cita->estaProgramada())
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Actualizar estado</h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        «Atendida» y «No asistida» solo se pueden marcar el día de la cita o después.
                    </p>

                    <form method="POST" action="{{ route('citas.estado') }}" class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 sm:items-end">
                        @csrf
                        <input type="hidden" name="id" value="{{ $cita->id }}">
                        <div>
                            <x-input-label for="estado_cita_id" value="Nuevo estado" />
                            <select id="estado_cita_id" name="estado_cita_id" class="{{ $claseCampo }}" required>
                                <option value="">Elegir…</option>
                                @foreach ($estadosDestino as $e)
                                    <option value="{{ $e->id }}" @disabled($cita->fecha->gt(today()) && $e->nombre !== 'cancelada')>{{ ucfirst($e->nombre) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label for="observacion" value="Observación (opcional)" />
                            <x-text-input id="observacion" name="observacion" type="text" class="mt-1 block w-full" maxlength="255" />
                        </div>
                        <div>
                            <x-primary-button>Guardar estado</x-primary-button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a la agenda
                </a>

                {{-- CU11: solo una cita programada se puede reprogramar o cancelar --}}
                @if ($cita->estaProgramada())
                    <x-secondary-button x-data @click="$dispatch('open-modal', 'reprogramar')">Reprogramar</x-secondary-button>
                    <x-danger-button type="button" x-data @click="$dispatch('open-modal', 'cancelar')">Cancelar cita</x-danger-button>
                @endif
            </div>
        </div>
    </div>

    @if ($cita->estaProgramada())
        {{-- CU11 Reprogramar: nueva fecha y hora con la misma duración; la cita original queda cancelada --}}
        <x-modal name="reprogramar" :show="$volvioReprogramar" focusable>
            <form method="POST" action="{{ route('citas.reprogramar') }}" class="p-6 space-y-6"
                  x-data="{
                     hora: {{ Js::from($volvioReprogramar ? old('hora_inicio', '') : '') }},
                     duracion: {{ Js::from($cita->duracion_minutos) }},
                     horaFin() {
                         if (! this.hora) return '—';
                         const [h, m] = this.hora.split(':').map(Number);
                         const total = h * 60 + m + this.duracion;
                         if (total >= 24 * 60) return '—';
                         return String(Math.floor(total / 60)).padStart(2, '0') + ':' + String(total % 60).padStart(2, '0');
                     },
                  }">
                @csrf
                <input type="hidden" name="formulario" value="reprogramar">
                <input type="hidden" name="id" value="{{ $cita->id }}">

                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reprogramar cita</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Se crea una cita nueva de {{ $cita->duracion_minutos }} minutos y esta queda cancelada, con la referencia a la nueva.
                    </p>
                </header>

                @if ($volvioReprogramar)
                    <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200 space-y-3">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if ($franjasLibres)
                            <div class="flex flex-wrap gap-2">
                                @foreach ($franjasLibres as $franja)
                                    <button type="button" @click="hora = {{ Js::from($franja['sugerida']) }}"
                                            class="rounded-md border border-red-300 dark:border-red-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-800 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                                        Libre de {{ $franja['inicio'] }} a {{ $franja['fin'] }} · usar {{ $franja['sugerida'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="reprogramar_fecha" value="Nueva fecha" />
                        <x-text-input id="reprogramar_fecha" name="fecha" type="date" class="mt-1 block w-full"
                                      :value="$volvioReprogramar ? old('fecha') : $cita->fecha->toDateString()" min="{{ today()->toDateString() }}" required />
                    </div>
                    <div>
                        <x-input-label for="reprogramar_hora" value="Nueva hora de inicio" />
                        <x-text-input id="reprogramar_hora" name="hora_inicio" type="time" class="mt-1 block w-full" x-model="hora" min="{{ $apertura }}" max="{{ $cierre }}" required />
                    </div>
                    <div>
                        <x-input-label for="reprogramar_fin" value="Hora de fin" />
                        <x-text-input id="reprogramar_fin" type="text" class="mt-1 block w-full bg-gray-100 dark:bg-gray-700" x-bind:value="horaFin()" readonly tabindex="-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="motivo_cambio" value="Motivo del cambio" />
                        <textarea id="motivo_cambio" name="motivo_cambio" rows="2" maxlength="255" class="{{ $claseCampo }}" required>{{ $volvioReprogramar ? old('motivo_cambio') : '' }}</textarea>
                    </div>
                </div>

                <div class="flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Volver</x-secondary-button>
                    <x-primary-button>Reprogramar</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- CU11 Cancelar: la cita queda cancelada con su motivo y su horario se libera --}}
        <x-modal name="cancelar" :show="$volvioCancelar" focusable>
            <form method="POST" action="{{ route('citas.cancelar') }}" class="p-6 space-y-6">
                @csrf
                <input type="hidden" name="formulario" value="cancelar">
                <input type="hidden" name="id" value="{{ $cita->id }}">

                <header>
                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cancelar cita</h2>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        La cita de {{ $cita->paciente->nombreCompleto() }} del {{ $cita->fecha->format('d/m/Y') }} a las {{ $cita->horaInicioCorta() }} quedará cancelada (no se borra) y su horario quedará libre.
                    </p>
                </header>

                <div>
                    <x-input-label for="motivo_cancelacion" value="Motivo de la cancelación" />
                    <textarea id="motivo_cancelacion" name="motivo" rows="2" maxlength="255" class="{{ $claseCampo }}" required>{{ $volvioCancelar ? old('motivo') : '' }}</textarea>
                    @if ($volvioCancelar) <x-input-error :messages="$errors->get('motivo')" class="mt-2" /> @endif
                </div>

                <div class="flex justify-end gap-3">
                    <x-secondary-button x-on:click="$dispatch('close')">Volver</x-secondary-button>
                    <x-danger-button>Cancelar cita</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
