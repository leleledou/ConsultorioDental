{{-- Barra lateral con los 5 paquetes. RN-05: muestra solo las opciones del rol del usuario
     (las calcula MenuService en el layout: $paquetes y $paqueteActivo). --}}

<!-- Fondo oscuro del menú móvil -->
<div x-show="menu" x-cloak
     x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-brand-900/50 lg:hidden" @click="menu = false" aria-hidden="true"></div>

<aside id="menu-lateral"
       class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col border-r border-line bg-white transition-transform duration-200 ease-out lg:translate-x-0"
       :class="{ 'translate-x-0 shadow-pop': menu }"
       aria-label="Menú principal">
    <!-- Logo oficial (fondo blanco, proporción original 1983 × 793) -->
    <div class="flex items-center justify-between border-b border-line px-5">
        <a href="{{ route('panel') }}" class="block py-1" aria-label="{{ config('app.name', 'DentalRox') }}: ir al panel">
            <img src="{{ asset('images/dentalrox-logo.png') }}" alt="{{ config('app.name', 'DentalRox') }}"
                 width="1983" height="793" class="h-[84px] w-auto [filter:brightness(1.012)]">
        </a>
        <button type="button" class="rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800 lg:hidden" @click="menu = false" aria-label="Cerrar menú">
            <x-icon name="close" />
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4">
        <a href="{{ route('panel') }}"
           @class(['nav-item', 'nav-item-active' => request()->routeIs('panel')])
           @if (request()->routeIs('panel')) aria-current="page" @endif>
            <span class="flex items-center gap-3"><x-icon name="home" class="h-5 w-5" /> Panel</span>
        </a>

        <div class="mt-5 space-y-1">
            @foreach ($paquetes as $paquete)
                @php $activo = $paqueteActivo($paquete); @endphp
                <div x-data="{ abierto: {{ Js::from($activo) }} }">
                    <button type="button" @click="abierto = ! abierto" :aria-expanded="abierto"
                            @class([
                                'flex w-full items-center justify-between gap-2 rounded-lg px-3 py-2.5 text-start transition-colors duration-150 hover:bg-surface',
                                'text-ink' => $activo,
                                'text-ink-soft' => ! $activo,
                            ])>
                        <span class="flex min-w-0 items-center gap-3">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                'bg-brand-700 text-white' => $activo,
                                'bg-brand-50 text-brand-700' => ! $activo,
                            ])>
                                <x-icon :name="$paquete['codigo']" class="h-[18px] w-[18px]" />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-[11px] font-bold uppercase tracking-wider text-brand-700">{{ $paquete['codigo'] }}</span>
                                <span class="block truncate text-sm font-semibold">{{ $paquete['corto'] }}</span>
                            </span>
                        </span>
                        <x-icon name="chevron" class="h-4 w-4 shrink-0 transition-transform duration-150" ::class="{ 'rotate-180': abierto }" />
                    </button>

                    <ul x-show="abierto" x-cloak
                        x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                        class="ms-[1.15rem] mt-1 space-y-0.5 border-s border-line ps-3">
                        @foreach ($paquete['opciones'] as $opcion)
                            <li>
                                @if ($opcion['ruta'])
                                    @php $actual = $opcion['activa'] && request()->routeIs($opcion['activa']); @endphp
                                    <a href="{{ route($opcion['ruta']) }}"
                                       @class(['nav-item', 'nav-item-active' => $actual])
                                       @if ($actual) aria-current="page" @endif>
                                        {{ $opcion['nombre'] }}
                                    </a>
                                @else
                                    {{-- Opción inhabilitada: en gris y sin enlace --}}
                                    <div class="flex cursor-not-allowed items-center justify-between gap-2 rounded-lg px-3 py-2 text-sm text-ink-soft" aria-disabled="true">
                                        <span>{{ $opcion['nombre'] }}</span>
                                        <span class="shrink-0 rounded-full bg-surface px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-ink-soft">Próximo ciclo</span>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </nav>

    <div class="border-t border-line px-5 py-3 text-xs text-ink-soft">
        Gestión de pacientes, citas, tratamientos y pagos
    </div>
</aside>
