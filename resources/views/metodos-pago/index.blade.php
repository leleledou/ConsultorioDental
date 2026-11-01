{{-- CU23 Gestionar métodos de pago (solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'nombre' => $volvioFormulario ? old('nombre', '') : '',
        'descripcion' => $volvioFormulario ? old('descripcion', '') : '',
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Catálogo de métodos de pago" subtitulo="Formas de pago disponibles al registrar un abono." />
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos del método de pago. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombre: '', descripcion: '' };
                this.mostrarFormulario();
            },
            editar(metodoPago) {
                this.form = { ...metodoPago, descripcion: metodoPago.descripcion ?? '' };
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

        {{-- Errores de las acciones por fila y del buscador --}}
        @if ($errors->any() && ! $volvioFormulario)
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <section class="dr-card overflow-hidden" aria-label="Listado de métodos de pago">
            {{-- Buscador y botón de registro --}}
            <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('metodos-pago.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar método de pago</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar" placeholder="Buscar por nombre" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('metodos-pago.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <x-primary-button type="button" @click="nuevo()" class="w-full lg:w-auto">
                    <x-icon name="plus" class="h-5 w-5" /> Nuevo método de pago
                </x-primary-button>
            </div>

            <div class="bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                @if ($buscar)
                    <span class="font-semibold text-ink">{{ $metodosPago->total() }}</span> resultado(s) para «{{ $buscar }}»
                @else
                    <span class="font-semibold text-ink">{{ $metodosPago->total() }}</span> método(s) de pago registrado(s)
                @endif
            </div>

            @if ($metodosPago->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P5" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">
                        {{ $buscar ? 'No se encontraron métodos de pago con ese criterio de búsqueda.' : 'No hay métodos de pago registrados.' }}
                    </p>
                </div>
            @else
                {{-- Tabla (desde 1024 px) --}}
                <div class="hidden lg:block">
                    <table class="dr-table">
                        <caption class="sr-only">Catálogo de métodos de pago</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($metodosPago as $m)
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">
                                        <a href="{{ route('metodos-pago.mostrar', $m->id) }}" class="hover:text-brand-800 hover:underline">{{ $m->nombre }}</a>
                                    </td>
                                    <td class="max-w-md whitespace-pre-line break-words text-ink-soft">{{ $m->descripcion ?? '—' }}</td>
                                    <td><x-insignia-activo :activo="$m->estado === 'activo'" /></td>
                                    <td><x-catalogos.acciones :registro="$m" ruta="metodos-pago" etiqueta="el método de pago" :campos="['id', 'nombre', 'descripcion']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil y tablet) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 lg:hidden">
                    @foreach ($metodosPago as $m)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('metodos-pago.mostrar', $m->id) }}" class="min-w-0 break-words font-display text-base font-bold text-ink hover:text-brand-800">{{ $m->nombre }}</a>
                                <x-insignia-activo :activo="$m->estado === 'activo'" />
                            </div>
                            <p class="whitespace-pre-line break-words text-sm text-ink-soft">{{ $m->descripcion ?? '—' }}</p>
                            <x-catalogos.acciones :registro="$m" ruta="metodos-pago" etiqueta="el método de pago" :campos="['id', 'nombre', 'descripcion']" />
                        </li>
                    @endforeach
                </ul>

                @if ($metodosPago->hasPages())
                    <div class="border-t border-line p-4 sm:px-5">
                        {{ $metodosPago->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- Formulario de registro / modificación --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="titulo-formulario" class="text-lg font-bold text-ink" x-text="form.id ? 'Modificar método de pago' : 'Registrar método de pago'">Registrar método de pago</h3>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('metodos-pago.modificar')) }} : {{ Js::from(route('metodos-pago.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="max-w-2xl space-y-5">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <x-campo name="nombre" label="Nombre" :requerido="true" :errores="$erroresDe('nombre')">
                        <x-text-input id="nombre" name="nombre" type="text" x-model="form.nombre" maxlength="30" required :error="$volvioFormulario && $errors->has('nombre')" />
                    </x-campo>

                    <x-campo name="descripcion" label="Descripción (opcional)" :errores="$erroresDe('descripcion')">
                        <textarea id="descripcion" name="descripcion" rows="3" x-model="form.descripcion" class="{{ $claseCampo('descripcion') }}"></textarea>
                    </x-campo>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Registrar'">Guardar</x-primary-button>
                    </div>
                </div>
            </form>
        </section>
    </x-pagina>
</x-app-layout>
