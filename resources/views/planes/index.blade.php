{{-- CU27 Gestionar plan de tratamiento: buscador de paciente o planes del paciente. --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores se abre solo.
    $volvioFormulario = old('formulario') === 'datos';

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            @if ($paciente)
                Planes de tratamiento: {{ $paciente->nombreCompleto() }}
            @else
                Plan de tratamiento
            @endif
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ abierto: {{ Js::from($volvioFormulario) }} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            {{-- Errores del buscador --}}
            @if ($errors->any() && ! $volvioFormulario)
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (! $paciente)
                {{-- Buscador de paciente (CU09) --}}
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                        <form method="GET" action="{{ route('planes.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                            <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                          placeholder="Buscar por CI, nombres o apellidos" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('planes.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                            @endif
                        </form>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="px-3 py-2">CI</th>
                                        <th class="px-3 py-2">Nombre completo</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($pacientes as $p)
                                        <tr class="align-top">
                                            <td class="px-3 py-3">{{ $p->ci }}</td>
                                            <td class="px-3 py-3">
                                                <a href="{{ route('planes.listar', ['paciente_id' => $p->id]) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                    {{ $p->nombreCompleto() }}
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="2" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                                {{ $buscar ? 'No se encontraron pacientes con ese criterio de búsqueda.' : 'No hay pacientes registrados.' }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        {{ $pacientes->links() }}
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                        @if ($paciente->estado === 'activo')
                            <div class="flex justify-end">
                                <x-primary-button type="button" @click="abierto = true">Nuevo plan</x-primary-button>
                            </div>
                        @endif

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="px-3 py-2">Fecha de creación</th>
                                        <th class="px-3 py-2">Odontólogo responsable</th>
                                        <th class="px-3 py-2">Descripción</th>
                                        <th class="px-3 py-2">Costo total</th>
                                        <th class="px-3 py-2">Estado</th>
                                        <th class="px-3 py-2">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @forelse ($planes as $plan)
                                        <tr class="align-top">
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $plan->fecha_creacion->format('d/m/Y') }}</td>
                                            <td class="px-3 py-3">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</td>
                                            <td class="px-3 py-3 whitespace-pre-line">{{ $plan->descripcion ?? '—' }}</td>
                                            <td class="px-3 py-3">{{ $plan->costo_total }}</td>
                                            <td class="px-3 py-3">{{ ucfirst($plan->estado) }}</td>
                                            <td class="px-3 py-3">
                                                <a href="{{ route('planes.mostrar', $plan->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    Abrir
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                                El paciente no tiene planes de tratamiento.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Formulario "Nuevo plan" --}}
                @if ($paciente->estado === 'activo')
                    <div x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                        <section>
                            <header>
                                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Nuevo plan</h2>
                            </header>

                            @if ($volvioFormulario && $errors->any())
                                <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                                    Revise los datos marcados en el formulario.
                                </div>
                            @endif

                            <form method="POST" action="{{ route('planes.guardar') }}" class="mt-6 space-y-6">
                                @csrf
                                <input type="hidden" name="formulario" value="datos">
                                <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                    <div>
                                        <x-input-label for="odontologo_id" value="Odontólogo responsable" />
                                        <select id="odontologo_id" name="odontologo_id" class="{{ $claseCampo }}" required>
                                            <option value="">Seleccione…</option>
                                            @foreach ($odontologos as $o)
                                                <option value="{{ $o->id }}" @selected(old('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                                            @endforeach
                                        </select>
                                        @if ($volvioFormulario) <x-input-error :messages="$errors->get('odontologo_id')" class="mt-2" /> @endif
                                        @if ($volvioFormulario) <x-input-error :messages="$errors->get('paciente_id')" class="mt-2" /> @endif
                                    </div>

                                    <div class="sm:col-span-2">
                                        <x-input-label for="descripcion" value="Descripción (opcional)" />
                                        <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo }}">{{ old('descripcion') }}</textarea>
                                        @if ($volvioFormulario) <x-input-error :messages="$errors->get('descripcion')" class="mt-2" /> @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button>Crear plan</x-primary-button>
                                    <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                                </div>
                            </form>
                        </section>
                    </div>
                @endif

                <div class="flex flex-wrap items-center gap-4">
                    <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                        Volver al paciente
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
