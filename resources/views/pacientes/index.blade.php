{{-- CU08 Gestionar pacientes y CU09 Buscar paciente (los dos roles). --}}
@php
    // El formulario de registro/modificación envía un campo oculto "formulario" = "datos".
    // Así, si vuelve con errores, se sabe que los datos anteriores (old) son de este
    // formulario y no de las acciones por fila (inhabilitar / habilitar).
    $volvioFormulario = old('formulario') === 'datos';

    // Datos iniciales del formulario: los que se enviaron antes (si hubo errores) o vacíos.
    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'ci' => $volvioFormulario ? old('ci', '') : '',
        'nombres' => $volvioFormulario ? old('nombres', '') : '',
        'apellidos' => $volvioFormulario ? old('apellidos', '') : '',
        'fecha_nacimiento' => $volvioFormulario ? old('fecha_nacimiento', '') : '',
        'sexo' => $volvioFormulario ? old('sexo', '') : '',
        'telefono' => $volvioFormulario ? old('telefono', '') : '',
        'correo' => $volvioFormulario ? old('correo', '') : '',
        'direccion' => $volvioFormulario ? old('direccion', '') : '',
        'observaciones' => $volvioFormulario ? old('observaciones', '') : '',
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestionar pacientes
        </h2>
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar (CU08 paso 3).
         editar(): formulario con los datos del paciente elegido para modificar. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, ci: '', nombres: '', apellidos: '', fecha_nacimiento: '', sexo: '',
                              telefono: '', correo: '', direccion: '', observaciones: '' };
                this.mostrarFormulario();
            },
            editar(paciente) {
                this.form = { ...paciente };
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
                    {{-- CU09 paso 1: buscador y botón de registro --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <form method="GET" action="{{ route('pacientes.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                            <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                          placeholder="Buscar por CI, nombres o apellidos" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('pacientes.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                            @endif
                        </form>

                        <x-primary-button type="button" @click="nuevo()">Nuevo paciente</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">CI</th>
                                    <th class="px-3 py-2">Nombre completo</th>
                                    <th class="px-3 py-2">Edad</th>
                                    <th class="px-3 py-2">Teléfono</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($pacientes as $p)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">{{ $p->ci }}</td>
                                        <td class="px-3 py-3">
                                            <a href="{{ route('pacientes.mostrar', $p->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $p->apellidos }}, {{ $p->nombres }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">{{ $p->edad() }} años</td>
                                        <td class="px-3 py-3">{{ $p->telefono ?? '—' }}</td>
                                        <td class="px-3 py-3">
                                            @if ($p->estado === 'activo')
                                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">Activo</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                {{-- CU08: modificar (carga los datos en el formulario) --}}
                                                <x-secondary-button
                                                    @click="editar({{ Js::from($p->only(['id', 'ci', 'nombres', 'apellidos', 'sexo', 'telefono', 'correo', 'direccion', 'observaciones']) + ['fecha_nacimiento' => $p->fecha_nacimiento->toDateString()]) }})">
                                                    Modificar
                                                </x-secondary-button>

                                                {{-- CU08: inhabilitar / habilitar (el id viaja oculto) --}}
                                                @if ($p->estado === 'activo')
                                                    <form method="POST" action="{{ route('pacientes.eliminar') }}"
                                                          onsubmit="return confirm({{ Js::from('¿Inhabilitar al paciente '.$p->nombreCompleto().'? Su historia clínica se conserva.') }});">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                                        <x-danger-button>Inhabilitar</x-danger-button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('pacientes.habilitar') }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
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

            {{-- CU08 pasos 3 a 5: formulario de registro / modificación --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="form.id ? 'Modificar paciente' : 'Registrar paciente'"></h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-show="! form.id">
                            Al registrar al paciente se abre su historia clínica con la fecha de hoy.
                        </p>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" :action="form.id ? {{ Js::from(route('pacientes.modificar')) }} : {{ Js::from(route('pacientes.guardar')) }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        {{-- El id viaja oculto solo al modificar --}}
                        <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="ci" value="CI" />
                                <x-text-input id="ci" name="ci" type="text" class="mt-1 block w-full" x-model="form.ci" maxlength="15" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('ci')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="fecha_nacimiento" value="Fecha de nacimiento" />
                                <x-text-input id="fecha_nacimiento" name="fecha_nacimiento" type="date" class="mt-1 block w-full" x-model="form.fecha_nacimiento" max="{{ today()->toDateString() }}" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('fecha_nacimiento')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="nombres" value="Nombres" />
                                <x-text-input id="nombres" name="nombres" type="text" class="mt-1 block w-full" x-model="form.nombres" maxlength="50" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('nombres')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="apellidos" value="Apellidos" />
                                <x-text-input id="apellidos" name="apellidos" type="text" class="mt-1 block w-full" x-model="form.apellidos" maxlength="50" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('apellidos')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="sexo" value="Sexo" />
                                <select id="sexo" name="sexo" class="{{ $claseCampo }}" x-model="form.sexo" required>
                                    <option value="">Seleccione…</option>
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                </select>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('sexo')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="telefono" value="Teléfono" />
                                <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" x-model="form.telefono" maxlength="15" />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('telefono')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="correo" value="Correo" />
                                <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" x-model="form.correo" maxlength="100" />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('correo')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="direccion" value="Dirección" />
                                <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full" x-model="form.direccion" maxlength="150" />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('direccion')" class="mt-2" /> @endif
                            </div>

                            <div class="sm:col-span-2">
                                <x-input-label for="observaciones" value="Observaciones" />
                                <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo }}" x-model="form.observaciones" maxlength="1000"></textarea>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('observaciones')" class="mt-2" /> @endif
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
