{{-- Cabecera superior: contexto (paquete actual y fecha) y menú del usuario. --}}
@php
    $rolVisible = Auth::user()->rol === 'administrador' ? 'Administrador' : 'Odontólogo';
    $iniciales = mb_strtoupper(mb_substr(Auth::user()->nombres, 0, 1).mb_substr(Auth::user()->apellidos, 0, 1));
    $paqueteActual = collect($paquetes)->first($paqueteActivo);
    $fechaHoy = ucfirst(now()->locale('es')->translatedFormat('l, j \d\e F \d\e Y'));
@endphp

<div class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-line bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
    <!-- Botón del menú móvil -->
    <button type="button" class="-ms-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800 lg:hidden"
            @click="menu = true" aria-controls="menu-lateral" :aria-expanded="menu" aria-label="Abrir menú">
        <x-icon name="menu" class="h-6 w-6" />
    </button>

    <!-- Contexto -->
    <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-ink">
            @if ($paqueteActual)
                <span class="me-1 text-brand-700">{{ $paqueteActual['codigo'] }}</span> {{ $paqueteActual['nombre'] }}
            @else
                Panel principal
            @endif
        </p>
        <p class="hidden truncate text-xs text-ink-soft sm:block">{{ $fechaHoy }}</p>
    </div>

    <!-- Menú desplegable del usuario -->
    <x-dropdown align="right" width="w-64">
        <x-slot name="trigger">
            <button type="button" class="flex items-center gap-2.5 rounded-full border border-line bg-white py-1 ps-1 pe-3 text-sm transition hover:border-brand-300 hover:bg-brand-50 focus:outline-none" aria-label="Menú del usuario">
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-700 text-xs font-bold text-white">{{ $iniciales }}</span>
                <span class="hidden text-start leading-tight sm:block">
                    <span class="block max-w-[11rem] truncate text-sm font-semibold text-ink">{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</span>
                    <span class="block text-xs text-ink-soft">{{ $rolVisible }}</span>
                </span>
                <x-icon name="chevron" class="h-4 w-4 text-ink-soft" />
            </button>
        </x-slot>

        <x-slot name="content">
            <!-- Usuario y rol -->
            <div class="border-b border-line bg-surface px-4 py-3">
                <div class="truncate text-sm font-semibold text-ink">{{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}</div>
                <div class="text-xs text-ink-soft">{{ Auth::user()->usuario }} · {{ $rolVisible }}</div>
            </div>

            <!-- Tablero (los dos roles) -->
            <x-dropdown-link :href="route('panel')">
                <span class="flex items-center gap-3"><x-icon name="home" class="h-4 w-4" /> Panel</span>
            </x-dropdown-link>

            <!-- CU03 Cambiar contraseña (P1) -->
            <x-dropdown-link :href="route('contrasena.mostrar')">
                <span class="flex items-center gap-3"><x-icon name="key" class="h-4 w-4" /> Cambiar contraseña</span>
            </x-dropdown-link>

            <!-- CU02 Cerrar sesión (POST con token CSRF) -->
            <form method="POST" action="{{ route('logout') }}" class="border-t border-line">
                @csrf

                <x-dropdown-link :href="route('logout')"
                        onclick="event.preventDefault();
                                    this.closest('form').submit();">
                    <span class="flex items-center gap-3"><x-icon name="logout" class="h-4 w-4" /> Cerrar sesión</span>
                </x-dropdown-link>
            </form>
        </x-slot>
    </x-dropdown>
</div>
