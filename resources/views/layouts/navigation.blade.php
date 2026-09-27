{{-- Menú principal con los 5 paquetes. RN-05: muestra solo las opciones del rol del usuario. --}}
@php
    $esAdministrador = Auth::user()->rol === 'administrador';
    $rolVisible = $esAdministrador ? 'Administrador' : 'Odontólogo';

    // Paquetes y opciones visibles para este usuario (App\Services\MenuService).
    $paquetes = \App\Services\MenuService::paquetes(Auth::user());

    // Un paquete se marca como actual si alguna de sus opciones es la página abierta.
    $paqueteActivo = fn ($paquete) => collect($paquete['opciones'])
        ->contains(fn ($opcion) => $opcion['activa'] && request()->routeIs($opcion['activa']));
@endphp

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Menú de navegación principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('panel') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ config('app.name', 'DentalRox') }}</span>
                    </a>
                </div>

                <!-- Menús desplegables de los paquetes -->
                <div class="hidden lg:flex lg:items-center lg:ms-8 lg:gap-1">
                    @foreach ($paquetes as $paquete)
                        <x-dropdown align="left" width="w-72">
                            <x-slot name="trigger">
                                <button @class([
                                    'inline-flex items-center px-3 py-2 rounded-md text-sm font-medium leading-4 focus:outline-none transition ease-in-out duration-150',
                                    'text-gray-900 dark:text-gray-100 bg-gray-100 dark:bg-gray-700' => $paqueteActivo($paquete),
                                    'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' => ! $paqueteActivo($paquete),
                                ])>
                                    <span class="me-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $paquete['codigo'] }}</span>
                                    {{ $paquete['corto'] }}
                                    <svg class="ms-1 fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <!-- Nombre completo del paquete -->
                                <div class="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-600">
                                    {{ $paquete['codigo'] }} · {{ $paquete['nombre'] }}
                                </div>

                                @foreach ($paquete['opciones'] as $opcion)
                                    @if ($opcion['ruta'])
                                        {{-- :class y no @class: las directivas no se procesan dentro de un componente --}}
                                        <x-dropdown-link :href="route($opcion['ruta'])"
                                                         :class="request()->routeIs($opcion['activa']) ? 'font-semibold text-indigo-700 dark:text-indigo-300' : ''">
                                            {{ $opcion['nombre'] }}
                                        </x-dropdown-link>
                                    @else
                                        {{-- Opción inhabilitada: en gris y sin enlace --}}
                                        <div class="flex items-center justify-between px-4 py-2 text-sm leading-5 text-gray-400 dark:text-gray-500 cursor-not-allowed" aria-disabled="true">
                                            <span>{{ $opcion['nombre'] }}</span>
                                            <span class="ms-2 rounded bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide">Próximo ciclo</span>
                                        </div>
                                    @endif
                                @endforeach
                            </x-slot>
                        </x-dropdown>
                    @endforeach
                </div>
            </div>

            <!-- Menú desplegable del usuario -->
            <div class="hidden lg:flex lg:items-center lg:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Usuario y rol -->
                        <div class="px-4 py-2 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
                            <div>{{ Auth::user()->usuario }}</div>
                            <div>{{ $rolVisible }}</div>
                        </div>

                        <!-- Tablero (los dos roles) -->
                        <x-dropdown-link :href="route('panel')">
                            Panel
                        </x-dropdown-link>

                        <!-- CU03 Cambiar contraseña (P1) -->
                        <x-dropdown-link :href="route('contrasena.mostrar')">
                            Cambiar contraseña
                        </x-dropdown-link>

                        <!-- CU02 Cerrar sesión (POST con token CSRF) -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Botón del menú móvil -->
            <div class="-me-2 flex items-center lg:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menú móvil (responsive): los mismos paquetes, uno debajo de otro -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden lg:hidden">
        <div class="pt-2 pb-3">
            @foreach ($paquetes as $paquete)
                <div class="pt-3">
                    <div class="px-4 pb-1 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        {{ $paquete['codigo'] }} · {{ $paquete['nombre'] }}
                    </div>

                    @foreach ($paquete['opciones'] as $opcion)
                        @if ($opcion['ruta'])
                            <x-responsive-nav-link :href="route($opcion['ruta'])" :active="request()->routeIs($opcion['activa'])">
                                {{ $opcion['nombre'] }}
                            </x-responsive-nav-link>
                        @else
                            {{-- Opción inhabilitada: en gris y sin enlace --}}
                            <div class="flex items-center justify-between ps-4 pe-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-400 dark:text-gray-500 cursor-not-allowed" aria-disabled="true">
                                <span>{{ $opcion['nombre'] }}</span>
                                <span class="rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide">Próximo ciclo</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>

        <!-- Opciones del usuario (móvil) -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200">{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->usuario }} · {{ $rolVisible }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('panel')" :active="request()->routeIs('panel')">
                    Panel
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('contrasena.mostrar')" :active="request()->routeIs('contrasena.mostrar')">
                    Cambiar contraseña
                </x-responsive-nav-link>

                <!-- CU02 Cerrar sesión (POST con token CSRF) -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
