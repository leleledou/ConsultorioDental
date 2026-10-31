{{-- CU01 Iniciar sesión: formulario de acceso con nombre de usuario (no email). --}}
<x-guest-layout>
    <h1 class="font-display text-2xl font-bold text-ink">Iniciar sesión</h1>
    <p class="mt-1 text-sm text-ink-soft">Ingrese con su usuario y contraseña.</p>

    {{-- Mensajes arriba del formulario: éxito (p. ej. al cerrar sesión), avisos
         (sesión expirada, cuenta inhabilitada) y el error de credenciales (clave "usuario"). --}}
    @if (session('exito'))
        <div class="mt-5 rounded-lg border border-green-200 bg-green-50 p-3 text-sm font-medium text-green-800" role="status">
            {{ session('exito') }}
        </div>
    @endif

    @if (session('aviso'))
        <div class="mt-5 rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-sm font-medium text-yellow-800" role="status">
            {{ session('aviso') }}
        </div>
    @endif

    @if ($errors->get('usuario'))
        <div class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3">
            <x-input-error :messages="$errors->get('usuario')" />
        </div>
    @endif

    <form method="POST" action="{{ route('login.ingresar') }}" class="mt-6">
        @csrf

        <!-- Usuario -->
        <div>
            <x-input-label for="usuario" value="Usuario" />
            <x-text-input id="usuario" class="mt-1.5 block w-full" type="text" name="usuario" :value="old('usuario')" required autofocus autocomplete="username" />
        </div>

        <!-- Contraseña -->
        <div class="mt-5">
            <x-input-label for="password" value="Contraseña" />

            <x-text-input id="password" class="mt-1.5 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Sin "Recordarme": una sesión recordada saltaría el cierre por inactividad (RNF-03).
             Sin "¿Olvidó su contraseña?": el acceso lo restablece el administrador (CU06). --}}
        <div class="mt-7">
            <x-primary-button class="w-full py-3">
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-xs text-ink-soft">
        ¿Problemas para ingresar? El administrador del consultorio puede restablecer su acceso.
    </p>
</x-guest-layout>
