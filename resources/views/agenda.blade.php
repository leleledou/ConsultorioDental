{{-- CU13 Consultar agenda (RF-72). Vista diaria por franjas y vista semanal (lunes a sábado). Los dos roles. --}}
@php
    // Enlace a la agenda conservando la vista, la fecha y los filtros; $cambios reemplaza algunos.
    $enlace = fn (array $cambios = []) => route('agenda', array_merge(
        ['vista' => $vista, 'fecha' => $fecha->toDateString()], $filtros, $cambios
    ));

    // Aspecto de cada cita según su estado (borde y fondo de la tarjeta). Mismo significado de color que <x-estado-cita>.
    $colores = [
        'programada' => 'border-brand-500 bg-brand-50',
        'atendida' => 'border-green-600 bg-green-50',
        'cancelada' => 'border-red-500 bg-red-50 opacity-80',
        'no asistida' => 'border-amber-500 bg-amber-50',
    ];

    // Fecha anterior y siguiente: una semana o un día, según la vista.
    $anterior = $vista === 'semana' ? $fecha->copy()->subWeek() : $fecha->copy()->subDay();
    $siguiente = $vista === 'semana' ? $fecha->copy()->addWeek() : $fecha->copy()->addDay();
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="$vista === 'semana'
                                ? 'Agenda semanal — '.$lunes->format('d/m').' al '.$sabado->format('d/m/Y')
                                : 'Agenda del día — '.ucfirst($fecha->locale('es')->isoFormat('dddd DD/MM/YYYY'))"
                           subtitulo="Franjas del sillón, citas programadas y horarios disponibles." />
    </x-slot>

    <x-pagina>
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

        {{-- Navegación, cambio de vista y filtros --}}
        <section class="dr-card space-y-5 p-4 sm:p-5" aria-label="Navegación y filtros de la agenda">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ $enlace(['fecha' => $anterior->toDateString()]) }}" class="btn btn-secondary btn-sm !px-2.5"
                       aria-label="{{ $vista === 'semana' ? 'Semana anterior' : 'Día anterior' }}"><x-icon name="chevron-left" class="h-4 w-4" /></a>
                    <a href="{{ $enlace(['fecha' => today()->toDateString()]) }}" class="btn btn-secondary btn-sm">Hoy</a>
                    <a href="{{ $enlace(['fecha' => $siguiente->toDateString()]) }}" class="btn btn-secondary btn-sm !px-2.5"
                       aria-label="{{ $vista === 'semana' ? 'Semana siguiente' : 'Día siguiente' }}"><x-icon name="chevron-right" class="h-4 w-4" /></a>

                    {{-- Cambio de vista --}}
                    <div class="ms-1 inline-flex overflow-hidden rounded-lg border border-gray-300 shadow-sm" role="group" aria-label="Vista de la agenda">
                        <a href="{{ $enlace(['vista' => 'dia']) }}" @class([
                            'inline-flex items-center px-3.5 py-1.5 max-sm:min-h-11 text-[13px] font-semibold transition-colors',
                            'bg-brand-700 text-white' => $vista === 'dia',
                            'bg-white text-ink hover:bg-brand-50' => $vista !== 'dia',
                        ]) @if ($vista === 'dia') aria-current="page" @endif>Día</a>
                        <a href="{{ $enlace(['vista' => 'semana']) }}" @class([
                            'inline-flex items-center border-s border-gray-300 px-3.5 py-1.5 max-sm:min-h-11 text-[13px] font-semibold transition-colors',
                            'bg-brand-700 text-white' => $vista === 'semana',
                            'bg-white text-ink hover:bg-brand-50' => $vista !== 'semana',
                        ]) @if ($vista === 'semana') aria-current="page" @endif>Semana</a>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('citas.listar', ['fecha' => $fecha->toDateString()]) }}" class="btn btn-secondary btn-sm">
                        <x-icon name="refresh" class="h-4 w-4" /> Actualizar estados
                    </a>
                    <a href="{{ route('citas.crear', ['fecha' => $fecha->gte(today()) ? $fecha->toDateString() : today()->toDateString()]) }}"
                       class="btn btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" /> Programar cita
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('agenda') }}" class="grid grid-cols-2 gap-3 border-t border-line pt-5 lg:grid-cols-[repeat(3,minmax(0,1fr))_auto] lg:items-end">
                <input type="hidden" name="vista" value="{{ $vista }}">
                <div class="col-span-2 lg:col-span-1">
                    <x-input-label for="fecha" value="Fecha" />
                    <x-text-input id="fecha" name="fecha" type="date" class="mt-1.5 block w-full text-sm" :value="$fecha->toDateString()" />
                </div>
                <div>
                    <x-input-label for="odontologo_id" value="Odontólogo" />
                    <select id="odontologo_id" name="odontologo_id" class="dr-input mt-1.5 text-sm">
                        <option value="">Todos</option>
                        @foreach ($odontologos as $o)
                            <option value="{{ $o->id }}" @selected((string) ($filtros['odontologo_id'] ?? '') === (string) $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="estado_cita_id" value="Estado" />
                    <select id="estado_cita_id" name="estado_cita_id" class="dr-input mt-1.5 text-sm">
                        <option value="">Todos</option>
                        @foreach ($estados as $e)
                            <option value="{{ $e->id }}" @selected((string) ($filtros['estado_cita_id'] ?? '') === (string) $e->id)>{{ ucfirst($e->nombre) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2 flex items-center gap-2 lg:col-span-1">
                    <x-primary-button>Filtrar</x-primary-button>
                    @if ($filtros)
                        <a href="{{ route('agenda', ['vista' => $vista, 'fecha' => $fecha->toDateString()]) }}" class="btn btn-secondary">Quitar filtros</a>
                    @endif
                </div>
            </form>

            {{-- Leyenda de estados y total --}}
            <div class="flex flex-wrap items-center gap-2 text-xs text-ink-soft">
                <span class="font-semibold">Estados:</span>
                @foreach ($estados as $e)
                    <x-estado-cita :nombre="$e->nombre" />
                @endforeach
                <span class="ms-auto font-semibold text-ink">{{ $citas->count() }} cita(s) en {{ $vista === 'semana' ? 'la semana' : 'el día' }}.</span>
            </div>
        </section>

        @if ($vista === 'dia')
            {{-- Vista diaria: una fila por franja; cada cita ocupa las filas de su duración --}}
            @php
                // Solo presentación: cada cita se asigna a la primera columna libre de sus filas (las canceladas van al costado).
                $colOcupadas = [];
                $colocados = [];
                foreach ($bloques as $b) {
                    $filasB = range($b['fila'], $b['fila'] + $b['filas'] - 1);
                    for ($col = 0; array_intersect($filasB, $colOcupadas[$col] ?? []); $col++);
                    $colOcupadas[$col] = array_merge($colOcupadas[$col] ?? [], $filasB);
                    $colocados[] = $b + ['col' => $col];
                }
                // Columnas ya usadas por alguna cita en una fila.
                $usadasEn = fn (int $fila) => collect($colOcupadas)->filter(fn ($filas) => in_array($fila, $filas))->keys()->all();
                // Total de columnas: las de las citas, más una si alguna franja sin cita comparte fila con una cita al costado.
                $nCols = max(count($colOcupadas), 1);
                foreach ($franjas as $franja) {
                    if ($franja['estado'] && ($usados = $usadasEn($franja['fila']))) {
                        $nCols = max($nCols, max($usados) + 2);
                    }
                }
            @endphp

            <section class="dr-card p-3 sm:p-5" aria-label="Franjas del día">
                {{-- Móvil y tablet vertical (< 768 px): lista vertical; cada franja apila sus citas y, si está libre, su enlace «Disponible».
                     Así ninguna franja se angosta por las citas simultáneas. --}}
                <div class="space-y-1.5 md:hidden">
                    @foreach ($franjas as $franja)
                        @php $citasFila = collect($colocados)->where('fila', $franja['fila']); @endphp
                        @continue($citasFila->isEmpty() && ! $franja['estado'])
                        <div class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-2">
                            <div class="border-t border-line pt-2 text-xs font-semibold tabular-nums text-ink-soft">{{ $franja['hora'] }}</div>
                            <div class="min-w-0 space-y-1.5">
                                @foreach ($citasFila as $b)
                                    <x-agenda.cita :cita="$b['cita']" />
                                @endforeach

                                @if ($franja['estado'] === 'libre')
                                    <a href="{{ route('citas.crear', ['fecha' => $fecha->toDateString(), 'hora_inicio' => $franja['hora']]) }}"
                                       class="group flex min-h-11 items-center justify-between gap-2 rounded-lg border border-dashed border-brand-200 px-3 py-2 text-xs text-ink-soft transition-colors hover:border-brand-500 hover:bg-brand-50">
                                        <span>Disponible</span>
                                        <span class="inline-flex items-center gap-1 font-semibold text-brand-700 group-hover:text-brand-900"><x-icon name="plus" class="h-3.5 w-3.5" /> Programar</span>
                                    </a>
                                @elseif ($franja['estado'] === 'ocupada')
                                    <div class="flex min-h-11 items-center rounded-lg bg-surface px-3 py-2 text-xs text-ink-soft">Ocupado (cita oculta por los filtros)</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Desde 768 px: rejilla por franjas; cada cita ocupa las filas de su duración y las simultáneas van en columnas --}}
                <div class="hidden gap-x-3 gap-y-1.5 md:grid"
                     style="grid-template-columns: 3.25rem repeat({{ $nCols }}, minmax(0, 1fr)); grid-template-rows: repeat({{ $totalFilas }}, minmax(2.75rem, auto));">
                    {{-- Hora de cada franja (primera columna) --}}
                    @foreach ($franjas as $franja)
                        <div class="border-t border-line pt-1.5 text-xs font-semibold tabular-nums text-ink-soft"
                             style="grid-column: 1; grid-row: {{ $franja['fila'] }};">
                            {{ $franja['hora'] }}
                        </div>
                    @endforeach

                    {{-- Citas (las canceladas al final, se dibujan al costado) --}}
                    @foreach ($colocados as $b)
                        <x-agenda.cita :cita="$b['cita']" style="grid-column: {{ $b['col'] + 2 }}; grid-row: {{ $b['fila'] }} / span {{ $b['filas'] }};" />
                    @endforeach

                    {{-- Franjas sin cita visible: disponibles (con enlace a CU10) u ocupadas por citas filtradas --}}
                    @foreach ($franjas as $franja)
                        @php
                            // Sin citas en esa fila: ocupa todo el ancho; si hay una cita al costado, va en la primera columna libre (más corta).
                            $usadas = $usadasEn($franja['fila']);
                            $primeraLibre = collect(range(0, $nCols - 1))->first(fn ($c) => ! in_array($c, $usadas));
                            $columnaFranja = $usadas ? $primeraLibre + 2 : 2;
                            $anchoFranja = $usadas ? 1 : $nCols;
                        @endphp
                        @if ($franja['estado'] === 'libre')
                            <a href="{{ route('citas.crear', ['fecha' => $fecha->toDateString(), 'hora_inicio' => $franja['hora']]) }}"
                               class="group flex min-w-0 items-center justify-between gap-2 rounded-lg border border-dashed border-brand-200 px-3 text-xs text-ink-soft transition-colors hover:border-brand-500 hover:bg-brand-50"
                               style="grid-column: {{ $columnaFranja }} / span {{ $anchoFranja }}; grid-row: {{ $franja['fila'] }};">
                                <span class="truncate">Disponible</span>
                                <span class="inline-flex shrink-0 items-center gap-1 font-semibold text-brand-700 group-hover:text-brand-900">
                                    <x-icon name="plus" class="h-3.5 w-3.5" /> <span @class(['sr-only' => $usadas])>Programar</span>
                                </span>
                            </a>
                        @elseif ($franja['estado'] === 'ocupada')
                            <div class="flex min-w-0 items-center rounded-lg bg-surface px-3 text-xs text-ink-soft"
                                 style="grid-column: {{ $columnaFranja }} / span {{ $anchoFranja }}; grid-row: {{ $franja['fila'] }};">
                                <span class="truncate">Ocupado (cita oculta por los filtros)</span>
                            </div>
                        @endif
                    @endforeach
                </div>

                @if ($citas->isEmpty())
                    <p class="mt-4 rounded-lg bg-surface px-4 py-3 text-center text-sm text-ink-soft">No hay citas en este día{{ $filtros ? ' con los filtros aplicados' : '' }}.</p>
                @endif
            </section>
        @else
            {{-- Vista semanal: una columna por día (en celular, una lista por día) --}}
            <section class="grid grid-cols-1 gap-4 md:grid-cols-3 min-[1400px]:grid-cols-6" aria-label="Semana">
                @foreach ($dias as $dia)
                    <div @class([
                        'dr-card flex min-w-0 flex-col gap-2 p-3',
                        '!border-brand-500 ring-2 ring-brand-200' => $dia['fecha']->isToday(),
                    ])>
                        <a href="{{ $enlace(['vista' => 'dia', 'fecha' => $dia['fecha']->toDateString()]) }}" class="block border-b border-line pb-2 hover:text-brand-800">
                            <span class="block font-display font-bold text-ink">{{ ucfirst($dia['fecha']->locale('es')->isoFormat('dddd')) }}</span>
                            <span class="mt-0.5 flex items-center gap-2 text-xs font-semibold tabular-nums text-ink-soft">
                                {{ $dia['fecha']->format('d/m') }}
                                @if ($dia['fecha']->isToday()) <span class="rounded-full bg-brand-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">Hoy</span> @endif
                            </span>
                        </a>

                        @forelse ($dia['citas'] as $c)
                            <a href="{{ route('citas.mostrar', $c->id) }}"
                               class="block rounded-lg border-l-4 px-2.5 py-1.5 text-xs shadow-sm transition hover:shadow-card hover:ring-2 hover:ring-brand-300 {{ $colores[$c->estado->nombre] ?? 'border-gray-400 bg-gray-50' }}">
                                <div class="font-bold tabular-nums text-ink">{{ $c->horaInicioCorta() }}–{{ $c->horaFinCorta() }}</div>
                                <div @class(['break-words font-semibold text-ink', 'line-through' => $c->estado->nombre === 'cancelada'])>{{ $c->paciente->nombreCompleto() }}</div>
                                <div class="break-words text-ink-soft">{{ $c->motivo ?? '—' }}</div>
                                <x-estado-cita :nombre="$c->estado->nombre" class="mt-1.5" />
                            </a>
                        @empty
                            <p class="py-2 text-xs text-ink-soft">Sin citas.</p>
                        @endforelse

                        @if ($dia['fecha']->gte(today()))
                            <a href="{{ route('citas.crear', ['fecha' => $dia['fecha']->toDateString()]) }}" class="mt-auto inline-flex items-center gap-1 pt-1 max-sm:min-h-11 text-xs font-semibold text-brand-700 hover:text-brand-900 hover:underline">
                                <x-icon name="plus" class="h-3.5 w-3.5" /> Programar
                            </a>
                        @endif
                    </div>
                @endforeach
            </section>
        @endif
    </x-pagina>
</x-app-layout>
