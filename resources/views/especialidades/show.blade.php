{{-- CU14 Consultar especialidad: detalle con los usuarios que la tienen. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Especialidad: {{ $especialidad->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombre</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $especialidad->nombre }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">Descripción</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $especialidad->descripcion ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Usuarios con esta especialidad ({{ $especialidad->usuarios_count }})
                </h3>

                @if ($especialidad->usuarios->isEmpty())
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">Ningún usuario tiene esta especialidad.</p>
                @else
                    <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700 text-sm">
                        @foreach ($especialidad->usuarios as $u)
                            <li class="py-2 flex justify-between">
                                <a href="{{ route('usuarios.mostrar', $u->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ $u->apellidos }}, {{ $u->nombres }}
                                </a>
                                <span class="text-gray-500 dark:text-gray-400">{{ $u->usuario }} · {{ $u->estado === 'activo' ? 'Activo' : 'Inactivo' }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div>
                <a href="{{ route('especialidades.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a especialidades
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
