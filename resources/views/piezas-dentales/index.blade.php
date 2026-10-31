{{-- CU22 Gestionar piezas dentales y estados de pieza (solo administrador). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Catálogo de piezas dentales
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Pestañas de «Piezas y estados» --}}
            <div class="flex gap-6 border-b border-gray-200 dark:border-gray-700">
                <x-nav-link :href="route('estados-pieza.listar')" :active="false">Estados de pieza</x-nav-link>
                <x-nav-link :href="route('piezas-dentales.listar')" :active="true">Piezas dentales</x-nav-link>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Código FDI</th>
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Cuadrante</th>
                                    <th class="px-3 py-2">Tipo de pieza</th>
                                    <th class="px-3 py-2">Dentición</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($piezas as $p)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">
                                            <a href="{{ route('piezas-dentales.mostrar', $p->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $p->codigo_fdi }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3">{{ $p->nombre }}</td>
                                        <td class="px-3 py-3">{{ $p->cuadrante }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($p->tipo_pieza) }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($p->tipo_denticion) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            No hay piezas dentales registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
