{{-- CU16 Ver historial de tarifas de un tratamiento, con el formulario "Nuevo precio". --}}
@php
    $vigente = $tarifas->firstWhere('estado', 'vigente');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Tarifas: '.$tratamiento->nombre" subtitulo="Historial de precios del tratamiento y registro de un precio nuevo."
                           :volver="route('tarifas.listar')" volver-texto="Tarifas" />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->any())
            <x-alerta tipo="error">Revise los datos marcados en el formulario «Nuevo precio».</x-alerta>
        @endif

        {{-- Tratamiento y precio vigente --}}
        <section class="dr-card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Precio vigente">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Tratamiento</p>
                <p class="mt-0.5 break-words font-display text-xl font-bold text-ink">{{ $tratamiento->nombre }}</p>
            </div>
            <div class="sm:text-right">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Precio vigente</p>
                @if ($vigente)
                    <p class="mt-0.5 font-display text-2xl font-bold text-ink"><x-moneda :monto="$vigente->precio" /></p>
                    <p class="text-sm text-ink-soft">desde el {{ $vigente->fecha_inicio_vigencia->format('d/m/Y') }}</p>
                @else
                    <p class="mt-1.5"><x-insignia tono="aviso">Sin tarifa vigente</x-insignia></p>
                @endif
            </div>
        </section>

        {{-- Historial --}}
        <section class="dr-card overflow-hidden" aria-labelledby="historial-precios">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="historial-precios" class="text-lg font-bold text-ink">Historial de precios <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $tarifas->count() }})</span></h3>
            </header>

            @if ($tarifas->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P5" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">Este tratamiento no tiene tarifas registradas.</p>
                </div>
            @else
                {{-- Tabla (desde 768 px) --}}
                <div class="hidden md:block">
                    <table class="dr-table">
                        <caption class="sr-only">Historial de precios de {{ $tratamiento->nombre }}</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="text-right">Precio</th>
                                <th scope="col">Inicio de vigencia</th>
                                <th scope="col">Fin de vigencia</th>
                                <th scope="col">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tarifas as $tarifa)
                                <tr>
                                    <td class="text-right font-semibold text-ink"><x-moneda :monto="$tarifa->precio" /></td>
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $tarifa->fecha_inicio_vigencia->format('d/m/Y') }}</td>
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $tarifa->fecha_fin_vigencia?->format('d/m/Y') ?? '—' }}</td>
                                    <td><x-insignia :tono="$tarifa->estado === 'vigente' ? 'exito' : 'neutro'">{{ $tarifa->estado === 'vigente' ? 'Vigente' : 'Cerrada' }}</x-insignia></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:hidden">
                    @foreach ($tarifas as $tarifa)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-display text-xl font-bold text-ink"><x-moneda :monto="$tarifa->precio" /></p>
                                <x-insignia :tono="$tarifa->estado === 'vigente' ? 'exito' : 'neutro'">{{ $tarifa->estado === 'vigente' ? 'Vigente' : 'Cerrada' }}</x-insignia>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Inicio de vigencia</dt><dd class="font-medium tabular-nums text-ink">{{ $tarifa->fecha_inicio_vigencia->format('d/m/Y') }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Fin de vigencia</dt><dd class="font-medium tabular-nums text-ink">{{ $tarifa->fecha_fin_vigencia?->format('d/m/Y') ?? '—' }}</dd></div>
                            </dl>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Formulario "Nuevo precio" de este tratamiento --}}
        <form method="POST" action="{{ route('tarifas.guardar') }}" class="dr-card overflow-hidden" aria-labelledby="titulo-nuevo-precio">
            @csrf
            <input type="hidden" name="tratamiento_id" value="{{ $tratamiento->id }}">

            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="titulo-nuevo-precio" class="text-lg font-bold text-ink">Nuevo precio</h3>
                <p class="mt-0.5 text-sm text-ink-soft">La tarifa vigente se cierra el día previo al inicio de la nueva.</p>
            </header>

            <div class="max-w-3xl p-5 sm:p-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-campo name="precio" label="Precio" :requerido="true" :errores="$errors->get('precio')">
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink-soft" aria-hidden="true">Bs</span>
                            <x-text-input id="precio" name="precio" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" class="pl-10 tabular-nums" :value="old('precio')" required :error="$errors->has('precio')" />
                        </div>
                    </x-campo>

                    <x-campo name="fecha_inicio_vigencia" label="Inicio de vigencia" :requerido="true" :errores="$errors->get('fecha_inicio_vigencia')">
                        <x-text-input id="fecha_inicio_vigencia" name="fecha_inicio_vigencia" type="date" :value="old('fecha_inicio_vigencia')" required :error="$errors->has('fecha_inicio_vigencia')" />
                    </x-campo>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                <x-primary-button class="w-full sm:w-auto">Guardar precio</x-primary-button>
            </div>
        </form>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tarifas.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a tarifas
            </a>
        </div>
    </x-pagina>
</x-app-layout>
