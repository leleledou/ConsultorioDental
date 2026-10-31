{{-- CU22 Consultar pieza dental: detalle de la pieza del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Pieza dental: {{ $pieza->codigo_fdi }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos de la pieza dental</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Código FDI</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $pieza->codigo_fdi }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $pieza->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Cuadrante</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $pieza->cuadrante }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tipo de pieza</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($pieza->tipo_pieza) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Dentición</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($pieza->tipo_denticion) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('piezas-dentales.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a piezas dentales
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
