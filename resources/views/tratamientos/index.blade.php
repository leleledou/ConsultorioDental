{{-- CU15 Gestionar catálogo de tratamientos (RF-25, solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'nombre' => $volvioFormulario ? old('nombre', '') : '',
        'descripcion' => $volvioFormulario ? old('descripcion', '') : '',
        'especialidad_id' => $volvioFormulario ? old('especialidad_id', '') : '',
        'duracion_estimada_minutos' => $volvioFormulario ? old('duracion_estimada_minutos', '') : '',
        'numero_sesiones_previstas' => $volvioFormulario ? old('numero_sesiones_previstas', 1) : 1,
        'requiere_sesiones' => $volvioFormulario ? (bool) old('requiere_sesiones') : false,
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Catálogo de tratamientos
        </h2>
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos del tratamiento. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombre: '', descripcion: '', especialidad_id: '', duracion_estimada_minutos: '',
                              numero_sesiones_previstas: 1, requiere_sesiones: false };
                this.mostrarFormulario();
            },
            editar(tratamiento) {
                this.form = { ...tratamiento, descripcion: tratamiento.descripcion ?? '' };
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
                        <form method="GET" action="{{ route('tratamientos.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                            <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                          placeholder="Buscar por nombre o especialidad" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('tratamientos.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                            @endif
                        </form>

                        <x-primary-button type="button" @click="nuevo()">Nuevo tratamiento</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Especialidad</th>
                                    <th class="px-3 py-2">Duración estimada</th>
                                    <th class="px-3 py-2">Sesiones previstas</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($tratamientos as $t)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">
                                            <a href="{{ route('tratamientos.mostrar', $t->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $t->nombre }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3">{{ $t->especialidad->nombre }}</td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $t->duracion_estimada_minutos }} min</td>
                                        <td class="px-3 py-3">
                                            {{ $t->numero_sesiones_previstas }}
                                            @if ($t->requiere_sesiones)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">(por sesiones)</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($t->estaDisponible())
                                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200 whitespace-nowrap">Disponible</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300 whitespace-nowrap">No disponible</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <x-secondary-button
                                                    @click="editar({{ Js::from($t->only(['id', 'nombre', 'descripcion', 'especialidad_id', 'duracion_estimada_minutos', 'numero_sesiones_previstas', 'requiere_sesiones'])) }})">
                                                    Modificar
                                                </x-secondary-button>

                                                {{-- CU16 Gestionar tarifas y CU19 Definir estudios requeridos --}}
                                                <a href="{{ route('tarifas.mostrar', $t->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    Tarifas
                                                </a>
                                                <a href="{{ route('tratamientos.estudios', $t->id) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                                                    Estudios requeridos
                                                </a>

                                                {{-- Dar de baja / habilitar (el id viaja oculto; no se borra, RNF-06) --}}
                                                @if ($t->estaDisponible())
                                                    <form method="POST" action="{{ route('tratamientos.eliminar') }}"
                                                          onsubmit="return confirm({{ Js::from('¿Dar de baja el tratamiento '.$t->nombre.'? Ya no se ofrecerá al programar citas.') }});">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $t->id }}">
                                                        <x-danger-button>Dar de baja</x-danger-button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('tratamientos.habilitar') }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $t->id }}">
                                                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
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

            {{-- Formulario de registro / modificación --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="form.id ? 'Modificar tratamiento' : 'Registrar tratamiento'"></h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            La duración estimada se propone al programar una cita con este tratamiento (RN-08).
                        </p>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" :action="form.id ? {{ Js::from(route('tratamientos.modificar')) }} : {{ Js::from(route('tratamientos.guardar')) }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        {{-- El id viaja oculto solo al modificar --}}
                        <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="nombre" value="Nombre" />
                                <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" x-model="form.nombre" maxlength="60" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('nombre')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="especialidad_id" value="Especialidad" />
                                <select id="especialidad_id" name="especialidad_id" class="{{ $claseCampo }}" x-model="form.especialidad_id" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($especialidades as $e)
                                        <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                    @endforeach
                                </select>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('especialidad_id')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="duracion_estimada_minutos" value="Duración estimada (minutos)" />
                                <x-text-input id="duracion_estimada_minutos" name="duracion_estimada_minutos" type="number" min="1" max="600" step="1" class="mt-1 block w-full" x-model="form.duracion_estimada_minutos" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('duracion_estimada_minutos')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="numero_sesiones_previstas" value="Sesiones previstas" />
                                <x-text-input id="numero_sesiones_previstas" name="numero_sesiones_previstas" type="number" min="1" max="100" step="1" class="mt-1 block w-full" x-model="form.numero_sesiones_previstas" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('numero_sesiones_previstas')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                {{-- El oculto envía 0 cuando la casilla no está marcada --}}
                                <input type="hidden" name="requiere_sesiones" value="0">
                                <label for="requiere_sesiones" class="inline-flex items-center gap-2">
                                    <input id="requiere_sesiones" name="requiere_sesiones" type="checkbox" value="1" x-model="form.requiere_sesiones"
                                           class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">Requiere varias sesiones</span>
                                </label>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('requiere_sesiones')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="descripcion" value="Descripción (opcional)" />
                                <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo }}" x-model="form.descripcion" maxlength="1000"></textarea>
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
