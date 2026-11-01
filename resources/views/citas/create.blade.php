{{-- CU10 Programar cita (RF-66 a RF-69). Los dos roles. --}}
@php
    // Paciente elegido: el anterior (si volvió con errores) o el único resultado de la búsqueda.
    $pacienteElegido = old('paciente_id', $buscar && $pacientes->count() === 1 ? $pacientes->first()->id : '');

    // Franjas libres más cercanas (flujo A2), si la franja pedida estaba ocupada.
    $franjasLibres = session('franjas_libres', []);

    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('agenda') }}" class="mb-1 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-brand-700 sm:mb-2 sm:min-h-0 hover:text-brand-900 hover:underline">
            <x-icon name="arrow-left" class="h-4 w-4" /> Agenda
        </a>
        <h1>Programar cita</h1>
        <p class="mt-1 text-sm text-ink-soft">Busque al paciente y complete los datos de la cita.</p>
    </x-slot>

    {{-- RF-67: al elegir el tratamiento se propone su duración. RF-68: la hora de fin se calcula en pantalla. --}}
    <div class="py-8"
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
        <div class="mx-auto min-w-0 max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl space-y-6">
            @if ($errors->any())
                <x-alerta tipo="error">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                    {{-- Flujo A2: franjas libres más cercanas de ese día --}}
                    @if ($franjasLibres)
                        <div class="mt-3">
                            <p>Franjas libres más cercanas ese día:</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($franjasLibres as $franja)
                                    <button type="button" @click="hora = {{ Js::from($franja['sugerida']) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-1.5 max-sm:min-h-11 text-xs font-semibold text-ink transition hover:border-brand-500 hover:bg-brand-50">
                                        <x-icon name="clock" class="h-3.5 w-3.5 text-brand-700" /> Libre de {{ $franja['inicio'] }} a {{ $franja['fin'] }} · usar {{ $franja['sugerida'] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </x-alerta>
            @endif

            {{-- Paso 1: buscar al paciente (misma búsqueda de CU09, solo pacientes activos) --}}
            <section class="dr-card p-5 sm:p-6" aria-labelledby="paso-1">
                <h3 id="paso-1" class="flex items-center gap-3 text-lg font-bold text-ink">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white" aria-hidden="true">1</span> Buscar paciente
                </h3>

                <form method="GET" action="{{ route('citas.crear') }}" role="search" class="mt-4 flex flex-col gap-2 sm:flex-row">
                    {{-- Se conservan la fecha y la hora ya elegidas --}}
                    <input type="hidden" name="fecha" :value="fecha">
                    <input type="hidden" name="hora_inicio" :value="hora" :disabled="! hora">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar paciente</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar"
                                      placeholder="Buscar por CI, nombres o apellidos" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('citas.crear', ['fecha' => $fecha]) }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <p class="mt-3 text-sm text-ink-soft">
                    @if ($pacientes->isEmpty())
                        No se encontraron pacientes activos con ese criterio de búsqueda.
                    @else
                        <span class="font-semibold text-ink">{{ $pacientes->count() }}</span> paciente(s) activo(s) en la lista{{ $buscar ? ' para «'.$buscar.'»' : '' }}.
                    @endif
                </p>
            </section>

            {{-- Pasos 2 a 4: datos de la cita --}}
            <section class="dr-card overflow-hidden" aria-labelledby="paso-2">
                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="paso-2" class="flex items-center gap-3 text-lg font-bold text-ink">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-700 text-sm font-bold text-white" aria-hidden="true">2</span> Datos de la cita
                    </h3>
                    <p class="mt-1.5 flex items-start gap-2 text-sm text-ink-soft">
                        <x-icon name="clock" class="mt-0.5 h-4 w-4 shrink-0 text-brand-700" />
                        <span>Horario de atención: <span class="font-semibold text-ink">{{ $apertura }} a {{ $cierre }}</span>. Hay un solo sillón: no puede haber dos citas programadas a la misma hora.</span>
                    </p>
                </header>

                <form method="POST" action="{{ route('citas.guardar') }}" class="p-5 sm:p-6">
                    @csrf

                    <div class="space-y-8">
                        <fieldset>
                            <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Paciente y profesional</legend>
                            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-campo name="paciente_id" label="Paciente" :requerido="true" class="sm:col-span-2" :errores="$errors->get('paciente_id')">
                                    <select id="paciente_id" name="paciente_id" class="{{ $claseCampo('paciente_id') }}" required>
                                        <option value="">Seleccione…</option>
                                        @foreach ($pacientes as $p)
                                            <option value="{{ $p->id }}" @selected((string) $pacienteElegido === (string) $p->id)>
                                                {{ $p->apellidos }}, {{ $p->nombres }} — CI {{ $p->ci }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-campo>

                                <x-campo name="odontologo_id" label="Odontólogo responsable" :requerido="true" :errores="$errors->get('odontologo_id')">
                                    <select id="odontologo_id" name="odontologo_id" class="{{ $claseCampo('odontologo_id') }}" required>
                                        @foreach ($odontologos as $o)
                                            <option value="{{ $o->id }}" @selected((string) old('odontologo_id', Auth::id()) === (string) $o->id)>
                                                {{ $o->nombres }} {{ $o->apellidos }}
                                            </option>
                                        @endforeach
                                    </select>
                                </x-campo>

                                <x-campo name="tratamiento_id" label="Tratamiento" :requerido="true" :errores="$errors->get('tratamiento_id')">
                                    <select id="tratamiento_id" name="tratamiento_id" class="{{ $claseCampo('tratamiento_id') }}" @change="elegirTratamiento($event)" required>
                                        <option value="">Seleccione…</option>
                                        @foreach ($tratamientos as $t)
                                            {{-- data-duracion: duración que se propone (RF-67 / RN-08) --}}
                                            <option value="{{ $t->id }}" data-duracion="{{ $t->duracion_estimada_minutos }}" @selected((string) old('tratamiento_id') === (string) $t->id)>
                                                {{ $t->nombre }} ({{ $t->especialidad->nombre }}, {{ $t->duracion_estimada_minutos }} min)
                                            </option>
                                        @endforeach
                                    </select>
                                </x-campo>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Fecha y horario</legend>
                            <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                                <x-campo name="fecha" label="Fecha" :requerido="true" :errores="$errors->get('fecha')">
                                    <x-text-input id="fecha" name="fecha" type="date" x-model="fecha" min="{{ today()->toDateString() }}" required :error="$errors->has('fecha')" />
                                </x-campo>

                                <x-campo name="hora_inicio" label="Hora de inicio" :requerido="true" :errores="$errors->get('hora_inicio')">
                                    <x-text-input id="hora_inicio" name="hora_inicio" type="time" x-model="hora" min="{{ $apertura }}" max="{{ $cierre }}" required :error="$errors->has('hora_inicio')" />
                                </x-campo>

                                <x-campo name="duracion_minutos" label="Duración (minutos)" :requerido="true" ayuda="Se propone la duración estimada del tratamiento; puede cambiarla." :errores="$errors->get('duracion_minutos')">
                                    <x-text-input id="duracion_minutos" name="duracion_minutos" type="number" min="1" max="600" step="1" x-model="duracion" required :error="$errors->has('duracion_minutos')" />
                                </x-campo>

                                <div>
                                    {{-- RF-68 / RN-09: solo se muestra; la base de datos calcula la hora de fin al guardar --}}
                                    <label for="hora_fin" class="block text-sm font-semibold text-ink">Hora de fin (calculada)</label>
                                    <div class="mt-1.5">
                                        <x-text-input id="hora_fin" type="text" class="!border-brand-200 !bg-brand-50 font-display text-lg font-bold tabular-nums !text-brand-900" x-bind:value="horaFin()" readonly tabindex="-1" />
                                    </div>
                                    <p class="mt-1.5 text-xs text-ink-soft">Se actualiza al cambiar la hora de inicio o la duración.</p>
                                </div>
                            </div>
                        </fieldset>

                        <fieldset>
                            <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Motivo</legend>
                            <div class="mt-4">
                                <x-campo name="motivo" label="Motivo (opcional)" ayuda="Si lo deja vacío se usa el nombre del tratamiento." :errores="$errors->get('motivo')">
                                    <x-text-input id="motivo" name="motivo" type="text" :value="old('motivo')" maxlength="150" :error="$errors->has('motivo')" />
                                </x-campo>
                            </div>
                        </fieldset>
                    </div>

                    <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row">
                            <a href="{{ route('agenda') }}" class="btn btn-secondary">Volver a la agenda</a>
                            <x-primary-button>Programar cita</x-primary-button>
                        </div>
                    </div>
                </form>
            </section>
            </div>
        </div>
    </div>
</x-app-layout>
