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

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Gestionar usuarios" subtitulo="Registre usuarios, asigne su rol y restablezca su acceso." />
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar (CU04 paso 3).
         editar(): formulario con los datos del usuario elegido para modificar. --}}
    <x-pagina
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
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        {{-- CU06 paso 7: la contraseña temporal se muestra una sola vez (dato de sesión de una petición). --}}
        @if (session('contrasena_temporal'))
            <x-alerta tipo="aviso" class="!border-2 !border-amber-400">
                <p class="font-bold">Contraseña temporal del usuario «{{ session('usuario_restablecido') }}»</p>
                <p class="mt-2 break-all rounded-lg bg-white px-3 py-2 font-mono text-xl tracking-wider text-ink select-all sm:text-2xl">{{ session('contrasena_temporal') }}</p>
                <p class="mt-2 font-medium">
                    Se muestra una sola vez: cópiela y entréguela personalmente. El usuario deberá
                    cambiarla en su primer ingreso.
                </p>
            </x-alerta>
        @endif

        {{-- Errores de las acciones por fila y del buscador --}}
        @if ($errors->any() && ! $volvioFormulario)
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <section class="dr-card overflow-hidden" aria-label="Listado de usuarios">
            {{-- CU04 paso 2: buscador y botón de registro --}}
            <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('usuarios.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar usuario</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar"
                                      placeholder="Buscar por nombres, apellidos, usuario o CI" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('usuarios.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <x-primary-button type="button" @click="nuevo()" class="w-full lg:w-auto">
                    <x-icon name="plus" class="h-5 w-5" /> Nuevo usuario
                </x-primary-button>
            </div>

            <div class="flex items-center justify-between gap-3 bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                <p>
                    @if ($buscar)
                        <span class="font-semibold text-ink">{{ $usuarios->total() }}</span> resultado(s) para «{{ $buscar }}»
                    @else
                        <span class="font-semibold text-ink">{{ $usuarios->total() }}</span> usuario(s) registrado(s)
                    @endif
                </p>
            </div>

            @if ($usuarios->isEmpty())
                {{-- Estado vacío --}}
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="users" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">No se encontraron usuarios.</p>
                    @if ($buscar)
                        <p class="mt-1 text-sm text-ink-soft">Revise los nombres, el usuario o el CI e intente de nuevo.</p>
                    @endif
                </div>
            @else
                {{-- Tabla (escritorio ancho, desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Usuarios registrados</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Usuario</th>
                                <th scope="col">Rol</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Bloqueo</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($usuarios as $u)
                                <tr class="align-top">
                                    <td>
                                        <a href="{{ route('usuarios.mostrar', $u->id) }}" class="group flex items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-800 ring-1 ring-brand-100" aria-hidden="true">
                                                {{ mb_strtoupper(mb_substr($u->nombres, 0, 1).mb_substr($u->apellidos, 0, 1)) }}
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block font-semibold text-ink group-hover:text-brand-800 group-hover:underline">{{ $u->apellidos }}, {{ $u->nombres }}</span>
                                                <span class="block text-xs text-ink-soft">{{ $u->especialidad?->nombre }}</span>
                                            </span>
                                        </a>
                                    </td>
                                    <td class="whitespace-nowrap font-medium text-ink">{{ $u->usuario }}</td>
                                    <td><x-insignia :tono="$u->rol === 'administrador' ? 'marca' : 'info'">{{ $u->rol === 'administrador' ? 'Administrador' : 'Odontólogo' }}</x-insignia></td>
                                    <td><x-insignia-activo :activo="$u->estado === 'activo'" /></td>
                                    <td>
                                        @if ($u->estaBloqueado())
                                            <x-insignia tono="error">Bloqueado ({{ $u->minutosBloqueoRestantes() }} min)</x-insignia>
                                        @else
                                            <span class="text-ink-mute">—</span>
                                        @endif
                                    </td>
                                    <td><x-usuarios.acciones :u="$u" sufijo="-tabla" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($usuarios as $u)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('usuarios.mostrar', $u->id) }}" class="flex min-w-0 items-center gap-3">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-800 ring-1 ring-brand-100" aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($u->nombres, 0, 1).mb_substr($u->apellidos, 0, 1)) }}
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block break-words font-semibold text-ink">{{ $u->apellidos }}, {{ $u->nombres }}</span>
                                        <span class="block text-xs text-ink-soft">{{ $u->especialidad?->nombre }}</span>
                                    </span>
                                </a>
                                <x-insignia-activo :activo="$u->estado === 'activo'" />
                            </div>

                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Usuario</dt><dd class="break-words font-medium text-ink">{{ $u->usuario }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Rol</dt><dd class="mt-0.5"><x-insignia :tono="$u->rol === 'administrador' ? 'marca' : 'info'">{{ $u->rol === 'administrador' ? 'Administrador' : 'Odontólogo' }}</x-insignia></dd></div>
                                <div class="col-span-2 min-w-0">
                                    <dt class="text-xs text-ink-soft">Bloqueo</dt>
                                    <dd class="mt-0.5 font-medium text-ink">
                                        @if ($u->estaBloqueado())
                                            <x-insignia tono="error">Bloqueado ({{ $u->minutosBloqueoRestantes() }} min)</x-insignia>
                                        @else
                                            <span class="text-ink-mute">—</span>
                                        @endif
                                    </dd>
                                </div>
                            </dl>

                            <x-usuarios.acciones :u="$u" sufijo="-tarjeta" />
                        </li>
                    @endforeach
                </ul>

                @if ($usuarios->hasPages())
                    <div class="border-t border-line p-4 sm:px-5">
                        {{ $usuarios->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- CU04 pasos 3 a 6: formulario de registro / modificación --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <div>
                    <h3 id="titulo-formulario" class="text-lg font-bold text-ink"
                        x-text="form.id ? 'Modificar usuario' : 'Registrar usuario'">Registrar usuario</h3>
                    <p class="mt-0.5 text-sm text-ink-soft" x-show="! form.id">
                        El usuario deberá cambiar la contraseña inicial en su primer ingreso.
                    </p>
                    <p class="mt-0.5 text-sm text-ink-soft" x-show="form.id">
                        La contraseña, el rol y el estado se cambian con sus propias acciones.
                    </p>
                </div>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('usuarios.modificar')) }} : {{ Js::from(route('usuarios.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="space-y-8">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos personales</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="nombres" label="Nombres" :requerido="true" :errores="$erroresDe('nombres')">
                                <x-text-input id="nombres" name="nombres" type="text" x-model="form.nombres" maxlength="50" required :error="$volvioFormulario && $errors->has('nombres')" />
                            </x-campo>

                            <x-campo name="apellidos" label="Apellidos" :requerido="true" :errores="$erroresDe('apellidos')">
                                <x-text-input id="apellidos" name="apellidos" type="text" x-model="form.apellidos" maxlength="50" required :error="$volvioFormulario && $errors->has('apellidos')" />
                            </x-campo>

                            <x-campo name="ci" label="CI" :requerido="true" :errores="$erroresDe('ci')">
                                <x-text-input id="ci" name="ci" type="text" x-model="form.ci" maxlength="15" required :error="$volvioFormulario && $errors->has('ci')" />
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos profesionales</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="matricula_profesional" label="Matrícula profesional" :requerido="true" :errores="$erroresDe('matricula_profesional')">
                                <x-text-input id="matricula_profesional" name="matricula_profesional" type="text" x-model="form.matricula_profesional" maxlength="20" required :error="$volvioFormulario && $errors->has('matricula_profesional')" />
                            </x-campo>

                            <x-campo name="especialidad_id" label="Especialidad" :requerido="true" :errores="$erroresDe('especialidad_id')">
                                <select id="especialidad_id" name="especialidad_id" class="{{ $claseCampo('especialidad_id') }}" x-model="form.especialidad_id" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($especialidades as $especialidad)
                                        <option value="{{ $especialidad->id }}">{{ $especialidad->nombre }}</option>
                                    @endforeach
                                </select>
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Contacto</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="telefono" label="Teléfono" :requerido="true" :errores="$erroresDe('telefono')">
                                <x-text-input id="telefono" name="telefono" type="text" x-model="form.telefono" maxlength="15" required :error="$volvioFormulario && $errors->has('telefono')" />
                            </x-campo>

                            <x-campo name="correo" label="Correo" :requerido="true" :errores="$erroresDe('correo')">
                                <x-text-input id="correo" name="correo" type="email" x-model="form.correo" maxlength="100" required :error="$volvioFormulario && $errors->has('correo')" />
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Acceso al sistema</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="usuario" label="Usuario" :requerido="true" ayuda="Entre 4 y 30 caracteres, sin espacios." :errores="$erroresDe('usuario')">
                                <x-text-input id="usuario" name="usuario" type="text" x-model="form.usuario" minlength="4" maxlength="30" required :error="$volvioFormulario && $errors->has('usuario')" />
                            </x-campo>

                            {{-- Solo al registrar: rol (CU05) y contraseña inicial. Deshabilitados no se envían. --}}
                            <template x-if="! form.id">
                                <div class="grid grid-cols-1 gap-5 sm:col-span-2 sm:grid-cols-2">
                                    <x-campo name="rol" label="Rol" :requerido="true" :errores="$erroresDe('rol')">
                                        <select id="rol" name="rol" class="{{ $claseCampo('rol') }}" x-model="form.rol" required>
                                            <option value="odontologo">Odontólogo</option>
                                            <option value="administrador">Administrador</option>
                                        </select>
                                    </x-campo>

                                    <div class="hidden sm:block"></div>

                                    <x-campo name="password" label="Contraseña inicial" :requerido="true"
                                             ayuda="Mínimo 8 caracteres, con mayúsculas, minúsculas, números y caracteres especiales, y sin espacios."
                                             :errores="$erroresDe('password')">
                                        <x-text-input id="password" name="password" type="password" autocomplete="new-password" required :error="$volvioFormulario && $errors->has('password')" />
                                    </x-campo>

                                    <x-campo name="password_confirmation" label="Confirmar contraseña" :requerido="true">
                                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required />
                                    </x-campo>
                                </div>
                            </template>
                        </div>
                    </fieldset>

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row">
                            <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                            <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Registrar'">Guardar</x-primary-button>
                        </div>
                    </div>
                </div>
            </form>
        </section>
    </x-pagina>
</x-app-layout>
