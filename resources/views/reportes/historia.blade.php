{{-- RF-84 Historia clínica imprimible: documento por paciente (ReporteService::historiaClinica) y exportación a PDF por CU45. --}}
@php
    $paciente = $datos['paciente'];
    $historia = $datos['historia'];
    $odontograma = $datos['ultimoOdontograma'];
    $cuenta = $datos['cuenta'];
    $exportar = route('reportes.exportar', ['reporte' => 'historia', 'paciente' => $paciente->id, 'formato' => 'pdf']);
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Historia clínica: '.$paciente->nombreCompleto()"
                           subtitulo="Documento imprimible con todo el registro clínico y financiero del paciente."
                           :volver="route('historias.consolidada', $paciente->id)" volver-texto="Historia consolidada" />
    </x-slot>

    <x-pagina>
        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <div class="flex flex-col gap-3 min-[420px]:flex-row min-[420px]:items-center min-[420px]:justify-between">
            <p class="text-sm text-ink-soft">Generada el <span class="font-semibold tabular-nums text-ink">{{ now()->format('d/m/Y H:i') }}</span></p>
            <a href="{{ $exportar }}" class="btn btn-primary"><x-icon name="download" class="h-4 w-4" /> Exportar a PDF</a>
        </div>

        {{-- Paciente --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="h-paciente">
            <h3 id="h-paciente" class="text-lg font-bold text-ink">Datos del paciente</h3>
            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <x-dato etiqueta="Paciente">{{ $paciente->nombreCompleto() }}</x-dato>
                <x-dato etiqueta="CI">{{ $paciente->ci }}</x-dato>
                <x-dato etiqueta="Fecha de nacimiento">{{ $paciente->fecha_nacimiento->format('d/m/Y') }} ({{ $paciente->edad() }} años)</x-dato>
                <x-dato etiqueta="Sexo">{{ $paciente->sexo === 'F' ? 'Femenino' : 'Masculino' }}</x-dato>
                <x-dato etiqueta="Teléfono">{{ $paciente->telefono ?? '—' }}</x-dato>
                <x-dato etiqueta="Historia N.º">{{ $historia->id }}</x-dato>
                <x-dato etiqueta="Apertura">{{ $historia->fecha_apertura->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Hábitos"><span class="whitespace-pre-line">{{ $historia->habitos ?? '—' }}</span></x-dato>
                <x-dato etiqueta="Observaciones generales" class="sm:col-span-2 lg:col-span-4 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $historia->observaciones_generales ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- Antecedentes --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-antecedentes">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-antecedentes" class="text-lg font-bold text-ink">Antecedentes médicos</h3>
            </header>
            @if ($historia->antecedentes->isEmpty())
                <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin antecedentes registrados.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($historia->antecedentes as $a)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $a->nombre }}</p>
                                <p class="whitespace-pre-line break-words text-sm text-ink-soft">{{ $a->pivot->observacion ?? 'Sin observación' }}</p>
                            </div>
                            <p class="shrink-0 text-sm tabular-nums text-ink-soft">{{ \Carbon\Carbon::parse($a->pivot->fecha_registro)->format('d/m/Y') }}</p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Último odontograma, dibujado por cuadrantes --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-odontograma">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-odontograma" class="text-lg font-bold text-ink">Odontograma</h3>
                @if ($odontograma)
                    <p class="mt-0.5 text-sm text-ink-soft">
                        {{ ucfirst($odontograma->tipo) }} del {{ $odontograma->fecha_registro->format('d/m/Y') }} · {{ ucfirst($odontograma->tipo_denticion) }}
                        · {{ $odontograma->odontologo->nombres }} {{ $odontograma->odontologo->apellidos }}
                    </p>
                @endif
            </header>
            @if (! $odontograma)
                <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">El paciente no tiene odontogramas activos.</p>
            @else
                <div class="p-5 sm:p-6">
                    @include('reportes.partials.odontograma', ['dibujo' => $datos['dibujo'], 'titulo' => 'Último odontograma'])
                </div>
            @endif
        </section>

        {{-- Planes --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-planes">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-planes" class="text-lg font-bold text-ink">Planes de tratamiento</h3>
            </header>
            @if ($datos['planes']->isEmpty())
                <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin planes de tratamiento.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($datos['planes'] as $plan)
                        <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <p class="min-w-0 text-ink"><span class="font-semibold">Plan del {{ $plan->fecha_creacion->format('d/m/Y') }}</span>
                                <span class="text-sm text-ink-soft">· {{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</span></p>
                            <div class="flex items-center gap-3">
                                <x-moneda :monto="$plan->costo_total" class="font-semibold text-ink" />
                                <x-estado-clinico :estado="$plan->estado" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Tratamientos con avance y sesiones realizadas --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-tratamientos">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-tratamientos" class="text-lg font-bold text-ink">Tratamientos aplicados y sesiones</h3>
            </header>
            @if ($datos['tratamientos']->isEmpty())
                <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin tratamientos aplicados.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($datos['tratamientos'] as $t)
                        @php $sesiones = $datos['sesiones']->get($t->id, collect()); @endphp
                        <li class="px-5 py-4 sm:px-6">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="font-display font-bold text-ink">{{ $t->tratamiento->nombre }}</p>
                                    <p class="mt-0.5 text-sm text-ink-soft">
                                        Pieza {{ $t->piezaDental?->codigo_fdi ?? '—' }} · {{ $t->diagnostico->nombre }} · desde {{ $t->fecha_inicio->format('d/m/Y') }}
                                    </p>
                                    <p class="mt-0.5 text-sm text-ink">Avance: <span class="font-semibold">Sesión {{ $t->sesiones_registradas }} de {{ $t->sesiones_planificadas }}</span></p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <x-moneda :monto="$t->costo_acordado" class="font-semibold text-ink" />
                                    <x-estado-clinico :estado="$t->estado" />
                                </div>
                            </div>

                            @if ($sesiones->isNotEmpty())
                                <ol class="mt-3 space-y-1.5 border-l-2 border-brand-100 pl-4 text-sm">
                                    @foreach ($sesiones as $s)
                                        <li class="min-w-0">
                                            <span class="font-semibold tabular-nums text-ink">{{ $s->fecha->format('d/m/Y') }}</span>
                                            <span class="text-ink-soft">· Sesión {{ $s->numero_sesion }} · {{ $s->odontologo->nombres }} {{ $s->odontologo->apellidos }}</span>
                                            @if ($s->avance_tecnico)
                                                <p class="whitespace-pre-line break-words text-ink">{{ $s->avance_tecnico }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            {{-- Estudios --}}
            <section class="dr-card overflow-hidden" aria-labelledby="h-estudios">
                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="h-estudios" class="text-lg font-bold text-ink">Estudios diagnósticos</h3>
                </header>
                @if ($datos['estudios']->isEmpty())
                    <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin estudios diagnósticos.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($datos['estudios'] as $e)
                            <li class="px-5 py-3 sm:px-6">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 break-words font-semibold text-ink">{{ $e->tipoEstudio->nombre }}</p>
                                    <p class="shrink-0 text-sm tabular-nums text-ink-soft">{{ $e->fecha_estudio->format('d/m/Y') }}</p>
                                </div>
                                <p class="text-sm text-ink-soft">Pieza {{ $e->pieza?->codigo_fdi ?? '—' }}@if ($e->centro_radiologico) · {{ $e->centro_radiologico }}@endif</p>
                                @if ($e->hallazgos)
                                    <p class="mt-1 whitespace-pre-line break-words text-sm text-ink">{{ $e->hallazgos }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Valoraciones --}}
            <section class="dr-card overflow-hidden" aria-labelledby="h-valoraciones">
                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="h-valoraciones" class="text-lg font-bold text-ink">Valoraciones para implante</h3>
                </header>
                @if ($datos['valoraciones']->isEmpty())
                    <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin valoraciones para implante.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($datos['valoraciones'] as $v)
                            <li class="px-5 py-3 sm:px-6">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="min-w-0 font-semibold text-ink">Pieza {{ $v->pieza->codigo_fdi }} <span class="font-normal text-ink-soft">· {{ $v->pieza->nombre }}</span></p>
                                    <x-valoraciones.resultado :resultado="$v->resultado" />
                                </div>
                                <p class="text-sm text-ink-soft"><span class="tabular-nums">{{ $v->fecha_valoracion->format('d/m/Y') }}</span> · hueso {{ $v->estado_hueso }}{{ $v->requiere_injerto ? ' · requiere injerto' : '' }}</p>
                                @if ($v->observaciones_clinicas)
                                    <p class="mt-1 whitespace-pre-line break-words text-sm text-ink">{{ $v->observaciones_clinicas }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        {{-- Controles --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-controles">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-controles" class="text-lg font-bold text-ink">Controles</h3>
            </header>
            @if ($datos['controles']->isEmpty())
                <p class="px-5 py-6 text-sm text-ink-soft sm:px-6">Sin controles programados.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($datos['controles'] as $control)
                        <li class="flex flex-col gap-1 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="font-semibold text-ink">{{ $control->tratamientoAplicado->tratamiento->nombre }}</p>
                                <p class="break-words text-sm text-ink-soft">{{ $control->motivo ?? 'Sin motivo indicado' }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-sm tabular-nums text-ink-soft">{{ $control->fecha_prevista->format('d/m/Y') }}</span>
                                <x-controles.estado :estado="$control->estado" />
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Estado de cuenta (mismo cálculo que CU36) --}}
        <section class="dr-card overflow-hidden" aria-labelledby="h-cuenta">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="h-cuenta" class="text-lg font-bold text-ink">Estado de cuenta</h3>
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
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Saldo pendiente</dt>
                    <dd class="mt-1 font-display text-2xl font-bold text-ink"><x-moneda :monto="$cuenta['saldo']" /></dd>
                </div>
            </dl>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('historias.consolidada', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a la historia consolidada
            </a>
            <a href="{{ $exportar }}" class="btn btn-primary"><x-icon name="download" class="h-4 w-4" /> Exportar a PDF</a>
        </div>
    </x-pagina>
</x-app-layout>
