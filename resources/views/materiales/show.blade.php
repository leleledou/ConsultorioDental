{{-- CU20 Consultar material: detalle del material del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Material: {{ $material->nombre }}
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

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del material</h3>
                    @if ($material->estado === 'activo')
                        <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200 whitespace-nowrap">Activo</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Inactivo</span>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $material->nombre }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Descripción</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $material->descripcion ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Categoría</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $material->categoria }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Unidad de medida</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $material->unidad_medida }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('materiales.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a materiales
                </a>

                {{-- Dar de baja / habilitar desde la ficha (el id viaja oculto) --}}
                @if ($material->estado === 'activo')
                    <form method="POST" action="{{ route('materiales.eliminar') }}"
                          onsubmit="return confirm({{ Js::from('¿Dar de baja el material '.$material->nombre.'?') }});">
                        @csrf
                        <input type="hidden" name="id" value="{{ $material->id }}">
                        <x-danger-button>Dar de baja</x-danger-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('materiales.habilitar') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $material->id }}">
                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
