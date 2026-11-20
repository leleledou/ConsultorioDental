{{-- CU26 Comparar odontogramas: estado de cada pieza en dos fechas. --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';

    $etiqueta = fn ($o) => $o->fecha_registro->format('d/m/Y').' · '.ucfirst($o->tipo).($o->estado === 'anulado' ? ' (anulado)' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Comparar odontogramas: {{ $paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @isset($aviso)
                <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    {{ $aviso }}
                </div>
            @else
                {{-- Elegir los dos odontogramas (parámetros GET a y b) --}}
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <form method="GET" action="{{ route('odontogramas.comparar', $paciente->id) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end">
                        <div>
                            <x-input-label for="a" value="Odontograma A" />
                            <select id="a" name="a" class="{{ $claseCampo }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($odontogramas as $o)
                                    <option value="{{ $o->id }}" @selected($a?->id === $o->id)>{{ $etiqueta($o) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-input-label for="b" value="Odontograma B" />
                            <select id="b" name="b" class="{{ $claseCampo }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($odontogramas as $o)
                                    <option value="{{ $o->id }}" @selected($b?->id === $o->id)>{{ $etiqueta($o) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <x-primary-button>Comparar</x-primary-button>
                        </div>
                    </form>
                </div>

                @if ($filas !== null)
                    @php $cambios = collect($filas)->where('cambio', true)->count(); @endphp

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                            <p class="text-sm">
                                <span class="font-semibold">{{ $cambios }}</span>
                                {{ $cambios === 1 ? 'pieza cambió' : 'piezas cambiaron' }} de {{ count($filas) }}
                                entre el {{ $etiqueta($a) }} (A) y el {{ $etiqueta($b) }} (B).
                            </p>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                    <thead>
                                        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                            <th class="px-3 py-2">Pieza</th>
                                            <th class="px-3 py-2">Nombre</th>
                                            <th class="px-3 py-2">Estado en A</th>
                                            <th class="px-3 py-2">Estado en B</th>
                                            <th class="px-3 py-2">Cambio</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                        @foreach ($filas as $fila)
                                            <tr @class(['bg-amber-50 dark:bg-amber-900/20' => $fila['cambio']])>
                                                <td class="px-3 py-2 font-medium">{{ $fila['codigo_fdi'] }}</td>
                                                <td class="px-3 py-2">{{ $fila['nombre'] }}</td>
                                                @foreach (['a', 'b'] as $lado)
                                                    <td class="px-3 py-2">
                                                        @if ($fila['estado_'.$lado])
                                                            <span class="inline-flex items-center gap-2">
                                                                <span class="inline-block h-3 w-3 rounded-sm border border-black/10" style="background-color: {{ $fila['color_'.$lado] ?? '#E5E7EB' }};"></span>
                                                                {{ $fila['estado_'.$lado] }}
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">No registrada</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="px-3 py-2">
                                                    @if ($fila['cambio'])
                                                        <span class="inline-flex items-center rounded-full bg-amber-100 dark:bg-amber-800/60 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:text-amber-100">● Cambió</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endisset

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('odontogramas.listar', $paciente->id) }}" class="{{ $claseEnlace }}">Volver a los odontogramas</a>
            </div>
        </div>
    </div>
</x-app-layout>
