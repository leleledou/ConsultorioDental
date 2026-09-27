{{-- CU03 Cambiar contraseña (basada en el formulario de contraseña del perfil de Breeze). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Cambiar contraseña
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Aviso destacado (p. ej. obligación de reemplazar la contraseña temporal, CU06) --}}
            @if (session('aviso'))
                <div class="rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 text-sm font-medium text-yellow-800 dark:text-yellow-200">
                    {{ session('aviso') }}
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Cambiar contraseña
                            </h2>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Al guardar, se cerrarán sus sesiones abiertas en otros equipos.
                            </p>
                        </header>

                        <form method="POST" action="{{ route('contrasena.guardar') }}" class="mt-6 space-y-6">
                            @csrf

                            <!-- Contraseña actual -->
                            <div>
                                <x-input-label for="contrasena_actual" value="Contraseña actual" />
                                <x-text-input id="contrasena_actual" name="contrasena_actual" type="password" class="mt-1 block w-full" autocomplete="current-password" required />
                                <x-input-error :messages="$errors->get('contrasena_actual')" class="mt-2" />
                            </div>

                            <!-- Nueva contraseña -->
                            <div>
                                <x-input-label for="password" value="Nueva contraseña" />
                                <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                                {{-- Ayuda con la política RN-01 --}}
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Mínimo 8 caracteres, con mayúsculas, minúsculas, números y caracteres especiales, y sin espacios.
                                </p>
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <!-- Confirmar nueva contraseña -->
                            <div>
                                <x-input-label for="password_confirmation" value="Confirmar nueva contraseña" />
                                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" required />
                                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                            </div>

                            <div class="flex items-center gap-4">
                                <x-primary-button>Guardar</x-primary-button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
