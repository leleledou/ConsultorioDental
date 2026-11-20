{{-- CU24 Consultar historia clínica consolidada (RF-13). Solo lectura: los datos los arma HistoriaClinicaService. --}}
@php
    $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
    $tratamientos = $planes->flatMap->tratamientosAplicados;
    $porcentaje = fn ($t) => $t->sesiones_planificadas > 0 ? min(100, (int) round($t->sesiones_registradas / $t->sesiones_planificadas * 100)) : 0;

    // Miniatura del odontograma: mismo esquema FDI que la ficha del odontograma.
    $piezas = $ultimoOdontograma?->detalles->keyBy('pieza_dental_id') ?? collect();
    $arcadas = [[range(18, 11), range(21, 28)], [range(48, 41), range(31, 38)]];
    if ($ultimoOdontograma && $ultimoOdontograma->tipo_denticion !== 'permanente') {
        $arcadas[] = [range(55, 51), range(61, 65)];
        $arcadas[] = [range(85, 81), range(71, 75)];
    }

    $secciones = [
        'paciente' => 'Paciente',
        'antecedentes' => 'Antecedentes',
        'odontograma' => 'Odontograma',
        'planes' => 'Planes',
        'tratamientos' => 'Tratamientos',
        'estudios' => 'Estudios',
        'valoraciones' => 'Implantes',
        'cuenta' => 'Estado de cuenta',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Historia consolidada: '.$paciente->nombreCompleto()"
                           subtitulo="Resumen clínico y financiero completo del paciente."
                           :volver="route('pacientes.mostrar', $paciente->id)" volver-texto="Paciente" />
    </x-slot>

    <x-pagina>
        {{-- Índice de secciones --}}
        <nav class="flex flex-wrap gap-2" aria-label="Secciones de la historia">
            @foreach ($secciones as $ancla => $texto)
                <a href="#{{ $ancla }}" class="btn btn-secondary btn-sm">{{ $texto }}</a>
            @endforeach
        </nav>

        {{-- 1. Datos del paciente --}}
        <section id="paciente" class="dr-card scroll-mt-24 p-5 sm:p-6" aria-labelledby="titulo-paciente">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                    <div class="min-w-0">
                        <h3 id="titulo-paciente" class="break-words font-display text-xl font-bold text-ink">{{ $paciente->nombreCompleto() }}</h3>
                        <p class="mt-0.5 text-sm text-ink-soft">CI {{ $paciente->ci }} · {{ $paciente->edad() }} años · Historia N.º {{ $historia->id }}</p>
                    </div>
                </div>
                <x-insignia-activo :activo="$paciente->estado === 'activo'" class="self-start sm:self-center" />
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <x-dato etiqueta="Fecha de nacimiento">{{ $paciente->fecha_nacimiento->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Sexo">{{ $paciente->sexo === 'F' ? 'Femenino' : 'Masculino' }}</x-dato>
                <x-dato etiqueta="Teléfono">{{ $paciente->telefono ?? '—' }}</x-dato>
                <x-dato etiqueta="Apertura de la historia">{{ $historia->fecha_apertura->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Hábitos" class="sm:col-span-2">
                    <span class="whitespace-pre-line">{{ $historia->habitos ?? '—' }}</span>
                </x-dato>
                <x-dato etiqueta="Observaciones generales" class="sm:col-span-2">
                    <span class="whitespace-pre-line">{{ $historia->observaciones_generales ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- 2. Antecedentes médicos --}}
        <section id="antecedentes" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-antecedentes">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="titulo-antecedentes" class="text-lg font-bold text-ink">Antecedentes médicos <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $historia->antecedentes->count() }})</span></h3>
                <a href="{{ route('historias.mostrar', $paciente->id) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Gestionar historia clínica</a>
            </header>

            @if ($historia->antecedentes->isEmpty())
                <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">La historia no tiene antecedentes registrados.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($historia->antecedentes as $a)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $a->nombre }}</p>
                                <p class="mt-0.5 whitespace-pre-line break-words text-sm text-ink-soft">{{ $a->pivot->observacion ?? 'Sin observación' }}</p>
                            </div>
                            <p class="shrink-0 text-sm tabular-nums text-ink-soft">{{ \Carbon\Carbon::parse($a->pivot->fecha_registro)->format('d/m/Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- 3. Odontograma: miniatura del último activo --}}
        <section id="odontograma" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-odontograma">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <div>
                    <h3 id="titulo-odontograma" class="text-lg font-bold text-ink">Odontograma</h3>
                    <p class="mt-0.5 text-sm text-ink-soft">{{ $totalOdontogramas }} {{ $totalOdontogramas === 1 ? 'odontograma registrado' : 'odontogramas registrados' }}</p>
                </div>
                <div class="flex flex-col gap-2 min-[420px]:flex-row">
                    <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="btn btn-secondary btn-sm">Ver odontogramas</a>
                    <a href="{{ route('odontogramas.comparar', $paciente->id) }}" class="btn btn-secondary btn-sm"><x-icon name="refresh" class="h-4 w-4" /> Comparar</a>
                </div>
            </header>

            @if (! $ultimoOdontograma)
                <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">El paciente no tiene odontogramas activos.</p>
            @else
                <div class="p-5 sm:p-6">
                    <p class="text-sm text-ink-soft">
                        Último: <a href="{{ route('odontogramas.mostrar', $ultimoOdontograma->id) }}" class="font-semibold text-brand-700 hover:text-brand-900 hover:underline">{{ ucfirst($ultimoOdontograma->tipo) }} del {{ $ultimoOdontograma->fecha_registro->format('d/m/Y') }}</a>
                        · {{ ucfirst($ultimoOdontograma->tipo_denticion) }} · {{ $ultimoOdontograma->odontologo->nombres }} {{ $ultimoOdontograma->odontologo->apellidos }}
                    </p>

                    <div class="mt-4 overflow-x-auto">
                        <div class="mx-auto w-max space-y-2" role="img" aria-label="Miniatura del último odontograma">
                            @foreach ($arcadas as $cuadrantes)
                                <div @class(['flex items-center justify-center gap-2', 'mt-3 border-t border-dashed border-line pt-3' => $loop->index === 2])>
                                    @foreach ($cuadrantes as $fila)
                                        @if (! $loop->first)
                                            <span class="h-6 w-px bg-ink-soft/40" aria-hidden="true"></span>
                                        @endif
                                        <div class="flex gap-0.5">
                                            @foreach ($fila as $fdi)
                                                @php $d = $piezas->get($fdi); @endphp
                                                <span @class([
                                                          'flex h-6 w-6 items-center justify-center rounded text-[10px] font-semibold tabular-nums',
                                                          'border border-black/10 text-ink' => $d,
                                                          'border border-dashed border-line text-ink-mute' => ! $d,
                                                      ])
                                                      @if ($d) style="background-color: {{ $d->estadoPieza->color_referencia ?? '#E1E8EB' }}; color: transparent;" @endif
                                                      title="{{ $fdi }} · {{ $d?->estadoPieza->nombre ?? 'No registrada' }}">{{ $d ? '' : $fdi }}</span>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Resumen por estado (el color nunca va solo) --}}
                    <ul class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-1 text-xs text-ink-soft">
                        @foreach ($ultimoOdontograma->detalles->groupBy('estado_pieza_id') as $grupo)
                            <li class="flex items-center gap-1.5">
                                <span class="h-3 w-3 rounded-sm border border-black/10" style="background-color: {{ $grupo->first()->estadoPieza->color_referencia ?? '#E1E8EB' }};" aria-hidden="true"></span>
                                {{ $grupo->first()->estadoPieza->nombre }}: <span class="font-semibold tabular-nums text-ink">{{ $grupo->count() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </section>

        {{-- 4. Planes de tratamiento --}}
        <section id="planes" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-planes">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="titulo-planes" class="text-lg font-bold text-ink">Planes de tratamiento <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $planes->count() }})</span></h3>
                <a href="{{ route('planes.listar', ['paciente_id' => $paciente->id]) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Ver planes</a>
            </header>

            @if ($planes->isEmpty())
                <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">El paciente no tiene planes de tratamiento.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($planes as $plan)
                        <li class="flex flex-col gap-2 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <a href="{{ route('planes.mostrar', $plan->id) }}" class="font-semibold text-brand-700 hover:text-brand-900 hover:underline">Plan del {{ $plan->fecha_creacion->format('d/m/Y') }}</a>
                                <p class="mt-0.5 text-sm text-ink-soft">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }} · {{ $plan->tratamientosAplicados->count() }} {{ $plan->tratamientosAplicados->count() === 1 ? 'tratamiento' : 'tratamientos' }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="font-semibold text-ink"><x-moneda :monto="$plan->costo_total" /></span>
                                <x-estado-clinico :estado="$plan->estado" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- 5. Tratamientos aplicados con su avance --}}
        <section id="tratamientos" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-tratamientos">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="titulo-tratamientos" class="text-lg font-bold text-ink">Tratamientos aplicados <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $tratamientos->count() }})</span></h3>
            </header>

            @if ($tratamientos->isEmpty())
                <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">El paciente no tiene tratamientos aplicados.</p>
            @else
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2">
                    @foreach ($tratamientos as $t)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 break-words font-display text-base font-bold text-ink">{{ $t->tratamiento->nombre }}</p>
                                <x-estado-clinico :estado="$t->estado" />
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Pieza</dt><dd class="font-medium tabular-nums text-ink">{{ $t->piezaDental?->codigo_fdi ?? '—' }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Costo acordado</dt><dd class="font-semibold text-ink"><x-moneda :monto="$t->costo_acordado" /></dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Diagnóstico</dt><dd class="break-words font-medium text-ink">{{ $t->diagnostico->nombre }}</dd></div>
                                <div class="col-span-2 min-w-0">
                                    <dt class="text-xs text-ink-soft">Avance</dt>
                                    <dd class="font-medium text-ink">Sesión {{ $t->sesiones_registradas }} de {{ $t->sesiones_planificadas }}
                                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-brand-100" aria-hidden="true"><span class="block h-full rounded-full bg-brand-600" style="width: {{ $porcentaje($t) }}%"></span></span>
                                    </dd>
                                </div>
                            </dl>
                            <div class="flex flex-col gap-2 min-[420px]:flex-row">
                                <a href="{{ route('sesiones.evolucion', $t->id) }}" class="btn btn-secondary btn-sm flex-1"><x-icon name="clock" class="h-4 w-4" /> Ver evolución</a>
                                <a href="{{ route('tratamientos-aplicados.mostrar', $t->id) }}" class="btn btn-secondary btn-sm flex-1">Abrir tratamiento</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            {{-- 6. Estudios diagnósticos --}}
            <section id="estudios" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-estudios">
                <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <h3 id="titulo-estudios" class="text-lg font-bold text-ink">Estudios diagnósticos <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $estudios->count() }})</span></h3>
                    <a href="{{ route('estudios.listar', $paciente->id) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Ver estudios</a>
                </header>

                @if ($estudios->isEmpty())
                    <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">El paciente no tiene estudios diagnósticos.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($estudios as $e)
                            <li class="px-5 py-3 sm:px-6">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('estudios.mostrar', $e->id) }}" class="min-w-0 break-words font-semibold text-brand-700 hover:text-brand-900 hover:underline">{{ $e->tipoEstudio->nombre }}</a>
                                    <p class="shrink-0 text-sm tabular-nums text-ink-soft">{{ $e->fecha_estudio->format('d/m/Y') }}</p>
                                </div>
                                <p class="mt-0.5 text-sm text-ink-soft">
                                    Pieza {{ $e->pieza?->codigo_fdi ?? '—' }}@if ($e->centro_radiologico) · {{ $e->centro_radiologico }}@endif
                                </p>
                                @if ($e->hallazgos)
                                    <p class="mt-1 whitespace-pre-line break-words text-sm text-ink">{{ $e->hallazgos }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- 7. Valoraciones para implante --}}
            <section id="valoraciones" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-valoraciones">
                <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <h3 id="titulo-valoraciones" class="text-lg font-bold text-ink">Valoraciones para implante <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $valoraciones->count() }})</span></h3>
                    <a href="{{ route('valoraciones.listar', $paciente->id) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Ver valoraciones</a>
                </header>

                @if ($valoraciones->isEmpty())
                    <p class="px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">El paciente no tiene valoraciones para implante.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($valoraciones as $v)
                            <li class="px-5 py-3 sm:px-6">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('valoraciones.mostrar', $v->id) }}" class="min-w-0 font-semibold text-brand-700 hover:text-brand-900 hover:underline">Pieza {{ $v->pieza->codigo_fdi }} · <span class="font-normal text-ink-soft">{{ $v->pieza->nombre }}</span></a>
                                    <x-valoraciones.resultado :resultado="$v->resultado" />
                                </div>
                                <p class="mt-0.5 text-sm text-ink-soft">
                                    <span class="tabular-nums">{{ $v->fecha_valoracion->format('d/m/Y') }}</span> · hueso {{ $v->estado_hueso }}{{ $v->requiere_injerto ? ' · requiere injerto' : '' }}
                                </p>
                                @if ($v->observaciones_clinicas)
                                    <p class="mt-1 whitespace-pre-line break-words text-sm text-ink">{{ $v->observaciones_clinicas }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- 8. Estado de cuenta (mismo cálculo que CU36) --}}
        <section id="cuenta" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-cuenta">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="titulo-cuenta" class="text-lg font-bold text-ink">Estado de cuenta</h3>
                <a href="{{ route('pagos.cuenta', $paciente->id) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Ver estado de cuenta</a>
            </header>

            <dl class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-3 sm:p-6">
                <div class="rounded-xl bg-surface p-4 ring-1 ring-line">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Costo total</dt>
                    <dd class="mt-1 font-display text-2xl font-bold text-ink"><x-moneda :monto="$cuenta['costo_total']" /></dd>
                </div>
                <div class="rounded-xl bg-green-50 p-4 ring-1 ring-green-200">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-green-800">Total abonado</dt>
                    <dd class="mt-1 font-display text-2xl font-bold text-green-900"><x-moneda :monto="$cuenta['abonado']" /></dd>
                </div>
                <div @class(['rounded-xl p-4 ring-1', 'bg-amber-50 ring-amber-300' => $cuenta['saldo'] > 0, 'bg-surface ring-line' => $cuenta['saldo'] <= 0])>
                    <dt @class(['text-xs font-semibold uppercase tracking-wide', 'text-amber-900' => $cuenta['saldo'] > 0, 'text-ink-soft' => $cuenta['saldo'] <= 0])>Saldo pendiente</dt>
                    <dd class="mt-1 flex flex-wrap items-center gap-2 font-display text-2xl font-bold text-ink">
                        <x-moneda :monto="$cuenta['saldo']" />
                        @if ($cuenta['saldo'] <= 0 && $cuenta['costo_total'] > 0)
                            <x-insignia tono="exito">Saldado</x-insignia>
                        @endif
                    </dd>
                </div>
            </dl>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
        </div>
    </x-pagina>
</x-app-layout>
