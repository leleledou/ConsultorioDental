{{-- CU27 Abrir plan de tratamiento: datos del plan y sus tratamientos aplicados. --}}
@php
    $activo = $plan->estado === 'activo';

    // Avance de un tratamiento: sesiones realizadas de las planificadas (solo presentación).
    $avance = fn ($t) => [
        'hechas' => $t->sesiones->where('estado', 'realizada')->count(),
        'total' => $t->sesiones_planificadas,
    ];
    $porcentaje = fn (array $a) => $a['total'] > 0 ? min(100, (int) round($a['hechas'] / $a['total'] * 100)) : 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Plan de tratamiento: '.$plan->paciente->nombreCompleto()"
                           subtitulo="Datos del plan y tratamientos que lo componen."
                           :volver="route('planes.listar', ['paciente_id' => $plan->paciente_id])" volver-texto="Planes del paciente" />
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

        {{-- Datos del plan --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-plan">
            <h3 id="datos-plan" class="text-lg font-bold text-ink">Datos del plan</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Paciente">
                    <a href="{{ route('pacientes.mostrar', $plan->paciente_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $plan->paciente->nombreCompleto() }}</a>
                </x-dato>
                <x-dato etiqueta="Odontólogo responsable">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</x-dato>
                <x-dato etiqueta="Fecha de creación">{{ $plan->fecha_creacion->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Costo total">
                    <span class="font-display text-xl font-bold text-ink"><x-moneda :monto="$plan->costo_total" /></span>
                </x-dato>
                <x-dato etiqueta="Estado"><x-estado-clinico :estado="$plan->estado" /></x-dato>
                <x-dato etiqueta="Descripción" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $plan->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- Tratamientos del plan --}}
        <section class="dr-card overflow-hidden" aria-labelledby="tratamientos-plan">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="tratamientos-plan" class="text-lg font-bold text-ink">Tratamientos del plan <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $tratamientos->count() }})</span></h3>
                @if ($activo)
                    <a href="{{ route('tratamientos-aplicados.crear', $plan->id) }}" class="btn btn-primary btn-sm self-start sm:self-auto">
                        <x-icon name="plus" class="h-4 w-4" /> Agregar tratamiento
                    </a>
                @endif
            </header>

            @if ($tratamientos->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">El plan no tiene tratamientos registrados.</p>
                </div>
            @else
                {{-- Tabla (desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Tratamientos del plan</caption>
                        <thead>
                            <tr>
                                <th scope="col">Tratamiento</th>
                                <th scope="col">Pieza</th>
                                <th scope="col">Diagnóstico</th>
                                <th scope="col" class="text-right">Costo acordado</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Avance</th>
                                <th scope="col"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tratamientos as $t)
                                @php $a = $avance($t); @endphp
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">{{ $t->tratamiento->nombre }}</td>
                                    {{-- El id de la pieza es su código FDI --}}
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $t->pieza_dental_id ?? '—' }}</td>
                                    <td class="text-ink-soft">{{ $t->diagnostico->nombre }}</td>
                                    <td class="text-right font-semibold text-ink"><x-moneda :monto="$t->costo_acordado" /></td>
                                    <td><x-estado-clinico :estado="$t->estado" /></td>
                                    <td class="whitespace-nowrap">
                                        <span class="text-ink">Sesión {{ $a['hechas'] }} de {{ $a['total'] }}</span>
                                        <span class="mt-1 block h-1.5 w-28 overflow-hidden rounded-full bg-brand-100" aria-hidden="true"><span class="block h-full rounded-full bg-brand-600" style="width: {{ $porcentaje($a) }}%"></span></span>
                                    </td>
                                    <td class="text-right"><a href="{{ route('tratamientos-aplicados.mostrar', $t->id) }}" class="btn btn-secondary btn-sm">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($tratamientos as $t)
                        @php $a = $avance($t); @endphp
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 break-words font-display text-base font-bold text-ink">{{ $t->tratamiento->nombre }}</p>
                                <x-estado-clinico :estado="$t->estado" />
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Pieza</dt><dd class="font-medium tabular-nums text-ink">{{ $t->pieza_dental_id ?? '—' }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Costo acordado</dt><dd class="font-semibold text-ink"><x-moneda :monto="$t->costo_acordado" /></dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Diagnóstico</dt><dd class="break-words font-medium text-ink">{{ $t->diagnostico->nombre }}</dd></div>
                                <div class="col-span-2 min-w-0">
                                    <dt class="text-xs text-ink-soft">Avance</dt>
                                    <dd class="font-medium text-ink">Sesión {{ $a['hechas'] }} de {{ $a['total'] }}
                                        <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-brand-100" aria-hidden="true"><span class="block h-full rounded-full bg-brand-600" style="width: {{ $porcentaje($a) }}%"></span></span>
                                    </dd>
                                </div>
                            </dl>
                            <a href="{{ route('tratamientos-aplicados.mostrar', $t->id) }}" class="btn btn-secondary btn-sm w-full">Abrir tratamiento</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        @if ($activo)
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
                {{-- Modificar la descripción (el id viaja oculto) --}}
                <section class="dr-card p-5 sm:p-6" aria-labelledby="modificar-descripcion">
                    <h3 id="modificar-descripcion" class="text-lg font-bold text-ink">Modificar descripción</h3>

                    <form method="POST" action="{{ route('planes.modificar') }}" class="mt-5 space-y-5">
                        @csrf
                        <input type="hidden" name="id" value="{{ $plan->id }}">

                        <x-campo name="descripcion" label="Descripción (opcional)" :errores="$errors->get('descripcion')">
                            <textarea id="descripcion" name="descripcion" rows="3" class="dr-input @error('descripcion') dr-input-error @enderror">{{ old('descripcion', $plan->descripcion) }}</textarea>
                        </x-campo>

                        <x-primary-button class="w-full sm:w-auto">Guardar</x-primary-button>
                    </form>
                </section>

                {{-- Cancelar plan (el id viaja oculto) --}}
                <section class="dr-card border-red-200 p-5 sm:p-6" aria-labelledby="cancelar-plan">
                    <h3 id="cancelar-plan" class="flex items-center gap-2 text-lg font-bold text-red-800"><x-icon name="alert" class="h-5 w-5" /> Cancelar plan</h3>
                    <p class="mt-1 text-sm text-ink-soft">Indique el motivo; el plan dejará de estar activo.</p>

                    <form method="POST" action="{{ route('planes.cancelar') }}" class="mt-5 space-y-5"
                          onsubmit="return confirm('¿Cancelar este plan de tratamiento?');">
                        @csrf
                        <input type="hidden" name="id" value="{{ $plan->id }}">

                        <x-campo name="motivo" label="Motivo" :requerido="true" :errores="$errors->get('motivo')">
                            <x-text-input id="motivo" name="motivo" type="text" :value="old('motivo')" maxlength="255" required :error="$errors->has('motivo')" />
                        </x-campo>

                        <x-danger-button class="w-full sm:w-auto"><x-icon name="ban" class="h-4 w-4" /> Cancelar plan</x-danger-button>
                    </form>
                </section>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('planes.listar', ['paciente_id' => $plan->paciente_id]) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a los planes
            </a>
        </div>
    </x-pagina>
</x-app-layout>
