{{-- CU04 Gestionar usuarios, CU05 Asignar rol y CU06 Restablecer acceso (solo administrador). --}}
@php
    // El formulario de registro/modificación envía un campo oculto "formulario" = "datos".
    // Así, si vuelve con errores, se sabe que los datos anteriores (old) son de este
    // formulario y no de las acciones por fila (asignar rol, inhabilitar, etc.).
    $volvioFormulario = old('formulario') === 'datos';

    // Datos iniciales del formulario: los que se enviaron antes (si hubo errores) o vacíos.
    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'nombres' => $volvioFormulario ? old('nombres', '') : '',
        'apellidos' => $volvioFormulario ? old('apellidos', '') : '',
        'ci' => $volvioFormulario ? old('ci', '') : '',
        'matricula_profesional' => $volvioFormulario ? old('matricula_profesional', '') : '',
        'especialidad_id' => $volvioFormulario ? old('especialidad_id', '') : '',
        'correo' => $volvioFormulario ? old('correo', '') : '',
        'telefono' => $volvioFormulario ? old('telefono', '') : '',
        'usuario' => $volvioFormulario ? old('usuario', '') : '',
        'rol' => $volvioFormulario ? old('rol', 'odontologo') : 'odontologo',
    ];

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestionar usuarios
        </h2>
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar (CU04 paso 3).
         editar(): formulario con los datos del usuario elegido para modificar. --}}
    <div class="py-12"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombres: '', apellidos: '', ci: '', matricula_profesional: '',
                              especialidad_id: '', correo: '', telefono: '', usuario: '', rol: 'odontologo' };
                this.mostrarFormulario();
            },
            editar(usuario) {
                this.form = { ...usuario, especialidad_id: String(usuario.especialidad_id), rol: 'odontologo' };
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

            {{-- CU06 paso 7: la contraseña temporal se muestra una sola vez (dato de sesión de una petición). --}}
            @if (session('contrasena_temporal'))
                <div class="rounded-md border-2 border-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 p-4 text-yellow-900 dark:text-yellow-100">
                    <p class="font-semibold">Contraseña temporal del usuario «{{ session('usuario_restablecido') }}»</p>
                    <p class="mt-2 font-mono text-2xl tracking-wider select-all">{{ session('contrasena_temporal') }}</p>
                    <p class="mt-2 text-sm">
                        Se muestra una sola vez: cópiela y entréguela personalmente. El usuario deberá
                        cambiarla en su primer ingreso.
                    </p>
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
                    {{-- CU04 paso 2: buscador y botón de registro --}}
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <form method="GET" action="{{ route('usuarios.listar') }}" class="flex gap-2 w-full sm:max-w-md">
                            <x-text-input name="buscar" type="text" class="block w-full" :value="$buscar"
                                          placeholder="Buscar por nombres, apellidos, usuario o CI" />
                            <x-primary-button>Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('usuarios.listar') }}" class="self-center text-sm text-gray-600 dark:text-gray-400 underline">Limpiar</a>
                            @endif
                        </form>

                        <x-primary-button type="button" @click="nuevo()">Nuevo usuario</x-primary-button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Nombre</th>
                                    <th class="px-3 py-2">Usuario</th>
                                    <th class="px-3 py-2">Rol</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Bloqueo</th>
                                    <th class="px-3 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($usuarios as $u)
                                    <tr class="align-top">
                                        <td class="px-3 py-3">
                                            <a href="{{ route('usuarios.mostrar', $u->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $u->apellidos }}, {{ $u->nombres }}
                                            </a>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $u->especialidad?->nombre }}</div>
                                        </td>
                                        <td class="px-3 py-3">{{ $u->usuario }}</td>
                                        <td class="px-3 py-3">{{ $u->rol === 'administrador' ? 'Administrador' : 'Odontólogo' }}</td>
                                        <td class="px-3 py-3">
                                            @if ($u->estado === 'activo')
                                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">Activo</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">Inactivo</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            @if ($u->estaBloqueado())
                                                <span class="inline-flex rounded-full bg-red-100 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">
                                                    Bloqueado ({{ $u->minutosBloqueoRestantes() }} min)
                                                </span>
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-wrap items-center gap-2">
                                                {{-- CU04: modificar (carga los datos en el formulario) --}}
                                                <x-secondary-button
                                                    @click="editar({{ Js::from($u->only(['id', 'nombres', 'apellidos', 'ci', 'matricula_profesional', 'especialidad_id', 'correo', 'telefono', 'usuario'])) }})">
                                                    Modificar
                                                </x-secondary-button>

                                                {{-- CU04 paso 8: inhabilitar / habilitar (el id viaja oculto) --}}
                                                @if ($u->estado === 'activo')
                                                    <form method="POST" action="{{ route('usuarios.eliminar') }}"
                                                          onsubmit="return confirm({{ Js::from('¿Inhabilitar al usuario '.$u->usuario.'? Sus registros se conservan.') }});">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $u->id }}">
                                                        <x-danger-button>Inhabilitar</x-danger-button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('usuarios.habilitar') }}">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $u->id }}">
                                                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                                                    </form>
                                                @endif

                                                {{-- CU05: asignar rol --}}
                                                <form method="POST" action="{{ route('usuarios.asignarRol') }}" class="flex items-center gap-1">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $u->id }}">
                                                    <select name="rol" class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm text-xs py-1.5">
                                                        <option value="administrador" @selected($u->rol === 'administrador')>Administrador</option>
                                                        <option value="odontologo" @selected($u->rol === 'odontologo')>Odontólogo</option>
                                                    </select>
                                                    <x-secondary-button type="submit">Asignar rol</x-secondary-button>
                                                </form>

                                                {{-- CU06: restablecer acceso --}}
                                                <form method="POST" action="{{ route('usuarios.restablecer') }}"
                                                      onsubmit="return confirm({{ Js::from('¿Restablecer el acceso de '.$u->usuario.'? Se generará una contraseña temporal y se cerrarán sus sesiones.') }});">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $u->id }}">
                                                    <x-secondary-button type="submit">Restablecer acceso</x-secondary-button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $usuarios->links() }}
                </div>
            </div>

            {{-- CU04 pasos 3 a 6: formulario de registro / modificación --}}
            <div x-ref="formulario" x-show="abierto" x-cloak class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <section>
                    <header>
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100"
                            x-text="form.id ? 'Modificar usuario' : 'Registrar usuario'"></h2>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-show="! form.id">
                            El usuario deberá cambiar la contraseña inicial en su primer ingreso.
                        </p>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400" x-show="form.id">
                            La contraseña, el rol y el estado se cambian con sus propias acciones.
                        </p>
                    </header>

                    @if ($volvioFormulario && $errors->any())
                        <div class="mt-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                            Revise los datos marcados en el formulario.
                        </div>
                    @endif

                    <form method="POST" :action="form.id ? {{ Js::from(route('usuarios.modificar')) }} : {{ Js::from(route('usuarios.guardar')) }}" class="mt-6 space-y-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        {{-- El id viaja oculto solo al modificar --}}
                        <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
                                <x-input-label for="ci" value="CI" />
                                <x-text-input id="ci" name="ci" type="text" class="mt-1 block w-full" x-model="form.ci" maxlength="15" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('ci')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="matricula_profesional" value="Matrícula profesional" />
                                <x-text-input id="matricula_profesional" name="matricula_profesional" type="text" class="mt-1 block w-full" x-model="form.matricula_profesional" maxlength="20" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('matricula_profesional')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="especialidad_id" value="Especialidad" />
                                <select id="especialidad_id" name="especialidad_id" class="{{ $claseCampo }}" x-model="form.especialidad_id" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($especialidades as $especialidad)
                                        <option value="{{ $especialidad->id }}">{{ $especialidad->nombre }}</option>
                                    @endforeach
                                </select>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('especialidad_id')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="telefono" value="Teléfono" />
                                <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" x-model="form.telefono" maxlength="15" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('telefono')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="correo" value="Correo" />
                                <x-text-input id="correo" name="correo" type="email" class="mt-1 block w-full" x-model="form.correo" maxlength="100" required />
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('correo')" class="mt-2" /> @endif
                            </div>

                            <div>
                                <x-input-label for="usuario" value="Usuario" />
                                <x-text-input id="usuario" name="usuario" type="text" class="mt-1 block w-full" x-model="form.usuario" minlength="4" maxlength="30" required />
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Entre 4 y 30 caracteres, sin espacios.</p>
                                @if ($volvioFormulario) <x-input-error :messages="$errors->get('usuario')" class="mt-2" /> @endif
                            </div>

                            {{-- Solo al registrar: rol (CU05) y contraseña inicial. Deshabilitados no se envían. --}}
                            <template x-if="! form.id">
                                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-6">
                                    <div>
                                        <x-input-label for="rol" value="Rol" />
                                        <select id="rol" name="rol" class="{{ $claseCampo }}" x-model="form.rol" required>
                                            <option value="odontologo">Odontólogo</option>
                                            <option value="administrador">Administrador</option>
                                        </select>
                                        @if ($volvioFormulario) <x-input-error :messages="$errors->get('rol')" class="mt-2" /> @endif
                                    </div>

                                    <div>
                                        <x-input-label for="password" value="Contraseña inicial" />
                                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            Mínimo 8 caracteres, con mayúsculas, minúsculas, números y caracteres especiales, y sin espacios.
                                        </p>
                                        @if ($volvioFormulario) <x-input-error :messages="$errors->get('password')" class="mt-2" /> @endif
                                    </div>

                                    <div>
                                        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
                                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                                    </div>
                                </div>
                            </template>
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
