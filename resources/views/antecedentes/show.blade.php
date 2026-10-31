{{-- CU21 Consultar antecedente: detalle del antecedente del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Antecedente: {{ $antecedente->nombre }}
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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del antecedente</h3>
                </div>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $antecedente->nombre }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Descripción</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $antecedente->descripcion ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('antecedentes.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a antecedentes
                </a>

                {{-- Eliminar desde la ficha (el id viaja oculto) --}}
                <form method="POST" action="{{ route('antecedentes.eliminar') }}"
                      onsubmit="return confirm({{ Js::from('¿Eliminar el antecedente '.$antecedente->nombre.'?') }});">
                    @csrf
                    <input type="hidden" name="id" value="{{ $antecedente->id }}">
                    <x-danger-button>Eliminar</x-danger-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
