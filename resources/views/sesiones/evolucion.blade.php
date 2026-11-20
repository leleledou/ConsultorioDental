{{-- CU31 Consultar evolución del tratamiento (RF-43, RF-44): línea de tiempo de las sesiones. --}}
@php
    $paciente = $tratamiento->plan->paciente;
    $porcentaje = $tratamiento->sesiones_planificadas > 0 ? min(100, (int) round($realizadas / $tratamiento->sesiones_planificadas * 100)) : 0;
    $tonos = ['realizada' => 'exito', 'pendiente' => 'aviso', 'anulada' => 'error'];
    $cantidad = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
    $puedeRegistrar = $tratamiento->estado !== 'cancelado';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Evolución: '.$tratamiento->tratamiento->nombre"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="route('tratamientos-aplicados.mostrar', $tratamiento->id)" volver-texto="Tratamiento aplicado" />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        {{-- Encabezado: paciente, tratamiento y avance --}}
        <section class="dr-card flex flex-col gap-4 p-5 sm:p-6" aria-label="Avance del tratamiento">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="break-words font-display text-xl font-bold text-ink">{{ $tratamiento->tratamiento->nombre }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">
                        <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $paciente->nombreCompleto() }}</a>
                        · pieza {{ $tratamiento->piezaDental?->codigo_fdi ?? '—' }} · desde el {{ $tratamiento->fecha_inicio->format('d/m/Y') }}
                    </p>
                </div>
                <x-estado-clinico :estado="$tratamiento->estado" class="self-start" />
            </div>

            <div class="flex flex-col gap-4 border-t border-line pt-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="w-full sm:max-w-md">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Avance</p>
                    <p class="mt-1 font-display text-2xl font-bold text-ink">Sesión {{ $realizadas }} de {{ $tratamiento->sesiones_planificadas }}</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $porcentaje }}" aria-label="Avance del tratamiento">
                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $porcentaje }}%"></div>
                    </div>
                </div>
                @if ($puedeRegistrar)
                    <a href="{{ route('sesiones.crear', $tratamiento->id) }}" class="btn btn-primary self-start sm:self-auto">
                        <x-icon name="plus" class="h-4 w-4" /> Registrar sesión
                    </a>
                @endif
            </div>
        </section>

        {{-- Línea de tiempo, en orden cronológico ascendente --}}
        <section class="dr-card overflow-hidden" aria-labelledby="linea-tiempo">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="linea-tiempo" class="text-lg font-bold text-ink">Sesiones <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $sesiones->count() }})</span></h3>
            </header>

            @if ($sesiones->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="clock" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">El tratamiento aún no tiene sesiones registradas.</p>
                    @if ($puedeRegistrar)
                        <a href="{{ route('sesiones.crear', $tratamiento->id) }}" class="btn btn-primary mt-5"><x-icon name="plus" class="h-4 w-4" /> Registrar la primera sesión</a>
                    @endif
                </div>
            @else
                <ol class="relative space-y-6 p-5 sm:p-6">
                    @foreach ($sesiones as $sesion)
                        <li class="relative flex gap-4">
                            {{-- Marcador y línea vertical --}}
                            <div class="flex flex-col items-center" aria-hidden="true">
                                <span @class([
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full font-display text-sm font-bold tabular-nums',
                                    'bg-brand-700 text-white' => $sesion->estado === 'realizada',
                                    'bg-amber-50 text-amber-900 ring-1 ring-amber-300' => $sesion->estado === 'pendiente',
                                    'bg-gray-100 text-ink-mute ring-1 ring-line line-through' => $sesion->estado === 'anulada',
                                ])>{{ $sesion->numero_sesion }}</span>
                                @unless ($loop->last)
                                    <span class="mt-2 w-px flex-1 bg-line"></span>
                                @endunless
                            </div>

                            <article class="min-w-0 flex-1 rounded-xl border border-line p-4">
                                <header class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <h4 class="font-bold text-ink">Sesión {{ $sesion->numero_sesion }} · <span class="tabular-nums">{{ $sesion->fecha->format('d/m/Y') }}</span></h4>
                                        <p class="mt-0.5 text-sm text-ink-soft">{{ $sesion->duracion_real_minutos }} min · {{ $sesion->odontologo->nombres }} {{ $sesion->odontologo->apellidos }}</p>
                                    </div>
                                    <x-insignia :tono="$tonos[$sesion->estado] ?? 'neutro'" class="self-start">{{ ucfirst($sesion->estado) }}</x-insignia>
                                </header>

                                <dl class="mt-3 space-y-3 text-sm">
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Avance técnico</dt>
                                        <dd class="mt-0.5 whitespace-pre-line break-words text-ink">{{ $sesion->avance_tecnico ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Materiales</dt>
                                        <dd class="mt-0.5 text-ink">
                                            @if ($sesion->materiales->isEmpty())
                                                <span class="text-ink-mute">Sin materiales registrados</span>
                                            @else
                                                <ul class="flex flex-wrap gap-2">
                                                    @foreach ($sesion->materiales as $m)
                                                        <li class="rounded-lg bg-surface px-2.5 py-1 ring-1 ring-line">
                                                            {{ $m->material->nombre }}: <span class="font-semibold tabular-nums">{{ $cantidad($m->cantidad) }}</span> {{ $m->material->unidad_medida }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @endif
                                        </dd>
                                    </div>
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Próxima sesión</dt>
                                            <dd class="mt-0.5 tabular-nums text-ink">{{ $sesion->fecha_proxima_sesion?->format('d/m/Y') ?? '—' }}</dd>
                                        </div>
                                        <a href="{{ route('sesiones.mostrar', $sesion->id) }}" class="btn btn-secondary btn-sm self-start sm:self-auto">Ver sesión</a>
                                    </div>
                                </dl>
                            </article>
                        </li>
                    @endforeach
                </ol>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tratamientos-aplicados.mostrar', $tratamiento->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al tratamiento
            </a>
            <a href="{{ route('planes.mostrar', $tratamiento->plan_tratamiento_id) }}" class="btn btn-secondary">Plan de tratamiento</a>
        </div>
    </x-pagina>
</x-app-layout>
