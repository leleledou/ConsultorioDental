{{-- CU04 Consultar usuario y CU06 paso 2: detalle del usuario con el resumen de su acceso. --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Usuario: {{ $usuario->nombres }} {{ $usuario->apellidos }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            {{-- Datos personales --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del usuario</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombres</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->nombres }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Apellidos</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">CI</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->ci }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Matrícula profesional</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->matricula_profesional }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Especialidad</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->especialidad?->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Teléfono</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->telefono }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Correo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->correo }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Usuario</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->usuario }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Rol</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->rol === 'administrador' ? 'Administrador' : 'Odontólogo' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Resumen del acceso (RN-03 y CU06) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Acceso</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                        <dd class="mt-1">
                            @if ($usuario->estado === 'activo')
                                <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">Activo</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">Inactivo</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Intentos fallidos</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->intentos_fallidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Bloqueo vigente</dt>
                        <dd class="mt-1">
                            @if ($usuario->estaBloqueado())
                                <span class="inline-flex rounded-full bg-red-100 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-800 dark:text-red-200">
                                    Bloqueado hasta {{ $usuario->bloqueado_hasta->format('d/m/Y H:i') }}
                                    ({{ $usuario->minutosBloqueoRestantes() }} min)
                                </span>
                            @else
                                <span class="font-medium text-gray-900 dark:text-gray-100">Sin bloqueo</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Debe cambiar la contraseña</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $usuario->debe_cambiar_contrasena ? 'Sí' : 'No' }}</dd>
                    </div>
                </dl>
            </div>

            <div>
                <a href="{{ route('usuarios.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a usuarios
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
