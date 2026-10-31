{{-- Ficha de una cita (CU10). Desde aquí se reprograma, cancela (CU11) o cambia el estado (CU12). --}}
@php
    // Cada modal envía un campo oculto "formulario"; si vuelve con errores, se vuelve a abrir
    // ese modal y los errores se muestran dentro de él.
    $volvioReprogramar = old('formulario') === 'reprogramar' && $errors->any();
    $volvioCancelar = old('formulario') === 'cancelar' && $errors->any();

    // CU11 flujo A2: franjas libres más cercanas, si el nuevo horario estaba ocupado.
    $franjasLibres = session('franjas_libres', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline">
            <x-icon name="arrow-left" class="h-4 w-4" /> Agenda
        </a>
        <h2>Cita N.º {{ $cita->id }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto min-w-0 max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('exito'))
                <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
            @endif

            @if ($errors->any() && ! $volvioReprogramar && ! $volvioCancelar)
                <x-alerta tipo="error">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alerta>
            @endif

            {{-- Resumen: fecha, horario y estado --}}
            <section class="dr-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Resumen de la cita">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-2xl bg-brand-700 text-white" aria-hidden="true">
                        <span class="text-[10px] font-bold uppercase leading-none">{{ $cita->fecha->locale('es')->isoFormat('MMM') }}</span>
                        <span class="font-display text-xl font-extrabold leading-tight">{{ $cita->fecha->format('d') }}</span>
                    </span>
                    <div class="min-w-0">
                        <p class="font-display text-xl font-bold text-ink">{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}</p>
                        <p class="mt-0.5 text-sm tabular-nums text-ink-soft">{{ $cita->horaInicioCorta() }} – {{ $cita->horaFinCorta() }} · {{ $cita->duracion_minutos }} minutos</p>
                    </div>
                </div>
                <x-estado-cita :nombre="$cita->estado->nombre" class="self-start sm:self-center" />
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Datos de la cita --}}
                <section class="dr-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="datos-cita">
                    <h3 id="datos-cita" class="text-lg font-bold text-ink">Datos de la cita</h3>

                    <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Paciente</dt>
                            <dd class="mt-1 break-words font-medium text-ink">
                                <a href="{{ route('pacientes.mostrar', $cita->paciente->id) }}" class="font-semibold text-brand-700 hover:text-brand-900 hover:underline">
                                    {{ $cita->paciente->nombreCompleto() }}
                                </a>
                                <span class="text-ink-soft">· CI {{ $cita->paciente->ci }}</span>
                            </dd>
                        </div>
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Odontólogo responsable</dt>
                            <dd class="mt-1 font-medium text-ink">{{ $cita->odontologo->nombres }} {{ $cita->odontologo->apellidos }}</dd>
                        </div>
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Fecha</dt>
                            <dd class="mt-1 font-medium text-ink">{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}</dd>
                        </div>
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Horario</dt>
                            <dd class="mt-1 font-medium tabular-nums text-ink">{{ $cita->horaInicioCorta() }} – {{ $cita->horaFinCorta() }}</dd>
                        </div>
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Duración</dt>
                            <dd class="mt-1 font-medium text-ink">{{ $cita->duracion_minutos }} minutos</dd>
                        </div>
                        <div class="min-w-0 border-b border-line pb-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Fecha de registro</dt>
                            <dd class="mt-1 font-medium text-ink">{{ $cita->fecha_registro->format('d/m/Y') }}</dd>
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Motivo</dt>
                            <dd class="mt-1 break-words font-medium text-ink">{{ $cita->motivo ?? '—' }}</dd>
                        </div>
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Observaciones</dt>
                            <dd class="mt-1 whitespace-pre-line break-words font-medium text-ink">{{ $cita->observaciones ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>

                {{-- CU12 Actualizar estado (RF-71): solo para una cita programada --}}
                @if ($cita->estaProgramada())
                    <section class="dr-card h-fit p-5 sm:p-6" aria-labelledby="actualizar-estado">
                        <h3 id="actualizar-estado" class="text-lg font-bold text-ink">Actualizar estado</h3>
                        <p class="mt-1.5 text-sm text-ink-soft">
                            «Atendida» y «No asistida» solo se pueden marcar el día de la cita o después.
                        </p>

                        <form method="POST" action="{{ route('citas.estado') }}" class="mt-5 space-y-4">
                            @csrf
                            <input type="hidden" name="id" value="{{ $cita->id }}">
                            <div>
                                <x-input-label for="estado_cita_id" value="Nuevo estado" />
                                <select id="estado_cita_id" name="estado_cita_id" class="dr-input mt-1.5" required>
                                    <option value="">Elegir…</option>
                                    @foreach ($estadosDestino as $e)
                                        <option value="{{ $e->id }}" @disabled($cita->fecha->gt(today()) && $e->nombre !== 'cancelada')>{{ ucfirst($e->nombre) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label for="observacion" value="Observación (opcional)" />
                                <x-text-input id="observacion" name="observacion" type="text" class="mt-1.5 block w-full" maxlength="255" />
                            </div>
                            <x-primary-button class="w-full">Guardar estado</x-primary-button>
                        </form>
                    </section>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="btn btn-secondary">
                    <x-icon name="arrow-left" class="h-4 w-4" /> Volver a la agenda
                </a>

                {{-- CU11: solo una cita programada se puede reprogramar o cancelar --}}
                @if ($cita->estaProgramada())
                    <x-secondary-button x-data @click="$dispatch('open-modal', 'reprogramar')"><x-icon name="calendar" class="h-4 w-4" /> Reprogramar</x-secondary-button>
                    <x-danger-button :suave="true" type="button" x-data @click="$dispatch('open-modal', 'cancelar')"><x-icon name="ban" class="h-4 w-4" /> Cancelar cita</x-danger-button>
                @endif
            </div>
        </div>
    </div>

    @if ($cita->estaProgramada())
        {{-- CU11 Reprogramar: nueva fecha y hora con la misma duración; la cita original queda cancelada --}}
        <x-modal name="reprogramar" :show="$volvioReprogramar" focusable>
            <form method="POST" action="{{ route('citas.reprogramar') }}" class="space-y-5 p-6"
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

                <header class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="calendar" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Reprogramar cita</h2>
                        <p class="mt-1 text-sm text-ink-soft">
                            Se crea una cita nueva de {{ $cita->duracion_minutos }} minutos y esta queda cancelada, con la referencia a la nueva.
                        </p>
                    </div>
                </header>

                @if ($volvioReprogramar)
                    <x-alerta tipo="error">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        @if ($franjasLibres)
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($franjasLibres as $franja)
                                    <button type="button" @click="hora = {{ Js::from($franja['sugerida']) }}"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-white px-3 py-1.5 text-xs font-semibold text-ink transition hover:border-brand-500 hover:bg-brand-50">
                                        <x-icon name="clock" class="h-3.5 w-3.5 text-brand-700" /> Libre de {{ $franja['inicio'] }} a {{ $franja['fin'] }} · usar {{ $franja['sugerida'] }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </x-alerta>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <x-input-label for="reprogramar_fecha" value="Nueva fecha" />
                        <x-text-input id="reprogramar_fecha" name="fecha" type="date" class="mt-1.5 block w-full"
                                      :value="$volvioReprogramar ? old('fecha') : $cita->fecha->toDateString()" min="{{ today()->toDateString() }}" required />
                    </div>
                    <div>
                        <x-input-label for="reprogramar_hora" value="Nueva hora de inicio" />
                        <x-text-input id="reprogramar_hora" name="hora_inicio" type="time" class="mt-1.5 block w-full" x-model="hora" min="{{ $apertura }}" max="{{ $cierre }}" required />
                    </div>
                    <div>
                        <x-input-label for="reprogramar_fin" value="Hora de fin" />
                        <x-text-input id="reprogramar_fin" type="text" class="mt-1.5 block w-full !border-brand-200 !bg-brand-50 font-bold tabular-nums !text-brand-900" x-bind:value="horaFin()" readonly tabindex="-1" />
                    </div>
                    <div class="sm:col-span-3">
                        <x-input-label for="motivo_cambio" value="Motivo del cambio" />
                        <textarea id="motivo_cambio" name="motivo_cambio" rows="2" maxlength="255" class="dr-input mt-1.5" required>{{ $volvioReprogramar ? old('motivo_cambio') : '' }}</textarea>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Volver</x-secondary-button>
                    <x-primary-button>Reprogramar</x-primary-button>
                </div>
            </form>
        </x-modal>

        {{-- CU11 Cancelar: la cita queda cancelada con su motivo y su horario se libera --}}
        <x-modal name="cancelar" :show="$volvioCancelar" focusable>
            <form method="POST" action="{{ route('citas.cancelar') }}" class="space-y-5 p-6">
                @csrf
                <input type="hidden" name="formulario" value="cancelar">
                <input type="hidden" name="id" value="{{ $cita->id }}">

                <header class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700"><x-icon name="alert" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Cancelar cita</h2>
                        <p class="mt-1 text-sm text-ink-soft">
                            La cita de {{ $cita->paciente->nombreCompleto() }} del {{ $cita->fecha->format('d/m/Y') }} a las {{ $cita->horaInicioCorta() }} quedará cancelada (no se borra) y su horario quedará libre.
                        </p>
                    </div>
                </header>

                <div>
                    <x-input-label for="motivo_cancelacion" value="Motivo de la cancelación" />
                    <textarea id="motivo_cancelacion" name="motivo" rows="2" maxlength="255" class="dr-input mt-1.5 {{ $volvioCancelar && $errors->has('motivo') ? 'dr-input-error' : '' }}" required>{{ $volvioCancelar ? old('motivo') : '' }}</textarea>
                    @if ($volvioCancelar) <x-input-error :messages="$errors->get('motivo')" class="mt-2" /> @endif
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:justify-end">
                    <x-secondary-button x-on:click="$dispatch('close')">Volver</x-secondary-button>
                    <x-danger-button>Cancelar cita</x-danger-button>
                </div>
            </form>
        </x-modal>
    @endif
</x-app-layout>
