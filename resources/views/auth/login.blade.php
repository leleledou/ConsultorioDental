{{-- CU01 Iniciar sesión: formulario de acceso con nombre de usuario (no email). --}}
<x-guest-layout>
    {{-- Mensajes arriba del formulario: éxito (p. ej. al cerrar sesión), avisos
         (sesión expirada, cuenta inhabilitada) y el error de credenciales (clave "usuario"). --}}
    @if (session('exito'))
        <div class="mb-4 font-medium text-sm text-green-600 dark:text-green-400">
            {{ session('exito') }}
        </div>
    @endif

    @if (session('aviso'))
        <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-3 text-sm font-medium text-yellow-800 dark:text-yellow-200">
            {{ session('aviso') }}
        </div>
    @endif

    <x-input-error :messages="$errors->get('usuario')" class="mb-4" />

    <form method="POST" action="{{ route('login.ingresar') }}">
        @csrf

        <!-- Usuario -->
        <div>
            <x-input-label for="usuario" value="Usuario" />
            <x-text-input id="usuario" class="block mt-1 w-full" type="text" name="usuario" :value="old('usuario')" required autofocus autocomplete="username" />
        </div>

        <!-- Contraseña -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Sin "Recordarme": una sesión recordada saltaría el cierre por inactividad (RNF-03).
             Sin "¿Olvidó su contraseña?": el acceso lo restablece el administrador (CU06). --}}
        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
