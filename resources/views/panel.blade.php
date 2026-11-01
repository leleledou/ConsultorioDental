{{-- Panel: pantalla inicial de los dos roles. Una tarjeta por paquete, filtrada por rol (RN-05), igual que el menú. --}}
@php
    // Accesos rápidos: las opciones disponibles de Pacientes y Agenda (P3), las de uso diario.
    $accesosRapidos = collect(collect($paquetes)->firstWhere('codigo', 'P3')['opciones'] ?? [])
        ->filter(fn ($opcion) => $opcion['ruta']);
    $rolVisible = Auth::user()->rol === 'administrador' ? 'Administrador' : 'Odontólogo';
@endphp

<x-app-layout>
    <div class="py-8">
        <div class="mx-auto min-w-0 max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            @if (session('exito'))
                <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm font-medium text-green-800" role="status">
                    {{ session('exito') }}
                </div>
            @endif

            {{-- Bienvenida --}}
            <section class="relative overflow-hidden rounded-3xl bg-brand-900 px-6 py-10 shadow-card sm:px-10 sm:py-12" aria-labelledby="bienvenida">
                <svg class="pointer-events-none absolute -right-16 -top-20 h-80 w-80 text-brand-500/25" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true">
                    <circle cx="100" cy="100" r="95" /><circle cx="100" cy="100" r="72" /><circle cx="100" cy="100" r="49" /><circle cx="100" cy="100" r="26" />
                </svg>

                <div class="relative max-w-2xl">
                    <span class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-brand-100 ring-1 ring-white/15">{{ $rolVisible }}</span>
                    <h1 id="bienvenida" class="mt-4 font-display text-3xl font-extrabold leading-tight text-white sm:text-4xl">
                        Hola, {{ Auth::user()->nombres }} {{ Auth::user()->apellidos }}
                    </h1>
                    <p class="mt-3 text-base text-brand-100">Elija una opción de los paquetes del sistema.</p>

                    @if ($accesosRapidos->isNotEmpty())
                        <div class="mt-7 flex flex-wrap gap-3">
                            @foreach ($accesosRapidos as $opcion)
                                <a href="{{ route($opcion['ruta']) }}"
                                   class="group inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-brand-900 shadow-sm transition hover:bg-brand-50 focus-visible:outline-white">
                                    {{ $opcion['nombre'] }}
                                    <x-icon name="arrow" class="h-4 w-4 text-brand-700 transition-transform duration-150 group-hover:translate-x-0.5" />
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Una tarjeta por paquete --}}
            <section aria-label="Paquetes del sistema">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($paquetes as $paquete)
                        <article class="dr-card flex min-w-0 flex-col overflow-hidden transition duration-200 hover:-translate-y-0.5 hover:border-brand-300">
                            <div class="p-6 pb-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-700">
                                        <x-icon :name="$paquete['codigo']" class="h-6 w-6" />
                                    </span>
                                    <span class="rounded-full bg-brand-50 px-2.5 py-0.5 text-xs font-bold tracking-wide text-brand-800">
                                        {{ $paquete['codigo'] }}
                                    </span>
                                </div>
                                <h2 class="mt-4 text-lg font-bold leading-snug text-ink">{{ $paquete['nombre'] }}</h2>
                                <p class="mt-1.5 text-sm leading-relaxed text-ink-soft">{{ $paquete['descripcion'] }}</p>
                            </div>

                            <ul class="mt-auto space-y-0.5 border-t border-line p-2">
                                @foreach ($paquete['opciones'] as $opcion)
                                    <li>
                                        @if ($opcion['ruta'])
                                            <a href="{{ route($opcion['ruta']) }}"
                                               class="group flex items-center justify-between rounded-lg px-4 py-2.5 text-sm font-semibold text-ink transition-colors hover:bg-brand-50 hover:text-brand-800">
                                                <span>{{ $opcion['nombre'] }}</span>
                                                <x-icon name="arrow" class="h-4 w-4 text-brand-700 opacity-60 transition group-hover:translate-x-0.5 group-hover:opacity-100" />
                                            </a>
                                        @else
                                            {{-- Opción inhabilitada: en gris y sin enlace --}}
                                            <div class="flex cursor-not-allowed items-center justify-between rounded-lg px-4 py-2.5 text-sm text-ink-soft" aria-disabled="true">
                                                <span>{{ $opcion['nombre'] }}</span>
                                                <span class="rounded-full bg-surface px-2 py-0.5 text-xs font-semibold uppercase tracking-wide">Próximo ciclo</span>
                                            </div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
