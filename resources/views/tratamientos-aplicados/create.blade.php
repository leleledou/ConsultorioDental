{{-- CU28 Registrar tratamiento aplicado (RF-27, RF-38, RF-39). --}}
@php
    $formularioInicial = [
        'tratamiento_id' => old('tratamiento_id', ''),
        'costo_acordado' => old('costo_acordado', ''),
        'sesiones_planificadas' => old('sesiones_planificadas', ''),
    ];

    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Agregar tratamiento: '.$plan->paciente->nombreCompleto()"
                           subtitulo="Registre el tratamiento, su diagnóstico, su costo y las sesiones previstas."
                           :volver="route('planes.mostrar', $plan->id)" volver-texto="Plan de tratamiento" />
    </x-slot>

    {{-- seleccionarTratamiento(): propone costo y sesiones sin ir al servidor; se pueden ajustar. --}}
    <x-pagina ancho="4xl"
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
        {{-- Errores de negocio (el plan dejó de estar activo) --}}
        @error('operacion')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        {{-- RF-39: advertencia, no bloquea el registro --}}
        <x-alerta tipo="aviso" x-show="advertencia" x-cloak><span x-text="advertencia"></span></x-alerta>

        {{-- Plan al que se agrega --}}
        <section class="dr-card flex flex-col gap-1 p-4 text-sm sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-label="Plan">
            <p class="min-w-0 text-ink-soft">Plan del <span class="font-semibold text-ink">{{ $plan->fecha_creacion->format('d/m/Y') }}</span> · paciente <span class="font-semibold text-ink">{{ $plan->paciente->nombreCompleto() }}</span></p>
            <x-estado-clinico :estado="$plan->estado" class="self-start" />
        </section>

        <form method="POST" action="{{ route('tratamientos-aplicados.guardar') }}" class="dr-card overflow-hidden">
            @csrf
            <input type="hidden" name="plan_tratamiento_id" value="{{ $plan->id }}">

            <div class="space-y-8 p-5 sm:p-6">
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Tratamiento y diagnóstico</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="tratamiento_id" label="Tratamiento" :requerido="true" :errores="$errors->get('tratamiento_id')">
                            <select id="tratamiento_id" name="tratamiento_id" class="{{ $claseCampo('tratamiento_id') }}" x-model="form.tratamiento_id"
                                    @change="seleccionarTratamiento($event.target.value)" required>
                                <option value="">Seleccione…</option>
                                @foreach ($tratamientos as $t)
                                    <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="pieza_dental_id" label="Pieza dental (opcional)" :errores="$errors->get('pieza_dental_id')">
                            <select id="pieza_dental_id" name="pieza_dental_id" class="{{ $claseCampo('pieza_dental_id') }}">
                                <option value="">Sin pieza</option>
                                @foreach ($piezas as $pieza)
                                    <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id') == $pieza->id)>{{ $pieza->codigo_fdi }} - {{ $pieza->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="diagnostico_id" label="Diagnóstico" :requerido="true" class="sm:col-span-2" :errores="$errors->get('diagnostico_id')">
                            <select id="diagnostico_id" name="diagnostico_id" class="{{ $claseCampo('diagnostico_id') }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($diagnosticos as $d)
                                    <option value="{{ $d->id }}" @selected(old('diagnostico_id') == $d->id)>{{ $d->codigo }} - {{ $d->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="observacion_diagnostica" label="Observación diagnóstica (opcional)" class="sm:col-span-2" :errores="$errors->get('observacion_diagnostica')">
                            <textarea id="observacion_diagnostica" name="observacion_diagnostica" rows="3" class="{{ $claseCampo('observacion_diagnostica') }}">{{ old('observacion_diagnostica') }}</textarea>
                        </x-campo>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Costo y programación</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <x-campo name="costo_acordado" label="Costo acordado" :requerido="true" ayuda="Se propone el precio vigente del tratamiento; puede ajustarlo." :errores="$errors->get('costo_acordado')">
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink-soft" aria-hidden="true">Bs</span>
                                <x-text-input id="costo_acordado" name="costo_acordado" type="number" min="0" max="99999999.99" step="0.01" inputmode="decimal" class="pl-10 tabular-nums" x-model="form.costo_acordado" required :error="$errors->has('costo_acordado')" />
                            </div>
                        </x-campo>

                        <x-campo name="sesiones_planificadas" label="Sesiones planificadas" :requerido="true" ayuda="Se proponen las sesiones previstas del tratamiento." :errores="$errors->get('sesiones_planificadas')">
                            <x-text-input id="sesiones_planificadas" name="sesiones_planificadas" type="number" min="1" max="65535" step="1" inputmode="numeric" x-model="form.sesiones_planificadas" required :error="$errors->has('sesiones_planificadas')" />
                        </x-campo>

                        <x-campo name="fecha_inicio" label="Fecha de inicio" :requerido="true" :errores="$errors->get('fecha_inicio')">
                            <x-text-input id="fecha_inicio" name="fecha_inicio" type="date" :value="old('fecha_inicio')" required :error="$errors->has('fecha_inicio')" />
                        </x-campo>
                    </div>
                </fieldset>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    <a href="{{ route('planes.mostrar', $plan->id) }}" class="btn btn-secondary">Volver al plan</a>
                    <x-primary-button>Registrar tratamiento</x-primary-button>
                </div>
            </div>
        </form>
    </x-pagina>
</x-app-layout>
