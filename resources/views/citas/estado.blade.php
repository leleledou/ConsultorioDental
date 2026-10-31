{{-- CU12 Actualizar estado de cita (RF-71): citas de un día con el cambio de estado. --}}
@php
    $esFutura = $fecha->gt(today());
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Citas del día — {{ ucfirst($fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}</h2>
        <p class="mt-1 text-sm text-ink-soft">Revise las citas del día y actualice su estado.</p>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto min-w-0 max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('exito'))
                <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
            @endif

            @if ($errors->any())
                <x-alerta tipo="error">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </x-alerta>
            @endif

            <section class="dr-card overflow-hidden" aria-label="Citas del día">
                {{-- Selector de fecha --}}
                <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ route('citas.listar', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" class="btn btn-secondary btn-sm !px-2.5" aria-label="Día anterior"><x-icon name="chevron-left" class="h-4 w-4" /></a>
                        <form method="GET" action="{{ route('citas.listar') }}" class="flex gap-2">
                            <label for="fecha" class="sr-only">Fecha</label>
                            <x-text-input id="fecha" name="fecha" type="date" class="text-sm" :value="$fecha->toDateString()" onchange="this.form.submit()" />
                            <noscript><x-primary-button>Ver</x-primary-button></noscript>
                        </form>
                        <a href="{{ route('citas.listar', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" class="btn btn-secondary btn-sm !px-2.5" aria-label="Día siguiente"><x-icon name="chevron-right" class="h-4 w-4" /></a>
                        <a href="{{ route('citas.listar') }}" class="btn btn-secondary btn-sm">Hoy</a>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('agenda', ['fecha' => $fecha->toDateString()]) }}" class="btn btn-secondary btn-sm">
                            <x-icon name="calendar" class="h-4 w-4" /> Ver en la agenda
                        </a>
                        <a href="{{ route('citas.crear', ['fecha' => $fecha->toDateString()]) }}" class="btn btn-primary btn-sm">
                            <x-icon name="plus" class="h-4 w-4" /> Programar cita
                        </a>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                    <p><span class="font-semibold text-ink">{{ $citas->count() }}</span> cita(s) en este día</p>
                    @if ($esFutura)
                        <p class="text-xs">Día futuro: sus citas solo se pueden cancelar. «Atendida» y «No asistida» se marcan el mismo día o después.</p>
                    @endif
                </div>

                @if ($citas->isEmpty())
                    {{-- Estado vacío --}}
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                            <x-icon name="calendar" class="h-7 w-7" />
                        </span>
                        <p class="mt-4 text-base font-semibold text-ink">No hay citas registradas para este día.</p>
                        <p class="mt-1 text-sm text-ink-soft">Puede programar una cita nueva o cambiar de fecha.</p>
                    </div>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($citas as $c)
                            <li class="grid gap-4 p-4 sm:p-5 lg:grid-cols-[10rem_minmax(0,1fr)_minmax(0,24rem)] lg:items-start">
                                {{-- Hora y estado --}}
                                <div class="flex items-center justify-between gap-3 lg:block lg:space-y-2">
                                    <a href="{{ route('citas.mostrar', $c->id) }}" class="inline-flex items-center gap-1.5 font-display text-lg font-bold tabular-nums text-ink hover:text-brand-800 hover:underline">
                                        <x-icon name="clock" class="h-4 w-4 text-brand-700" /> {{ $c->horaInicioCorta() }} – {{ $c->horaFinCorta() }}
                                    </a>
                                    <div><x-estado-cita :nombre="$c->estado->nombre" /></div>
                                </div>

                                {{-- Paciente, odontólogo y motivo --}}
                                <div class="min-w-0 text-sm">
                                    <p class="font-semibold text-ink">{{ $c->paciente->apellidos }}, {{ $c->paciente->nombres }}</p>
                                    <p class="mt-0.5 text-ink-soft">{{ $c->odontologo->nombres }} {{ $c->odontologo->apellidos }}</p>
                                    <p class="mt-1 text-ink-soft"><span class="font-semibold text-ink">Motivo:</span> {{ $c->motivo ?? '—' }}</p>
                                </div>

                                {{-- Cambio de estado --}}
                                <div>
                                    @if ($c->estaProgramada())
                                        {{-- El id viaja oculto; el controlador valida el estado destino --}}
                                        <form method="POST" action="{{ route('citas.estado') }}" class="space-y-2 rounded-xl bg-surface p-3">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $c->id }}">
                                            <label class="block text-xs font-semibold text-ink-soft">Nuevo estado
                                                <select name="estado_cita_id" class="dr-input mt-1 text-sm" required>
                                                    <option value="">Elegir…</option>
                                                    @foreach ($estadosDestino as $e)
                                                        {{-- Atendida / no asistida: solo hoy o días anteriores --}}
                                                        <option value="{{ $e->id }}" @disabled($esFutura && $e->nombre !== 'cancelada')>{{ ucfirst($e->nombre) }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="block text-xs font-semibold text-ink-soft">Observación (opcional)
                                                <x-text-input name="observacion" type="text" class="mt-1 block w-full text-sm" maxlength="255" />
                                            </label>
                                            <x-primary-button class="btn-sm w-full sm:w-auto">Guardar estado</x-primary-button>
                                        </form>
                                    @else
                                        <p class="text-sm text-ink-soft">Sin cambios de estado disponibles.</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
