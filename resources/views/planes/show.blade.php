{{-- CU27 Abrir plan de tratamiento: datos del plan y sus tratamientos aplicados. --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Plan de tratamiento: {{ $plan->paciente->nombreCompleto() }}
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
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del plan</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->paciente->nombreCompleto() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Odontólogo responsable</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de creación</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->fecha_creacion->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Costo total</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $plan->costo_total }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($plan->estado) }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Descripción</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $plan->descripcion ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Tratamientos del plan</h3>
                        @if ($plan->estado === 'activo')
                            <a href="{{ route('tratamientos-aplicados.crear', $plan->id) }}" class="{{ $claseEnlace }}">Agregar tratamiento</a>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Tratamiento</th>
                                    <th class="px-3 py-2">Pieza</th>
                                    <th class="px-3 py-2">Diagnóstico</th>
                                    <th class="px-3 py-2">Costo acordado</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Avance</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($tratamientos as $t)
                                    <tr class="align-top">
                                        <td class="px-3 py-3 font-medium">{{ $t->tratamiento->nombre }}</td>
                                        {{-- El id de la pieza es su código FDI --}}
                                        <td class="px-3 py-3">{{ $t->pieza_dental_id ?? '—' }}</td>
                                        <td class="px-3 py-3">{{ $t->diagnostico->nombre }}</td>
                                        <td class="px-3 py-3">{{ $t->costo_acordado }}</td>
                                        <td class="px-3 py-3">{{ ucfirst($t->estado) }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">Sesión {{ $t->sesiones->where('estado', 'realizada')->count() }} de {{ $t->sesiones_planificadas }}</td>
                                        <td class="px-3 py-3">
                                            <a href="{{ route('tratamientos-aplicados.mostrar', $t->id) }}" class="{{ $claseEnlace }}">Abrir</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            El plan no tiene tratamientos registrados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($plan->estado === 'activo')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- Modificar la descripción (el id viaja oculto) --}}
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Modificar descripción</h3>

                        <form method="POST" action="{{ route('planes.modificar') }}" class="mt-6 space-y-6">
                            @csrf
                            <input type="hidden" name="id" value="{{ $plan->id }}">

                            <div>
                                <x-input-label for="descripcion" value="Descripción (opcional)" />
                                <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo }}">{{ old('descripcion', $plan->descripcion) }}</textarea>
                            </div>

                            <x-primary-button>Guardar</x-primary-button>
                        </form>
                    </div>

                    {{-- Cancelar plan (el id viaja oculto) --}}
                    <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cancelar plan</h3>

                        <form method="POST" action="{{ route('planes.cancelar') }}" class="mt-6 space-y-6"
                              onsubmit="return confirm('¿Cancelar este plan de tratamiento?');">
                            @csrf
                            <input type="hidden" name="id" value="{{ $plan->id }}">

                            <div>
                                <x-input-label for="motivo" value="Motivo" />
                                <x-text-input id="motivo" name="motivo" type="text" class="mt-1 block w-full" :value="old('motivo')" maxlength="255" required />
                            </div>

                            <x-danger-button>Cancelar plan</x-danger-button>
                        </form>
                    </div>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('planes.listar', ['paciente_id' => $plan->paciente_id]) }}" class="{{ $claseEnlace }}">
                    Volver a los planes
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
