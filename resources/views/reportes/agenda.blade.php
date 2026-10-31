{{-- CU43 Generar reportes de agenda. --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Reportes de agenda
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reportes.partials.pestanas')
            @include('reportes.partials.estatico', ['accion' => route('reportes.agenda')])

            {{-- Reporte dinámico (mismo método del controlador con modo=dinamico) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reporte personalizado</h3>

                <form method="GET" action="{{ route('reportes.agenda') }}" class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-6 items-end">
                    <input type="hidden" name="modo" value="dinamico">

                    <div>
                        <x-input-label for="odontologo_id" value="Odontólogo" />
                        <select id="odontologo_id" name="odontologo_id" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($odontologos as $o)
                                <option value="{{ $o->id }}" @selected(request('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="paciente_id" value="Paciente" />
                        <select id="paciente_id" name="paciente_id" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($pacientes as $p)
                                <option value="{{ $p->id }}" @selected(request('paciente_id') == $p->id)>{{ $p->nombreCompleto() }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="estado_cita_id" value="Estado" />
                        <select id="estado_cita_id" name="estado_cita_id" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado->id }}" @selected(request('estado_cita_id') == $estado->id)>{{ ucfirst($estado->nombre) }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div></div>

                    @include('reportes.partials.periodo-columnas')
                </form>
            </div>

            @include('reportes.partials.resultado')
        </div>
    </div>
</x-app-layout>
