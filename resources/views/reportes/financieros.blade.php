{{-- CU44 Generar reportes financieros. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Reportes financieros" subtitulo="Abonos, ingresos y saldos pendientes por periodo." />
    </x-slot>

    <x-pagina>
        @include('reportes.partials.pestanas')
        @include('reportes.partials.estatico', ['accion' => route('reportes.financieros')])

        {{-- Reporte dinámico (mismo método del controlador con modo=dinamico) --}}
        <section class="dr-card overflow-hidden" aria-labelledby="reporte-personalizado">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="reporte-personalizado" class="text-lg font-bold text-ink">Reporte personalizado</h3>
                <p class="mt-0.5 text-sm text-ink-soft">Filtre los datos, elija el periodo y las columnas que desea ver.</p>
            </header>

            <form method="GET" action="{{ route('reportes.financieros') }}" class="space-y-8 p-5 sm:p-6">
                <input type="hidden" name="modo" value="dinamico">

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Filtros</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <x-campo name="odontologo_id" label="Odontólogo que recibió el cobro">
                            <select id="odontologo_id" name="odontologo_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($odontologos as $o)
                                    <option value="{{ $o->id }}" @selected(request('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="metodo_pago_id" label="Método de pago">
                            <select id="metodo_pago_id" name="metodo_pago_id" class="dr-input">
                                <option value="">Todos</option>
                                @foreach ($metodos as $m)
                                    <option value="{{ $m->id }}" @selected(request('metodo_pago_id') == $m->id)>{{ $m->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="estado" label="Estado del abono">
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
    </x-pagina>
</x-app-layout>
