{{-- Panel: pantalla inicial de los dos roles. Una tarjeta por paquete, filtrada por rol (RN-05), igual que el menú. --}}
@php
    // Accesos rápidos: las opciones disponibles de Pacientes y Agenda (P3), las de uso diario.
    $accesosRapidos = collect(collect($paquetes)->firstWhere('codigo', 'P3')['opciones'] ?? [])
        ->filter(fn ($opcion) => $opcion['ruta']);
    $esAdministrador = Auth::user()->rol === 'administrador';
    $rolVisible = $esAdministrador ? 'Administrador' : 'Odontólogo';

    // Dashboard: cada indicador con su etiqueta, icono y el listado o reporte al que lleva.
    $tarjetas = [
        ['valor' => $indicadores['citas_hoy'], 'etiqueta' => 'Citas programadas para hoy', 'icono' => 'calendar', 'enlace' => route('citas.listar'), 'moneda' => false],
        ['valor' => $indicadores['pacientes_mes'], 'etiqueta' => 'Pacientes atendidos este mes', 'icono' => 'users', 'enlace' => route('reportes.clinicos', [
            'modo' => 'estatico', 'tipo' => 'pacientes_atendidos',
            'fecha_inicio' => today()->startOfMonth()->toDateString(), 'fecha_fin' => today()->toDateString(),
        ]), 'moneda' => false],
        ['valor' => $indicadores['tratamientos_en_proceso'], 'etiqueta' => 'Tratamientos en proceso', 'icono' => 'activity', 'enlace' => route('planes.listar'), 'moneda' => false],
        ['valor' => $indicadores['sesiones_pendientes'], 'etiqueta' => 'Tratamientos con sesiones pendientes', 'icono' => 'clock', 'enlace' => route('reportes.clinicos'), 'moneda' => false],
        ['valor' => $indicadores['saldo_por_cobrar'], 'etiqueta' => 'Saldo por cobrar', 'icono' => 'cash', 'enlace' => route('reportes.financieros'), 'moneda' => true],
        ['valor' => $indicadores['avisos_no_leidos'], 'etiqueta' => 'Avisos sin leer', 'icono' => 'bell', 'enlace' => route('avisos.listar'), 'moneda' => false],
    ];

    // Escalas de los gráficos: la barra más alta ocupa el 100 %.
    $maxIngreso = collect($graficos['ingresos_meses'])->max('monto');
    $totalIngresos = collect($graficos['ingresos_meses'])->sum('monto');
    $maxEspecialidad = max(1, collect($graficos['tratamientos_especialidad'])->max('cantidad') ?? 1);
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="mx-auto min-w-0 max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('exito'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800" role="status">
                    {{ session('exito') }}
                </div>
            @endif

            {{-- Bienvenida --}}
            <section class="relative overflow-hidden rounded-3xl bg-brand-900 px-6 py-10 shadow-card sm:px-10 sm:py-12" aria-labelledby="bienvenida">
                <svg class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 text-brand-500/25" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                    <circle cx="100" cy="100" r="95" /><circle cx="100" cy="100" r="72" /><circle cx="100" cy="100" r="49" /><circle cx="100" cy="100" r="26" />
                </svg>

                <div class="relative max-w-2xl">
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-100 ring-1 ring-white/15">{{ $rolVisible }}</span>
                    <h1 id="bienvenida" class="mt-4 font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                        Hola, {{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}
                    </h1>
                    <p class="mt-3 text-base text-brand-100">Elija una opción de los paquetes del sistema.</p>

                    @if ($accesosRapidos->isNotEmpty())
                        <div class="mt-7 flex flex-wrap gap-3">
                            @foreach ($accesosRapidos as $opcion)
                                <a href="{{ route($opcion['ruta']) }}"
                                   class="group inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50 focus-visible:outline-white">
                                    {{ $opcion['nombre'] }}
                                    <x-icon name="arrow" class="h-4 w-4 text-brand-700 transition-transform duration-150 group-hover:translate-x-0.5" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Dashboard: seis indicadores (PanelService). Una tarjeta en cero se muestra igual, con el cero. --}}
            <section aria-labelledby="titulo-indicadores">
                <h2 id="titulo-indicadores" class="sr-only">Indicadores del consultorio</h2>
                <ul class="grid grid-cols-1 gap-4 min-[420px]:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                    @foreach ($tarjetas as $tarjeta)
                        <li class="min-w-0">
                            <a href="{{ $tarjeta['enlace'] }}"
                               class="dr-card group flex h-full flex-col gap-3 p-5 transition duration-200 hover:-translate-y-0.5 hover:border-brand-300 focus-visible:outline-brand-500">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                        <x-icon :name="$tarjeta['icono']" class="h-5 w-5" />
                                    </span>
                                    <x-icon name="arrow" class="h-4 w-4 text-brand-700 opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                                </div>
                                <p class="font-display text-3xl font-extrabold leading-none text-ink">
                                    @if ($tarjeta['moneda'])
                                        <x-moneda :monto="$tarjeta['valor']" class="text-2xl" />
                                    @else
                                        <span class="tabular-nums">{{ $tarjeta['valor'] }}</span>
                                    @endif
                                </p>
                                <p class="text-sm font-semibold leading-snug text-ink-soft">{{ $tarjeta['etiqueta'] }}</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
                @if (! $esAdministrador)
                    <p class="mt-3 text-xs text-ink-mute">Pacientes, tratamientos y sesiones muestran solo los suyos; las citas de hoy y el saldo por cobrar son de todo el consultorio.</p>
                @endif
            </section>

            {{-- Gráficos con barras de CSS (sin librerías). El dato siempre va escrito junto a la barra. --}}
            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2" aria-label="Gráficos">
                <article class="dr-card min-w-0 p-5 sm:p-6" aria-labelledby="titulo-ingresos">
                    <h2 id="titulo-ingresos" class="text-lg font-bold text-ink">Ingresos de los últimos 6 meses</h2>
                    <p class="mt-0.5 text-sm text-ink-soft">Abonos vigentes cobrados en cada mes. Total: <x-moneda :monto="$totalIngresos" class="font-semibold text-ink" /></p>

                    <ul class="mt-6 grid h-56 grid-cols-6 items-end gap-2 sm:gap-3">
                        @foreach ($graficos['ingresos_meses'] as $mes)
                            <li class="flex h-full min-w-0 flex-col items-center justify-end gap-1.5">
                                <span class="w-full truncate text-center text-[11px] font-semibold tabular-nums text-ink-soft" title="Bs {{ number_format($mes['monto'], 2, ',', '.') }}">
                                    {{ $mes['monto'] > 0 ? number_format($mes['monto'], 0, ',', '.') : '0' }}
                                </span>
                                {{-- El mes en curso, con el color de marca más intenso --}}
                                <span class="flex min-h-0 w-full flex-1 items-end justify-center" aria-hidden="true">
                                    <span @class([
                                              'block w-full max-w-12 rounded-t-md',
                                              'bg-brand-600' => $loop->last,
                                              'bg-brand-300' => ! $loop->last,
                                          ])
                                          style="height: {{ $maxIngreso > 0 ? max(2, round($mes['monto'] / $maxIngreso * 100)) : 2 }}%"></span>
                                </span>
                                <span class="text-xs font-medium text-ink">{{ $mes['etiqueta'] }}</span>
                                <span class="sr-only">: <x-moneda :monto="$mes['monto']" /></span>
                            </li>
                        @endforeach
                    </ul>
                </article>

                <article class="dr-card min-w-0 p-5 sm:p-6" aria-labelledby="titulo-especialidades">
                    <h2 id="titulo-especialidades" class="text-lg font-bold text-ink">Tratamientos por especialidad</h2>
                    <p class="mt-0.5 text-sm text-ink-soft">Tratamientos aplicados no cancelados{{ $esAdministrador ? ' del consultorio' : ' a su cargo' }}.</p>

                    @if ($graficos['tratamientos_especialidad'] === [])
                        <p class="mt-6 rounded-xl bg-surface px-4 py-10 text-center text-sm font-semibold text-ink-soft">Todavía no hay tratamientos aplicados.</p>
                    @else
                        <ul class="mt-6 space-y-3">
                            @foreach ($graficos['tratamientos_especialidad'] as $fila)
                                <li class="min-w-0">
                                    <div class="flex items-baseline justify-between gap-3 text-sm">
                                        <span class="min-w-0 truncate font-medium text-ink">{{ $fila['especialidad'] }}</span>
                                        <span class="shrink-0 font-semibold tabular-nums text-ink">{{ $fila['cantidad'] }}</span>
                                    </div>
                                    <span class="mt-1 block h-2.5 overflow-hidden rounded-full bg-brand-50" aria-hidden="true">
                                        <span class="block h-full rounded-full bg-brand-500" style="width: {{ max(2, round($fila['cantidad'] / $maxEspecialidad * 100)) }}%"></span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </article>
            </section>

            {{-- Una tarjeta por paquete --}}
            <section aria-label="Paquetes del sistema">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($paquetes as $paquete)
                        <article class="dr-card flex min-w-0 flex-col overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:border-brand-300">
                            <div class="p-6 pb-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                        <x-icon :name="$paquete['codigo']" class="h-6 w-6" />
                                    </span>
                                    <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold tracking-wide text-brand-800">
                                        {{ $paquete['codigo'] }}
                                    </span>
                                </div>
                                <h2 class="mt-4 text-lg font-bold leading-snug text-ink">{{ $paquete['nombre'] }}</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $paquete['descripcion'] }}</p>
                            </div>

                            <ul class="mt-auto space-y-0.5 border-t border-line p-2">
                                @foreach ($paquete['opciones'] as $opcion)
                                    <li>
                                        @if ($opcion['ruta'])
                                            <a href="{{ route($opcion['ruta']) }}"
                                               class="group flex items-center justify-between rounded-lg px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-brand-50 hover:text-brand-800">
                                                <span>{{ $opcion['nombre'] }}</span>
                                                <x-icon name="arrow" class="h-4 w-4 text-brand-700 opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                                            </a>
                                        @else
                                            {{-- Opción inhabilitada: en gris y sin enlace --}}
                                            <div class="flex cursor-not-allowed items-center justify-between rounded-lg px-4 py-2.5 text-sm text-ink-soft" aria-disabled="true">
                                                <span>{{ $opcion['nombre'] }}</span>
                                                <span class="rounded-full bg-surface px-2 py-0.5 text-xs font-semibold uppercase tracking-wide">Próximo ciclo</span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
