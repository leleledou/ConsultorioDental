{{-- CU16 Ver historial de tarifas de un tratamiento, con el formulario "Nuevo precio". --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tarifas: {{ $tratamiento->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historial de precios</h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Precio</th>
                                    <th class="px-3 py-2">Inicio de vigencia</th>
                                    <th class="px-3 py-2">Fin de vigencia</th>
                                    <th class="px-3 py-2">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($tarifas as $tarifa)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">{{ $tarifa->precio }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $tarifa->fecha_inicio_vigencia->format('d/m/Y') }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $tarifa->fecha_fin_vigencia?->format('d/m/Y') ?? '—' }}</td>
                                        <td class="px-3 py-3">
                                            @if ($tarifa->estado === 'vigente')
                                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200 whitespace-nowrap">Vigente</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Cerrada</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            Este tratamiento no tiene tarifas registradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Formulario "Nuevo precio" de este tratamiento --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nuevo precio</h2>
                    </header>

                    <form method="POST" action="{{ route('tarifas.guardar') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="tratamiento_id" value="{{ $tratamiento->id }}">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="precio" value="Precio" />
                                <x-text-input id="precio" name="precio" type="number" min="0.01" max="99999999.99" step="0.01" class="mt-1 block w-full" :value="old('precio')" required />
                                <x-input-error :messages="$errors->get('precio')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="fecha_inicio_vigencia" value="Inicio de vigencia" />
                                <x-text-input id="fecha_inicio_vigencia" name="fecha_inicio_vigencia" type="date" class="mt-1 block w-full" :value="old('fecha_inicio_vigencia')" required />
                                <x-input-error :messages="$errors->get('fecha_inicio_vigencia')" class="mt-2" />
                            </div>
                        </div>

                        <x-primary-button>Guardar precio</x-primary-button>
                    </form>
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('tarifas.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a tarifas
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
