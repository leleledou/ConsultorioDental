{{-- CU13 Consultar agenda (RF-72). Vista diaria por franjas y vista semanal (lunes a sábado). Los dos roles. --}}
@php
    // Enlace a la agenda conservando la vista, la fecha y los filtros; $cambios reemplaza algunos.
    $enlace = fn (array $cambios = []) => route('agenda', array_merge(
        ['vista' => $vista, 'fecha' => $fecha->toDateString()], $filtros, $cambios
    ));

    // Color de cada cita según su estado (borde y fondo de la tarjeta).
    $colores = [
        'programada' => 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30',
        'atendida' => 'border-green-500 bg-green-50 dark:bg-green-900/30',
        'cancelada' => 'border-red-400 bg-red-50 dark:bg-red-900/20 opacity-75',
        'no asistida' => 'border-amber-500 bg-amber-50 dark:bg-amber-900/30',
    ];

    $claseEnlace = 'inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
    $claseCampo = 'block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm';
    // Fecha anterior y siguiente: una semana o un día, según la vista.
    $anterior = $vista === 'semana' ? $fecha->copy()->subWeek() : $fecha->copy()->subDay();
    $siguiente = $vista === 'semana' ? $fecha->copy()->addWeek() : $fecha->copy()->addDay();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            @if ($vista === 'semana')
                Agenda semanal — {{ $lunes->format('d/m') }} al {{ $sabado->format('d/m/Y') }}
            @else
                Agenda del día — {{ ucfirst($fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}
            @endif
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

            {{-- Navegación, cambio de vista y filtros --}}
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-4 sm:p-6 space-y-4 text-gray-900 dark:text-gray-100">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <a href="{{ $enlace(['fecha' => $anterior->toDateString()]) }}" class="{{ $claseEnlace }}"
                           aria-label="{{ $vista === 'semana' ? 'Semana anterior' : 'Día anterior' }}">&larr;</a>
                        <a href="{{ $enlace(['fecha' => today()->toDateString()]) }}" class="{{ $claseEnlace }}">Hoy</a>
                        <a href="{{ $enlace(['fecha' => $siguiente->toDateString()]) }}" class="{{ $claseEnlace }}"
                           aria-label="{{ $vista === 'semana' ? 'Semana siguiente' : 'Día siguiente' }}">&rarr;</a>

                        {{-- Cambio de vista --}}
                        <div class="inline-flex rounded-md shadow-sm ms-2" role="group">
                            <a href="{{ $enlace(['vista' => 'dia']) }}" @class([
                                'px-3 py-2 text-xs font-semibold uppercase tracking-widest border rounded-s-md',
                                'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 border-transparent' => $vista === 'dia',
                                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-500' => $vista !== 'dia',
                            ])>Día</a>
                            <a href="{{ $enlace(['vista' => 'semana']) }}" @class([
                                'px-3 py-2 text-xs font-semibold uppercase tracking-widest border rounded-e-md',
                                'bg-gray-800 dark:bg-gray-200 text-white dark:text-gray-800 border-transparent' => $vista === 'semana',
                                'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border-gray-300 dark:border-gray-500' => $vista !== 'semana',
                            ])>Semana</a>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('citas.listar', ['fecha' => $fecha->toDateString()]) }}" class="{{ $claseEnlace }}">Actualizar estados</a>
                        <a href="{{ route('citas.crear', ['fecha' => $fecha->gte(today()) ? $fecha->toDateString() : today()->toDateString()]) }}"
                           class="inline-flex items-center px-3 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                            Programar cita
                        </a>
                    </div>
                </div>

                <form method="GET" action="{{ route('agenda') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:items-end">
                    <input type="hidden" name="vista" value="{{ $vista }}">
                    <div>
                        <x-input-label for="fecha" value="Fecha" />
                        <x-text-input id="fecha" name="fecha" type="date" class="mt-1 block w-full text-sm" :value="$fecha->toDateString()" />
                    </div>
                    <div>
                        <x-input-label for="odontologo_id" value="Odontólogo" />
                        <select id="odontologo_id" name="odontologo_id" class="mt-1 {{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($odontologos as $o)
                                <option value="{{ $o->id }}" @selected((string) ($filtros['odontologo_id'] ?? '') === (string) $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="estado_cita_id" value="Estado" />
                        <select id="estado_cita_id" name="estado_cita_id" class="mt-1 {{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($estados as $e)
                                <option value="{{ $e->id }}" @selected((string) ($filtros['estado_cita_id'] ?? '') === (string) $e->id)>{{ ucfirst($e->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-primary-button>Filtrar</x-primary-button>
                        @if ($filtros)
                            <a href="{{ route('agenda', ['vista' => $vista, 'fecha' => $fecha->toDateString()]) }}" class="text-sm text-gray-600 dark:text-gray-400 underline">Quitar filtros</a>
                        @endif
                    </div>
                </form>

                {{-- Leyenda de colores --}}
                <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>Estados:</span>
                    @foreach ($estados as $e)
                        <x-estado-cita :nombre="$e->nombre" />
                    @endforeach
                    <span class="ms-2">{{ $citas->count() }} cita(s) en {{ $vista === 'semana' ? 'la semana' : 'el día' }}.</span>
                </div>
            </div>

            @if ($vista === 'dia')
                {{-- Vista diaria: una fila por franja; cada cita ocupa las filas de su duración --}}
                <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-2 sm:p-4 text-gray-900 dark:text-gray-100">
                    <div class="grid gap-x-2 gap-y-1"
                         style="grid-template-columns: 3.25rem; grid-auto-columns: minmax(0, 1fr); grid-template-rows: repeat({{ $totalFilas }}, minmax(2.75rem, auto));">
                        {{-- Hora de cada franja (primera columna) --}}
                        @foreach ($franjas as $franja)
                            <div class="text-xs text-gray-500 dark:text-gray-400 pt-1 border-t border-gray-100 dark:border-gray-700"
                                 style="grid-column: 1; grid-row: {{ $franja['fila'] }};">
                                {{ $franja['hora'] }}
                            </div>
                        @endforeach

                        {{-- Citas (las canceladas al final, se dibujan al costado) --}}
                        @foreach ($bloques as $b)
                            @php $c = $b['cita']; @endphp
                            <a href="{{ route('citas.mostrar', $c->id) }}"
                               class="block min-w-0 rounded-md border-l-4 px-2 py-1 text-xs sm:text-sm hover:ring-2 hover:ring-indigo-400 {{ $colores[$c->estado->nombre] ?? '' }}"
                               style="grid-row: {{ $b['fila'] }} / span {{ $b['filas'] }};">
                                <div class="flex flex-wrap items-center justify-between gap-1">
                                    <span class="font-semibold">{{ $c->horaInicioCorta() }}–{{ $c->horaFinCorta() }}</span>
                                    <x-estado-cita :nombre="$c->estado->nombre" />
                                </div>
                                <div @class(['truncate font-medium', 'line-through' => $c->estado->nombre === 'cancelada'])>
                                    {{ $c->paciente->nombreCompleto() }}
                                </div>
                                <div class="truncate text-gray-600 dark:text-gray-300">{{ $c->motivo ?? '—' }} · {{ $c->odontologo->nombres }}</div>
                            </a>
                        @endforeach

                        {{-- Franjas sin cita visible: disponibles (con enlace a CU10) u ocupadas por citas filtradas --}}
                        @foreach ($franjas as $franja)
                            @if ($franja['estado'] === 'libre')
                                <a href="{{ route('citas.crear', ['fecha' => $fecha->toDateString(), 'hora_inicio' => $franja['hora']]) }}"
                                   class="flex items-center justify-between rounded-md border border-dashed border-gray-300 dark:border-gray-600 px-2 text-xs text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700"
                                   style="grid-row: {{ $franja['fila'] }};">
                                    <span>Disponible</span>
                                    <span class="font-semibold text-indigo-600 dark:text-indigo-400">Programar</span>
                                </a>
                            @elseif ($franja['estado'] === 'ocupada')
                                <div class="flex items-center rounded-md bg-gray-100 dark:bg-gray-700/50 px-2 text-xs text-gray-400 dark:text-gray-500"
                                     style="grid-row: {{ $franja['fila'] }};">
                                    Ocupado (cita oculta por los filtros)
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                {{-- Vista semanal: una columna por día (en celular, una lista por día) --}}
                <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    @foreach ($dias as $dia)
                        <div @class([
                            'bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-3 flex flex-col gap-2 text-gray-900 dark:text-gray-100',
                            'ring-2 ring-indigo-500' => $dia['fecha']->isToday(),
                        ])>
                            <a href="{{ $enlace(['vista' => 'dia', 'fecha' => $dia['fecha']->toDateString()]) }}" class="flex items-baseline justify-between hover:underline">
                                <span class="font-semibold">{{ ucfirst($dia['fecha']->locale('es')->isoFormat('dddd')) }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $dia['fecha']->format('d/m') }}</span>
                            </a>

                            @forelse ($dia['citas'] as $c)
                                <a href="{{ route('citas.mostrar', $c->id) }}"
                                   class="block rounded-md border-l-4 px-2 py-1 text-xs hover:ring-2 hover:ring-indigo-400 {{ $colores[$c->estado->nombre] ?? '' }}">
                                    <div class="font-semibold">{{ $c->horaInicioCorta() }}–{{ $c->horaFinCorta() }}</div>
                                    <div @class(['truncate font-medium', 'line-through' => $c->estado->nombre === 'cancelada'])>{{ $c->paciente->nombreCompleto() }}</div>
                                    <div class="truncate text-gray-600 dark:text-gray-300">{{ $c->motivo ?? '—' }}</div>
                                    <x-estado-cita :nombre="$c->estado->nombre" class="mt-1" />
                                </a>
                            @empty
                                <p class="text-xs text-gray-400 dark:text-gray-500">Sin citas.</p>
                            @endforelse

                            @if ($dia['fecha']->gte(today()))
                                <a href="{{ route('citas.crear', ['fecha' => $dia['fecha']->toDateString()]) }}" class="mt-auto text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    + Programar
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
