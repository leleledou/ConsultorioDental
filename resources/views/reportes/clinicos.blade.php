{{-- CU42 Generar reportes clínicos. --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Reportes clínicos
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('reportes.partials.pestanas')
            @include('reportes.partials.estatico', ['accion' => route('reportes.clinicos')])

            {{-- Reporte dinámico (mismo método del controlador con modo=dinamico) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reporte personalizado</h3>

                <form method="GET" action="{{ route('reportes.clinicos') }}" class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-6 items-end">
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
                        <x-input-label for="especialidad_id" value="Especialidad" />
                        <select id="especialidad_id" name="especialidad_id" class="{{ $claseCampo }}">
                            <option value="">Todas</option>
                            @foreach ($especialidades as $e)
                                <option value="{{ $e->id }}" @selected(request('especialidad_id') == $e->id)>{{ $e->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="tratamiento_id" value="Tratamiento" />
                        <select id="tratamiento_id" name="tratamiento_id" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($tratamientos as $t)
                                <option value="{{ $t->id }}" @selected(request('tratamiento_id') == $t->id)>{{ $t->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="estado" value="Estado" />
                        <select id="estado" name="estado" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($estados as $estado)
                                <option value="{{ $estado }}" @selected(request('estado') === $estado)>{{ ucfirst($estado) }}</option>
                            @endforeach
                        </select>
                    </div>

                    @include('reportes.partials.periodo-columnas')
                </form>
            </div>

            @include('reportes.partials.resultado')
        </div>
    </div>
</x-app-layout>
