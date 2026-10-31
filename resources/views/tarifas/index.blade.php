{{-- CU16 Gestionar tarifas (solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'tratamiento_id' => $volvioFormulario ? old('tratamiento_id', '') : '',
        'tratamiento' => $volvioFormulario ? old('tratamiento', '') : '',
        'precio' => $volvioFormulario ? old('precio', '') : '',
        'fecha_inicio_vigencia' => $volvioFormulario ? old('fecha_inicio_vigencia', '') : '',
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Tarifas
        </h2>
    </x-slot>

    {{-- nuevoPrecio(): abre el formulario con el tratamiento de la fila. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevoPrecio(tratamiento) {
                this.form = { tratamiento_id: tratamiento.id, tratamiento: tratamiento.nombre, precio: '', fecha_inicio_vigencia: '' };
                this.mostrarFormulario();
            },
            mostrarFormulario() {
                this.abierto = true;
                this.$nextTick(() => this.$refs.formulario.scrollIntoView({ behavior: 'smooth' }));
            },
         }">
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    {{-- Buscador --}}
                    <form method="GET" action="{{ route('tarifas.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                        <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                      placeholder="Buscar por tratamiento" />
                        <x-primary-button>Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('tarifas.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                        @endif
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Tratamiento</th>
                                    <th class="px-3 py-2">Precio vigente</th>
                                    <th class="px-3 py-2">Vigente desde</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($tratamientos as $t)
                                    <tr class="align-top">
                                        <td class="px-3 py-3 font-medium">{{ $t->nombre }}</td>
                                        @if ($t->tarifaVigente)
                                            <td class="px-3 py-3">{{ $t->tarifaVigente->precio }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $t->tarifaVigente->fecha_inicio_vigencia->format('d/m/Y') }}</td>
                                        @else
                                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">Sin tarifa</td>
                                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">—</td>
                                        @endif
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-secondary-button @click="nuevoPrecio({{ Js::from($t->only(['id', 'nombre'])) }})">
                                                    Nuevo precio
                                                </x-secondary-button>

                                                <a href="{{ route('tarifas.mostrar', $t->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    Historial
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            {{ $buscar ? 'No se encontraron tratamientos con ese criterio de búsqueda.' : 'No hay tratamientos registrados.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $tratamientos->links() }}
                </div>
            </div>

            {{-- Formulario "Nuevo precio" --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="'Nuevo precio: ' + form.tratamiento"></h2>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('tarifas.guardar') }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        <input type="hidden" name="tratamiento_id" :value="form.tratamiento_id">
                        {{-- Solo para el título, si el formulario vuelve con errores --}}
                        <input type="hidden" name="tratamiento" :value="form.tratamiento">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="precio" value="Precio" />
                                <x-text-input id="precio" name="precio" type="number" min="0.01" max="99999999.99" step="0.01" class="mt-1 block w-full" x-model="form.precio" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('precio')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="fecha_inicio_vigencia" value="Inicio de vigencia" />
                                <x-text-input id="fecha_inicio_vigencia" name="fecha_inicio_vigencia" type="date" class="mt-1 block w-full" x-model="form.fecha_inicio_vigencia" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('fecha_inicio_vigencia')" class="mt-2" /> @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button>Guardar precio</x-primary-button>
                            <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
