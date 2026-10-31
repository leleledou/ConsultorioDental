{{-- CU29 Actualizar estado del tratamiento aplicado (RF-40). --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
    $confirmar = session('confirmar');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tratamiento aplicado: {{ $tratamiento->tratamiento->nombre }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ estado: {{ Js::from(old('estado', '')) }}, confirmando: {{ Js::from((bool) $confirmar) }} }">
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

            {{-- Flujo A2: concluir con sesiones pendientes --}}
            @if ($confirmar)
                <div x-show="confirmando" class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200 space-y-3">
                    <p>El tratamiento tiene {{ $confirmar }} {{ $confirmar > 1 ? 'sesiones pendientes' : 'sesión pendiente' }}. ¿Desea concluirlo igualmente?</p>

                    <form method="POST" action="{{ route('tratamientos-aplicados.estado') }}" class="flex items-center gap-2">
                        @csrf
                        <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                        <input type="hidden" name="estado" value="concluido">
                        <input type="hidden" name="confirmado" value="1">
                        <x-primary-button>Confirmar</x-primary-button>
                        <x-secondary-button @click="confirmando = false">Cancelar</x-secondary-button>
                    </form>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del tratamiento</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->plan->paciente->nombreCompleto() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tratamiento</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->tratamiento->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Pieza dental</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">
                            {{ $tratamiento->piezaDental ? $tratamiento->piezaDental->codigo_fdi.' - '.$tratamiento->piezaDental->nombre : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Diagnóstico</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->diagnostico->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Costo acordado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->costo_acordado }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de inicio</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->fecha_inicio->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de fin</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->fecha_fin?->format('d/m/Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($tratamiento->estado) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Avance</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">Sesión {{ $realizadas }} de {{ $tratamiento->sesiones_planificadas }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Observación diagnóstica</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $tratamiento->observacion_diagnostica ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Observaciones</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $tratamiento->observaciones ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Cambio de estado: solo los destinos permitidos (el id viaja oculto) --}}
            @if ($tratamiento->plan->estado !== 'activo')
                <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    El plan no está activo.
                </div>
            @elseif ($destinos !== [])
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Cambiar estado</h3>

                    <form method="POST" action="{{ route('tratamientos-aplicados.estado') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                        <input type="hidden" name="confirmado" value="0">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="estado" value="Nuevo estado" />
                                <select id="estado" name="estado" class="{{ $claseCampo }}" x-model="estado" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($destinos as $destino)
                                        <option value="{{ $destino }}">{{ ucfirst($destino) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- El motivo solo se pide al cancelar --}}
                            <div x-show="estado === 'cancelado'" x-cloak>
                                <x-input-label for="motivo" value="Motivo" />
                                <x-text-input id="motivo" name="motivo" type="text" class="mt-1 block w-full" :value="old('motivo')" maxlength="255" x-bind:required="estado === 'cancelado'" />
                            </div>
                        </div>

                        <x-primary-button>Actualizar estado</x-primary-button>
                    </form>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('planes.mostrar', $tratamiento->plan_tratamiento_id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver al plan
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
