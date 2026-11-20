{{-- RF-49: la cita atendida no indica tratamiento; se elige uno activo del paciente para generar la sesión. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Registrar sesión: '.$cita->paciente->nombreCompleto()"
                           subtitulo="Elija el tratamiento que se atendió en la cita."
                           :volver="route('citas.mostrar', $cita->id)" volver-texto="Cita" />
    </x-slot>

    <x-pagina ancho="4xl">
        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <section class="dr-card flex flex-col gap-1 p-4 text-sm sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-label="Cita">
            <p class="min-w-0 text-ink-soft">
                Cita del <span class="font-semibold text-ink">{{ $cita->fecha->format('d/m/Y') }}</span> a las
                <span class="font-semibold text-ink">{{ $cita->horaInicioCorta() }}</span> · {{ $cita->motivo }}
            </p>
            <x-estado-cita :nombre="$cita->estado->nombre" class="self-start" />
        </section>

        <section class="dr-card overflow-hidden" aria-labelledby="tratamientos-activos">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="tratamientos-activos" class="text-lg font-bold text-ink">Tratamientos activos <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $activos->count() }})</span></h3>
                <p class="mt-0.5 text-sm text-ink-soft">La cita quedará vinculada al tratamiento elegido al guardar la sesión.</p>
            </header>

            @if ($activos->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">El paciente no tiene tratamientos planificados ni en proceso.</p>
                    <a href="{{ route('planes.listar', ['paciente_id' => $cita->paciente_id]) }}" class="btn btn-secondary mt-5">Ver planes del paciente</a>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($activos as $t)
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="break-words font-semibold text-ink">{{ $t->tratamiento->nombre }}</p>
                                <p class="mt-0.5 text-sm text-ink-soft">Pieza {{ $t->piezaDental?->codigo_fdi ?? '—' }} · desde el {{ $t->fecha_inicio->format('d/m/Y') }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-estado-clinico :estado="$t->estado" />
                                <a href="{{ route('sesiones.desde-cita', ['cita' => $cita->id, 'tratamiento_aplicado_id' => $t->id]) }}" class="btn btn-primary btn-sm">
                                    Elegir <x-icon name="arrow" class="h-4 w-4" />
                                </a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </x-pagina>
</x-app-layout>
