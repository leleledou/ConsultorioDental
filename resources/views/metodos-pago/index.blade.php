{{-- CU37 Gestionar métodos de pago (solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'nombre' => $volvioFormulario ? old('nombre', '') : '',
        'descripcion' => $volvioFormulario ? old('descripcion', '') : '',
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Catálogo de métodos de pago
        </h2>
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos del método de pago. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombre: '', descripcion: '' };
                this.mostrarFormulario();
            },
            editar(metodoPago) {
                this.form = { ...metodoPago, descripcion: metodoPago.descripcion ?? '' };
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

            {{-- Errores de las acciones por fila y del buscador --}}
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
                    {{-- Buscador y botón de registro --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <form method="GET" action="{{ route('metodos-pago.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                            <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                          placeholder="Buscar por nombre" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('metodos-pago.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                            @endif
                        </form>

                        <x-primary-button type="button" @click="nuevo()">Nuevo método de pago</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Descripción</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($metodosPago as $m)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">
                                            <a href="{{ route('metodos-pago.mostrar', $m->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $m->nombre }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3">{{ $m->descripcion ?? '—' }}</td>
                                        <td class="px-3 py-3">
                                            @if ($m->estado === 'activo')
                                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200 whitespace-nowrap">Activo</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-secondary-button
                                                    @click="editar({{ Js::from($m->only(['id', 'nombre', 'descripcion'])) }})">
                                                    Modificar
                                                </x-secondary-button>

                                                {{-- Dar de baja / habilitar (el id viaja oculto; no se borra, RNF-06) --}}
                                                @if ($m->estado === 'activo')
                                                    <form method="POST" action="{{ route('metodos-pago.eliminar') }}"
                                                          onsubmit="return confirm({{ Js::from('¿Dar de baja el método de pago '.$m->nombre.'?') }});">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $m->id }}">
                                                        <x-danger-button>Dar de baja</x-danger-button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('metodos-pago.habilitar') }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $m->id }}">
                                                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            {{ $buscar ? 'No se encontraron métodos de pago con ese criterio de búsqueda.' : 'No hay métodos de pago registrados.' }}
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $metodosPago->links() }}
                </div>
            </div>

            {{-- Formulario de registro / modificación --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="form.id ? 'Modificar método de pago' : 'Registrar método de pago'"></h2>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" :action="form.id ? {{ Js::from(route('metodos-pago.modificar')) }} : {{ Js::from(route('metodos-pago.guardar')) }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        {{-- El id viaja oculto solo al modificar --}}
                        <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" x-model="form.nombre" maxlength="30" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('nombre')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="descripcion" value="Descripción (opcional)" />
                                <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo }}" x-model="form.descripcion"></textarea>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('descripcion')" class="mt-2" /> @endif
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Registrar'">Guardar</x-primary-button>
                            <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
