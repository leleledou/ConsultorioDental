{{-- CU22 Gestionar estados de pieza (solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'codigo' => $volvioFormulario ? old('codigo', '') : '',
        'nombre' => $volvioFormulario ? old('nombre', '') : '',
        'descripcion' => $volvioFormulario ? old('descripcion', '') : '',
        'color_referencia' => $volvioFormulario ? old('color_referencia', '') : '',
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
    $camposEdicion = ['id', 'codigo', 'nombre', 'descripcion', 'color_referencia'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Catálogo de estados de pieza" subtitulo="Estados con su código y color de referencia para el registro clínico de las piezas." />
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos del estado de pieza. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, codigo: '', nombre: '', descripcion: '', color_referencia: '' };
                this.mostrarFormulario();
            },
            editar(estadoPieza) {
                this.form = { ...estadoPieza, descripcion: estadoPieza.descripcion ?? '', color_referencia: estadoPieza.color_referencia ?? '' };
                this.mostrarFormulario();
            },
            colorValido() { return /^#[0-9A-Fa-f]{6}$/.test(this.form.color_referencia); },
            mostrarFormulario() {
                this.abierto = true;
                this.$nextTick(() => this.$refs.formulario.scrollIntoView({ behavior: 'smooth' }));
            },
         }">
        <x-catalogos.pestanas activa="estados" />

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

        <section class="dr-card overflow-hidden" aria-label="Listado de estados de pieza">
            {{-- Buscador y botón de registro --}}
            <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('estados-pieza.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar estado de pieza</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar" placeholder="Buscar por nombre" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('estados-pieza.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <x-primary-button type="button" @click="nuevo()" class="w-full lg:w-auto">
                    <x-icon name="plus" class="h-5 w-5" /> Nuevo estado de pieza
                </x-primary-button>
            </div>

            <div class="bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                @if ($buscar)
                    <span class="font-semibold text-ink">{{ $estadosPieza->total() }}</span> resultado(s) para «{{ $buscar }}»
                @else
                    <span class="font-semibold text-ink">{{ $estadosPieza->total() }}</span> estado(s) de pieza registrado(s)
                @endif
            </div>

            @if ($estadosPieza->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">
                        {{ $buscar ? 'No se encontraron estados de pieza con ese criterio de búsqueda.' : 'No hay estados de pieza registrados.' }}
                    </p>
                </div>
            @else
                {{-- Tabla (desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Catálogo de estados de pieza</caption>
                        <thead>
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripción</th>
                                <th scope="col">Color de referencia</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($estadosPieza as $e)
                                <tr class="align-top">
                                    <td class="whitespace-nowrap font-semibold">
                                        <a href="{{ route('estados-pieza.mostrar', $e->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $e->codigo }}</a>
                                    </td>
                                    <td class="font-semibold text-ink">{{ $e->nombre }}</td>
                                    <td class="max-w-xs whitespace-pre-line break-words text-ink-soft">{{ $e->descripcion ?? '—' }}</td>
                                    <td class="whitespace-nowrap"><x-estados-pieza.color :color="$e->color_referencia" /></td>
                                    <td><x-insignia-activo :activo="$e->estado === 'activo'" /></td>
                                    <td><x-catalogos.acciones :registro="$e" ruta="estados-pieza" etiqueta="el estado de pieza" :campos="$camposEdicion" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil y tablet) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($estadosPieza as $e)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ route('estados-pieza.mostrar', $e->id) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline">{{ $e->codigo }}</a>
                                    <p class="break-words font-display text-base font-bold text-ink">{{ $e->nombre }}</p>
                                </div>
                                <x-insignia-activo :activo="$e->estado === 'activo'" />
                            </div>
                            <p class="whitespace-pre-line break-words text-sm text-ink-soft">{{ $e->descripcion ?? '—' }}</p>
                            <p class="text-sm"><span class="text-xs text-ink-soft">Color de referencia</span><br><x-estados-pieza.color :color="$e->color_referencia" /></p>
                            <x-catalogos.acciones :registro="$e" ruta="estados-pieza" etiqueta="el estado de pieza" :campos="$camposEdicion" />
                        </li>
                    @endforeach
                </ul>

                @if ($estadosPieza->hasPages())
                    <div class="border-t border-line p-4 sm:px-5">
                        {{ $estadosPieza->links() }}
                    </div>
                @endif
            @endif
        </section>

        {{-- Formulario de registro / modificación --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="titulo-formulario" class="text-lg font-bold text-ink" x-text="form.id ? 'Modificar estado de pieza' : 'Registrar estado de pieza'">Registrar estado de pieza</h3>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('estados-pieza.modificar')) }} : {{ Js::from(route('estados-pieza.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="max-w-3xl space-y-5">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                        <x-campo name="codigo" label="Código" :requerido="true" :errores="$erroresDe('codigo')">
                            <x-text-input id="codigo" name="codigo" type="text" x-model="form.codigo" maxlength="10" required :error="$volvioFormulario && $errors->has('codigo')" />
                        </x-campo>

                        <x-campo name="nombre" label="Nombre" class="sm:col-span-2" :requerido="true" :errores="$erroresDe('nombre')">
                            <x-text-input id="nombre" name="nombre" type="text" x-model="form.nombre" maxlength="40" required :error="$volvioFormulario && $errors->has('nombre')" />
                        </x-campo>
                    </div>

                    <x-campo name="color_referencia" label="Color de referencia (opcional)" :errores="$erroresDe('color_referencia')">
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 rounded-full border border-line bg-white" aria-hidden="true"
                                  :style="colorValido() ? 'background-color:' + form.color_referencia : ''"></span>
                            <x-text-input id="color_referencia" name="color_referencia" type="text" class="pl-11 uppercase placeholder:normal-case" x-model="form.color_referencia" maxlength="7" placeholder="#RRGGBB" :error="$volvioFormulario && $errors->has('color_referencia')" />
                        </div>
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
