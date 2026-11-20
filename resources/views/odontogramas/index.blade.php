{{-- CU25 Registrar odontograma: odontogramas de un paciente, del más reciente al más antiguo. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Odontogramas: '.$paciente->nombreCompleto()"
                           subtitulo="Odontograma inicial y de evolución del paciente."
                           :volver="route('pacientes.mostrar', $paciente->id)" volver-texto="Paciente" />
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

        <section class="dr-card overflow-hidden" aria-labelledby="lista-odontogramas">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="lista-odontogramas" class="text-lg font-bold text-ink">Odontogramas <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $odontogramas->count() }})</span></h3>
                <div class="flex flex-col gap-2 min-[420px]:flex-row">
                    {{-- CU26 Comparar odontogramas --}}
                    <a href="{{ route('odontogramas.comparar', $paciente->id) }}" class="btn btn-secondary btn-sm">
                        <x-icon name="refresh" class="h-4 w-4" /> Comparar
                    </a>
                    <a href="{{ route('odontogramas.crear', ['paciente' => $paciente->id]) }}" class="btn btn-primary btn-sm">
                        <x-icon name="plus" class="h-4 w-4" /> Registrar odontograma
                    </a>
                </div>
            </header>

            @if ($odontogramas->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">El paciente no tiene odontogramas registrados.</p>
                    <a href="{{ route('odontogramas.crear', ['paciente' => $paciente->id]) }}" class="btn btn-primary mt-5">
                        <x-icon name="plus" class="h-4 w-4" /> Registrar el odontograma inicial
                    </a>
                </div>
            @else
                {{-- Tabla (desde 1280 px: 7 columnas con acción) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Odontogramas de {{ $paciente->nombreCompleto() }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Fecha</th>
                                <th scope="col">Tipo</th>
                                <th scope="col">Dentición</th>
                                <th scope="col">Odontólogo</th>
                                <th scope="col" class="text-right">Piezas</th>
                                <th scope="col">Estado</th>
                                <th scope="col"><span class="sr-only">Acciones</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($odontogramas as $o)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap font-semibold tabular-nums text-ink">{{ $o->fecha_registro->format('d/m/Y') }}</td>
                                    <td><x-insignia :tono="$o->tipo === 'inicial' ? 'marca' : 'info'">{{ ucfirst($o->tipo) }}</x-insignia></td>
                                    <td class="text-ink-soft">{{ ucfirst($o->tipo_denticion) }}</td>
                                    <td class="text-ink-soft">{{ $o->odontologo->nombres }} {{ $o->odontologo->apellidos }}</td>
                                    <td class="text-right tabular-nums text-ink">{{ $o->detalles_count }}</td>
                                    <td><x-insignia :tono="$o->estado === 'activo' ? 'exito' : 'error'">{{ ucfirst($o->estado) }}</x-insignia></td>
                                    <td class="text-right"><a href="{{ route('odontogramas.mostrar', $o->id) }}" class="btn btn-secondary btn-sm">Abrir</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas: 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($odontogramas as $o)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-display text-base font-bold tabular-nums text-ink">{{ $o->fecha_registro->format('d/m/Y') }}</p>
                                <x-insignia :tono="$o->estado === 'activo' ? 'exito' : 'error'">{{ ucfirst($o->estado) }}</x-insignia>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Tipo</dt><dd class="mt-0.5"><x-insignia :tono="$o->tipo === 'inicial' ? 'marca' : 'info'">{{ ucfirst($o->tipo) }}</x-insignia></dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Dentición</dt><dd class="font-medium text-ink">{{ ucfirst($o->tipo_denticion) }} · {{ $o->detalles_count }} piezas</dd></div>
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Odontólogo</dt><dd class="break-words font-medium text-ink">{{ $o->odontologo->nombres }} {{ $o->odontologo->apellidos }}</dd></div>
                            </dl>
                            <a href="{{ route('odontogramas.mostrar', $o->id) }}" class="btn btn-secondary btn-sm w-full">Abrir odontograma</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
            <a href="{{ route('historias.mostrar', $paciente->id) }}" class="btn btn-secondary">Historia clínica</a>
        </div>
    </x-pagina>
</x-app-layout>
