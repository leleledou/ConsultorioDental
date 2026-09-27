{{-- CU07 Consultar bitácora (solo lectura, RN-02). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Bitácora
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

            {{-- CU07 pasos 2 y 3: filtros --}}
            <div class="p-4 sm:p-6 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="GET" action="{{ route('bitacora.listar') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <x-input-label for="fecha_desde" value="Fecha desde" />
                        <x-text-input id="fecha_desde" name="fecha_desde" type="date" class="mt-1 block w-full" :value="request('fecha_desde')" />
                    </div>

                    <div>
                        <x-input-label for="fecha_hasta" value="Fecha hasta" />
                        <x-text-input id="fecha_hasta" name="fecha_hasta" type="date" class="mt-1 block w-full" :value="request('fecha_hasta')" />
                    </div>

                    <div>
                        <x-input-label for="usuario_id" value="Usuario" />
                        <select id="usuario_id" name="usuario_id" class="{{ $claseCampo }}">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $u)
                                <option value="{{ $u->id }}" @selected((string) request('usuario_id') === (string) $u->id)>
                                    {{ $u->apellidos }}, {{ $u->nombres }} ({{ $u->usuario }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="accion" value="Acción" />
                        <select id="accion" name="accion" class="{{ $claseCampo }}">
                            <option value="">Todas</option>
                            @foreach ($acciones as $accion)
                                <option value="{{ $accion }}" @selected(request('accion') === $accion)>{{ $accion }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <x-input-label for="ip" value="IP" />
                        <x-text-input id="ip" name="ip" type="text" class="mt-1 block w-full" :value="request('ip')" maxlength="45" />
                    </div>

                    <div class="sm:col-span-2 lg:col-span-5 flex items-center gap-4">
                        <x-primary-button>Filtrar</x-primary-button>
                        <a href="{{ route('bitacora.listar') }}" class="text-sm text-gray-600 dark:text-gray-400 underline">Limpiar filtros</a>
                    </div>
                </form>
            </div>

            {{-- CU07 pasos 4 y 5: resultados --}}
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $registros->total() }} registro(s) encontrado(s).
                    </p>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Usuario</th>
                                    <th class="px-3 py-2">Acción</th>
                                    <th class="px-3 py-2">Tabla</th>
                                    <th class="px-3 py-2">Registro</th>
                                    <th class="px-3 py-2">Fecha</th>
                                    <th class="px-3 py-2">Hora</th>
                                    <th class="px-3 py-2">IP</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($registros as $registro)
                                    <tr>
                                        <td class="px-3 py-2">
                                            @if ($registro->usuario)
                                                {{ $registro->usuario->usuario }}
                                            @elseif ($registro->usuario_digitado)
                                                <span class="text-gray-500 dark:text-gray-400">«{{ $registro->usuario_digitado }}» (digitado)</span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 font-mono text-xs">{{ $registro->accion }}</td>
                                        <td class="px-3 py-2">{{ $registro->tabla_afectada ?? '—' }}</td>
                                        <td class="px-3 py-2">{{ $registro->registro_id ?? '—' }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $registro->fecha_hora->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2 whitespace-nowrap">{{ $registro->fecha_hora->format('H:i:s') }}</td>
                                        <td class="px-3 py-2">{{ $registro->ip }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('bitacora.mostrar', $registro->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Ver</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            No existen registros para los filtros aplicados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $registros->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
