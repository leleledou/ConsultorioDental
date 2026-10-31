{{-- CU43 Generar reportes de agenda. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Reportes de agenda" subtitulo="Citas del periodo según su estado, paciente y odontólogo." />
    </x-slot>

    <x-pagina>
        @include('reportes.partials.pestanas')
        @include('reportes.partials.estatico', ['accion' => route('reportes.agenda')])

        {{-- Reporte dinámico (mismo método del controlador con modo=dinamico) --}}
        <section class="dr-card overflow-hidden" aria-labelledby="reporte-personalizado">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="reporte-personalizado" class="text-lg font-bold text-ink">Reporte personalizado</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Filtre los datos, elija el periodo y las columnas que desea ver.</p>
            </header>

            <form method="GET" action="{{ route('reportes.agenda') }}" class="space-y-8 p-5 sm:p-6">
                <input type="hidden" name="modo" value="dinamico">

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Filtros</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <x-campo name="odontologo_id" label="Odontólogo">
                            <select id="odontologo_id" name="odontologo_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($odontologos as $o)
                                    <option value="{{ $o->id }}" @selected(request('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="paciente_id" label="Paciente">
                            <select id="paciente_id" name="paciente_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($pacientes as $p)
                                    <option value="{{ $p->id }}" @selected(request('paciente_id') == $p->id)>{{ $p->nombreCompleto() }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="estado_cita_id" label="Estado">
                            <select id="estado_cita_id" name="estado_cita_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($estados as $estado)
                                    <option value="{{ $estado->id }}" @selected(request('estado_cita_id') == $estado->id)>{{ ucfirst($estado->nombre) }}</option>
                                @endforeach
                            </select>
                        </x-campo>
                    </div>
                </fieldset>

                @include('reportes.partials.periodo-columnas')
            </form>
        </section>

        @include('reportes.partials.resultado')
    </x-pagina>
</x-app-layout>
