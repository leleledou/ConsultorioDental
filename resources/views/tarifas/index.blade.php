{{-- CU16 Gestionar tarifas (solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'tratamiento_id' => $volvioFormulario ? old('tratamiento_id', '') : '',
        'tratamiento' => $volvioFormulario ? old('tratamiento', '') : '',
        'precio' => $volvioFormulario ? old('precio', '') : '',
        'fecha_inicio_vigencia' => $volvioFormulario ? old('fecha_inicio_vigencia', '') : '',
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Tarifas" subtitulo="Precio vigente de cada tratamiento. Un precio nuevo cierra el anterior; las tarifas no se modifican ni se eliminan." />
    </x-slot>

    {{-- nuevoPrecio(): abre el formulario con el tratamiento de la fila. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevoPrecio(tratamiento) {
                this.form = { tratamiento_id: tratamiento.id, tratamiento: tratamiento.nombre, precio: '', fecha_inicio_vigencia: '' };
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

        {{-- Errores del buscador --}}
        @if ($errors->any() && ! $volvioFormulario)
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <section class="dr-card overflow-hidden" aria-label="Tarifas vigentes">
            {{-- Buscador --}}
            <div class="border-b border-line p-4 sm:p-5">
                <form method="GET" action="{{ route('tarifas.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar tratamiento</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar" placeholder="Buscar por tratamiento" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('tarifas.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                @if ($buscar)
                    <span class="font-semibold text-ink">{{ $tratamientos->total() }}</span> resultado(s) para «{{ $buscar }}»
                @else
                    <span class="font-semibold text-ink">{{ $tratamientos->total() }}</span> tratamiento(s)
                @endif
            </div>

            @if ($tratamientos->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P5" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">
                        {{ $buscar ? 'No se encontraron tratamientos con ese criterio de búsqueda.' : 'No hay tratamientos registrados.' }}
                    </p>
                </div>
            @else
                {{-- Tabla (desde 768 px) --}}
                <div class="hidden md:block">
                    <table class="dr-table">
                        <caption class="sr-only">Precio vigente de cada tratamiento</caption>
                        <thead>
                            <tr>
                                <th scope="col">Tratamiento</th>
                                <th scope="col" class="text-right">Precio vigente</th>
                                <th scope="col">Vigente desde</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tratamientos as $t)
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">{{ $t->nombre }}</td>
                                    @if ($t->tarifaVigente)
                                        <td class="text-right font-semibold text-ink"><x-moneda :monto="$t->tarifaVigente->precio" /></td>
                                        <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $t->tarifaVigente->fecha_inicio_vigencia->format('d/m/Y') }}</td>
                                    @else
                                        <td class="text-right"><x-insignia tono="aviso">Sin tarifa</x-insignia></td>
                                        <td class="text-ink-mute">—</td>
                                    @endif
                                    <td>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <x-secondary-button class="btn-sm" @click="nuevoPrecio({{ Js::from($t->only(['id', 'nombre'])) }})">
                                                <x-icon name="plus" class="h-4 w-4" /> Nuevo precio
                                            </x-secondary-button>
                                            <a href="{{ route('tarifas.mostrar', $t->id) }}" class="btn btn-secondary btn-sm"><x-icon name="clock" class="h-4 w-4" /> Historial</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:hidden">
                    @foreach ($tratamientos as $t)
                        <li class="dr-item">
                            <p class="break-words font-display text-base font-bold text-ink">{{ $t->nombre }}</p>
                            @if ($t->tarifaVigente)
                                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                    <div class="min-w-0"><dt class="text-xs text-ink-soft">Precio vigente</dt><dd class="font-display text-lg font-bold text-ink"><x-moneda :monto="$t->tarifaVigente->precio" /></dd></div>
                                    <div class="min-w-0"><dt class="text-xs text-ink-soft">Vigente desde</dt><dd class="font-medium tabular-nums text-ink">{{ $t->tarifaVigente->fecha_inicio_vigencia->format('d/m/Y') }}</dd></div>
                                </dl>
                            @else
                                <div><x-insignia tono="aviso">Sin tarifa</x-insignia></div>
                            @endif
                            <div class="flex flex-col gap-2 min-[420px]:flex-row">
                                <x-secondary-button class="btn-sm flex-1" @click="nuevoPrecio({{ Js::from($t->only(['id', 'nombre'])) }})">
                                    <x-icon name="plus" class="h-4 w-4" /> Nuevo precio
                                </x-secondary-button>
                                <a href="{{ route('tarifas.mostrar', $t->id) }}" class="btn btn-secondary btn-sm flex-1"><x-icon name="clock" class="h-4 w-4" /> Historial</a>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($tratamientos->hasPages())
                    <div class="border-t border-line p-4 sm:px-5">
                        {{ $tratamientos->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- Formulario "Nuevo precio" --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <h3 id="titulo-formulario" class="break-words text-lg font-bold text-ink" x-text="'Nuevo precio: ' + form.tratamiento"></h3>
                    <p class="mt-0.5 text-sm text-ink-soft">La tarifa anterior se cierra el día previo al inicio de la nueva.</p>
                </div>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" action="{{ route('tarifas.guardar') }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                <input type="hidden" name="tratamiento_id" :value="form.tratamiento_id">
                {{-- Solo para el título, si el formulario vuelve con errores --}}
                <input type="hidden" name="tratamiento" :value="form.tratamiento">

                <div class="max-w-3xl space-y-5">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="precio" label="Precio" :requerido="true" :errores="$erroresDe('precio')">
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink-soft" aria-hidden="true">Bs</span>
                                <x-text-input id="precio" name="precio" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" class="pl-10 tabular-nums" x-model="form.precio" required :error="$volvioFormulario && $errors->has('precio')" />
                            </div>
                        </x-campo>

                        <x-campo name="fecha_inicio_vigencia" label="Inicio de vigencia" :requerido="true" :errores="$erroresDe('fecha_inicio_vigencia')">
                            <x-text-input id="fecha_inicio_vigencia" name="fecha_inicio_vigencia" type="date" x-model="form.fecha_inicio_vigencia" required :error="$volvioFormulario && $errors->has('fecha_inicio_vigencia')" />
                        </x-campo>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        <x-primary-button>Guardar precio</x-primary-button>
                    </div>
                </div>
            </form>
        </section>
    </x-pagina>
</x-app-layout>
