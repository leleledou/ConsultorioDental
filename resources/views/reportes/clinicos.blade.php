{{-- CU42 Generar reportes clínicos. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Reportes clínicos" subtitulo="Tratamientos aplicados, sesiones y avance clínico por periodo." />
    </x-slot>

    <x-pagina>
        @include('reportes.partials.pestanas')
        @include('reportes.partials.estatico', ['accion' => route('reportes.clinicos')])

        {{-- Reporte dinámico (mismo método del controlador con modo=dinamico) --}}
        <section class="dr-card overflow-hidden" aria-labelledby="reporte-personalizado">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="reporte-personalizado" class="text-lg font-bold text-ink">Reporte personalizado</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Filtre los datos, elija el periodo y las columnas que desea ver.</p>
            </header>

            <form method="GET" action="{{ route('reportes.clinicos') }}" class="space-y-8 p-5 sm:p-6">
                <input type="hidden" name="modo" value="dinamico">

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Filtros</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        <x-campo name="odontologo_id" label="Odontólogo">
                            <select id="odontologo_id" name="odontologo_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($odontologos as $o)
                                    <option value="{{ $o->id }}" @selected(request('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="especialidad_id" label="Especialidad">
                            <select id="especialidad_id" name="especialidad_id" class="dr-input">
                                <option value="">Todas</option>
                                @foreach ($especialidades as $e)
                                    <option value="{{ $e->id }}" @selected(request('especialidad_id') == $e->id)>{{ $e->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="tratamiento_id" label="Tratamiento">
                            <select id="tratamiento_id" name="tratamiento_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($tratamientos as $t)
                                    <option value="{{ $t->id }}" @selected(request('tratamiento_id') == $t->id)>{{ $t->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="estado" label="Estado">
                            <select id="estado" name="estado" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                                @endforeach
                            </select>
                        </x-campo>
                    </div>
                </fieldset>

                @include('reportes.partials.periodo-columnas')
            </form>
        </section>

        @include('reportes.partials.resultado')

        {{-- RF-84 y RF-85: documentos por paciente. Búsqueda por CI, nombres o apellidos (CU09). --}}
        <section id="documentos" class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="documentos-paciente">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="documentos-paciente" class="text-lg font-bold text-ink">Documentos por paciente</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Historia clínica imprimible y comparativo de odontogramas de un paciente.</p>
            </header>

            <form method="GET" action="{{ route('reportes.clinicos') }}#documentos" class="flex flex-col gap-3 p-5 sm:flex-row sm:items-end sm:p-6">
                <x-campo name="buscar_paciente" label="Buscar paciente" class="min-w-0 flex-1">
                    <input id="buscar_paciente" name="buscar_paciente" type="search" value="{{ request('buscar_paciente') }}"
                           class="dr-input" placeholder="CI, nombres o apellidos" autocomplete="off">
                </x-campo>
                <button type="submit" class="btn btn-primary"><x-icon name="search" class="h-4 w-4" /> Buscar</button>
            </form>

            @if (request()->filled('buscar_paciente'))
                @if ($pacientesDocumento->isEmpty())
                    <p class="border-t border-line px-5 py-8 text-center text-sm font-semibold text-ink-soft sm:px-6">No se encontraron pacientes con ese criterio de búsqueda.</p>
                @else
                    <ul class="divide-y divide-line border-t border-line">
                        @foreach ($pacientesDocumento as $p)
                            <li class="flex flex-col gap-3 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                                <p class="min-w-0 text-ink"><span class="font-semibold">{{ $p->nombreCompleto() }}</span> <span class="text-sm text-ink-soft">· CI {{ $p->ci }}</span></p>
                                <div class="flex flex-col gap-2 min-[420px]:flex-row">
                                    <a href="{{ route('reportes.historia', $p->id) }}" class="btn btn-secondary btn-sm"><x-icon name="printer" class="h-4 w-4" /> Historia clínica</a>
                                    <a href="{{ route('reportes.odontogramas', $p->id) }}" class="btn btn-secondary btn-sm"><x-icon name="refresh" class="h-4 w-4" /> Comparativo de odontogramas</a>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </section>
    </x-pagina>
</x-app-layout>
