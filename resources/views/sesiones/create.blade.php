{{-- CU30 Registrar sesión de evolución (RF-43 a RF-50). Desde una cita atendida, RF-49 y RF-66. --}}
@php
    $paciente = $tratamiento->plan->paciente;
    // Cancelado y concluido son estados terminales: ya no admiten sesiones.
    $cerrado = in_array($tratamiento->estado, ['cancelado', 'concluido'], true);
    $porcentaje = $tratamiento->sesiones_planificadas > 0 ? min(100, (int) round($realizadas / $tratamiento->sesiones_planificadas * 100)) : 0;

    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');

    // Filas de materiales (RF-45): las que volvieron con error o ninguna.
    $filasIniciales = collect(old('materiales', []))->values()->map(fn ($m) => [
        'material_id' => (string) ($m['material_id'] ?? ''),
        'cantidad' => (string) ($m['cantidad'] ?? ''),
        'observacion' => (string) ($m['observacion'] ?? ''),
    ])->all();

    $volver = $cita ? route('citas.mostrar', $cita->id) : route('tratamientos-aplicados.mostrar', $tratamiento->id);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Registrar sesión: '.$tratamiento->tratamiento->nombre"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="$volver" :volver-texto="$cita ? 'Cita' : 'Tratamiento aplicado'" />
    </x-slot>

    <x-pagina ancho="4xl"
              x-data="{
                  filas: {{ Js::from($filasIniciales) }},
                  agregar() { this.filas.push({ material_id: '', cantidad: '', observacion: '' }); },
                  quitar(i) { this.filas.splice(i, 1); },
              }">
        @error('operacion')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        {{-- Tratamiento y avance --}}
        <section class="dr-card flex flex-col gap-4 p-5 sm:p-6" aria-label="Tratamiento">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="break-words font-display text-xl font-bold text-ink">{{ $tratamiento->tratamiento->nombre }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">
                        Pieza {{ $tratamiento->piezaDental?->codigo_fdi ?? '—' }} · iniciado el {{ $tratamiento->fecha_inicio->format('d/m/Y') }}
                    </p>
                </div>
                <x-estado-clinico :estado="$tratamiento->estado" class="self-start" />
            </div>

            <div class="grid grid-cols-1 gap-4 border-t border-line pt-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Avance</p>
                    <p class="mt-1 font-medium text-ink">Sesión {{ $realizadas }} de {{ $tratamiento->sesiones_planificadas }}</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-100" aria-hidden="true"><div class="h-full rounded-full bg-brand-600" style="width: {{ $porcentaje }}%"></div></div>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Se registrará como</p>
                    <p class="mt-1 font-display text-2xl font-bold text-ink">Sesión {{ $numero }}</p>
                </div>
            </div>
        </section>

        @if ($cita)
            {{-- RF-49: la sesión sale de una cita atendida --}}
            <x-alerta tipo="exito">
                Sesión generada desde la cita del {{ $cita->fecha->format('d/m/Y') }} a las {{ $cita->horaInicioCorta() }} ({{ $cita->motivo }}).
                Se precargaron su fecha y su duración.
            </x-alerta>
        @endif

        @if ($cerrado)
            <x-alerta tipo="error">No se pueden registrar sesiones en un tratamiento {{ $tratamiento->estado }}.</x-alerta>
        @else
            <form method="POST" action="{{ route('sesiones.guardar') }}" class="dr-card overflow-hidden">
                @csrf
                <input type="hidden" name="tratamiento_aplicado_id" value="{{ $tratamiento->id }}">
                @if ($cita)
                    <input type="hidden" name="cita_id" value="{{ $cita->id }}">
                @endif

                <div class="space-y-8 p-5 sm:p-6">
                    {{-- RF-50: la sesión supera lo planificado --}}
                    @if ($excede)
                        <div class="space-y-3">
                            <x-alerta tipo="aviso">
                                El tratamiento tiene {{ $tratamiento->sesiones_planificadas }} sesiones planificadas y esta será la sesión {{ $numero }}.
                                Para registrarla, amplíe el número de sesiones.
                            </x-alerta>
                            <label for="ampliar" @class([
                                'flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm font-semibold text-ink has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50',
                                'border-red-300' => $errors->has('ampliar'),
                                'border-line' => ! $errors->has('ampliar'),
                            ])>
                                <input type="hidden" name="ampliar" value="0">
                                <input id="ampliar" type="checkbox" name="ampliar" value="1" class="h-4 w-4 rounded border-line text-brand-700 focus:ring-brand-500" @checked(old('ampliar'))>
                                Ampliar el número de sesiones a {{ $numero }}
                            </label>
                            <x-input-error :messages="$errors->get('ampliar')" />
                        </div>
                    @endif

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos de la sesión</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-3">
                            <x-campo name="fecha" label="Fecha" :requerido="true" :ayuda="'Desde el '.$tratamiento->fecha_inicio->format('d/m/Y').' hasta hoy.'" :errores="$errors->get('fecha')">
                                <x-text-input id="fecha" name="fecha" type="date" min="{{ $tratamiento->fecha_inicio->toDateString() }}" max="{{ today()->toDateString() }}"
                                              :value="old('fecha', $fechaPropuesta)" required :error="$errors->has('fecha')" />
                            </x-campo>

                            <x-campo name="duracion_real_minutos" label="Duración (minutos)" :requerido="true" ayuda="Entre 1 y 600." :errores="$errors->get('duracion_real_minutos')">
                                <x-text-input id="duracion_real_minutos" name="duracion_real_minutos" type="number" min="1" max="600" step="1" inputmode="numeric" class="tabular-nums"
                                              :value="old('duracion_real_minutos', $duracionPropuesta)" required :error="$errors->has('duracion_real_minutos')" />
                            </x-campo>

                            <x-campo name="fecha_proxima_sesion" label="Próxima sesión (opcional)" ayuda="Posterior a la fecha de esta sesión." :errores="$errors->get('fecha_proxima_sesion')">
                                <x-text-input id="fecha_proxima_sesion" name="fecha_proxima_sesion" type="date" :value="old('fecha_proxima_sesion')" :error="$errors->has('fecha_proxima_sesion')" />
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Evolución</legend>
                        <div class="mt-4 space-y-5">
                            <x-campo name="avance_tecnico" label="Avance técnico (opcional)" ayuda="Qué se hizo en esta sesión." :errores="$errors->get('avance_tecnico')">
                                <textarea id="avance_tecnico" name="avance_tecnico" rows="3" class="{{ $claseCampo('avance_tecnico') }}">{{ old('avance_tecnico') }}</textarea>
                            </x-campo>

                            <x-campo name="observaciones" label="Observaciones (opcional)" :errores="$errors->get('observaciones')">
                                <textarea id="observaciones" name="observaciones" rows="2" class="{{ $claseCampo('observaciones') }}">{{ old('observaciones') }}</textarea>
                            </x-campo>
                        </div>
                    </fieldset>

                    {{-- RF-45: materiales usados (también pueden cargarse después, desde la ficha de la sesión) --}}
                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Materiales usados (opcional)</legend>

                        @if ($errors->has('materiales*'))
                            <x-alerta tipo="error" class="mt-4">
                                <ul class="space-y-1">
                                    @foreach (collect($errors->getMessages())->filter(fn ($m, $clave) => str_starts_with($clave, 'materiales'))->flatten()->unique() as $mensaje)
                                        <li>{{ $mensaje }}</li>
                                    @endforeach
                                </ul>
                            </x-alerta>
                        @endif

                        <ul class="mt-4 space-y-3">
                            <template x-for="(fila, i) in filas" :key="i">
                                <li class="grid grid-cols-1 gap-3 rounded-xl border border-line p-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end">
                                    <div>
                                        <label :for="'material_' + i" class="block text-sm font-semibold text-ink">Material<span class="ms-0.5 text-red-700" aria-hidden="true">*</span></label>
                                        <select :id="'material_' + i" :name="'materiales[' + i + '][material_id]'" x-model="fila.material_id" class="dr-input mt-1.5" required>
                                            <option value="">Seleccione…</option>
                                            @foreach ($materiales as $m)
                                                <option value="{{ $m->id }}">{{ $m->nombre }} ({{ $m->unidad_medida }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label :for="'cantidad_' + i" class="block text-sm font-semibold text-ink">Cantidad<span class="ms-0.5 text-red-700" aria-hidden="true">*</span></label>
                                        <input :id="'cantidad_' + i" type="number" min="0.01" max="999999.99" step="0.01" inputmode="decimal" :name="'materiales[' + i + '][cantidad]'" x-model="fila.cantidad" class="dr-input mt-1.5 tabular-nums" required>
                                    </div>
                                    <div>
                                        <label :for="'obs_material_' + i" class="block text-sm font-semibold text-ink">Observación</label>
                                        <input :id="'obs_material_' + i" type="text" :name="'materiales[' + i + '][observacion]'" x-model="fila.observacion" class="dr-input mt-1.5">
                                    </div>
                                    <x-danger-button type="button" :suave="true" class="btn-sm" @click="quitar(i)">
                                        <x-icon name="close" class="h-4 w-4" /> <span class="sm:sr-only">Quitar material</span>
                                    </x-danger-button>
                                </li>
                            </template>
                        </ul>

                        <p x-show="filas.length === 0" class="mt-3 text-sm text-ink-soft">No se agregaron materiales.</p>

                        @if ($materiales->isNotEmpty())
                            <x-secondary-button class="btn-sm mt-3" @click="agregar()"><x-icon name="plus" class="h-4 w-4" /> Agregar material</x-secondary-button>
                        @endif
                    </fieldset>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <a href="{{ $volver }}" class="btn btn-secondary">Cancelar</a>
                        <x-primary-button>Registrar sesión</x-primary-button>
                    </div>
                </div>
            </form>
        @endif
    </x-pagina>
</x-app-layout>
