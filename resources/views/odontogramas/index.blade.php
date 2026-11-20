{{-- CU25 Registrar odontograma: odontogramas de un paciente. --}}
@php
    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Odontogramas: {{ $paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <div class="flex flex-wrap justify-end gap-2">
                        {{-- CU26 Comparar odontogramas --}}
                        <a href="{{ route('odontogramas.comparar', $paciente->id) }}" class="{{ $claseEnlace }}">Comparar</a>
                        <a href="{{ route('odontogramas.crear', ['paciente' => $paciente->id]) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                            Registrar odontograma
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Fecha</th>
                                    <th class="px-3 py-2">Tipo</th>
                                    <th class="px-3 py-2">Dentición</th>
                                    <th class="px-3 py-2">Odontólogo</th>
                                    <th class="px-3 py-2">Piezas</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($odontogramas as $o)
                                    <tr class="align-top">
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $o->fecha_registro->format('d/m/Y') }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($o->tipo) }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($o->tipo_denticion) }}</td>
                                        <td class="px-3 py-3">{{ $o->odontologo->nombres }} {{ $o->odontologo->apellidos }}</td>
                                        <td class="px-3 py-3">{{ $o->detalles_count }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($o->estado) }}</td>
                                        <td class="px-3 py-3">
                                            <a href="{{ route('odontogramas.mostrar', $o->id) }}" class="{{ $claseEnlace }}">Abrir</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            El paciente no tiene odontogramas registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="{{ $claseEnlace }}">Volver al paciente</a>
                <a href="{{ route('historias.mostrar', $paciente->id) }}" class="{{ $claseEnlace }}">Historia clínica</a>
            </div>
        </div>
    </div>
</x-app-layout>
