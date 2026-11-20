{{-- CU14 Gestionar especialidades (catálogo, solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no del botón Eliminar.
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
        <x-cabecera-pagina titulo="Gestionar especialidades" subtitulo="Catálogo de especialidades odontológicas del consultorio." />
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos de la especialidad. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombre: '', descripcion: '' };
                this.mostrarFormulario();
            },
            editar(especialidad) {
                this.form = { ...especialidad, descripcion: especialidad.descripcion ?? '' };
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

        @if ($errors->any() && ! $volvioFormulario)
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <section class="dr-card overflow-hidden" aria-label="Listado de especialidades">
            <div class="flex flex-col gap-3 border-b border-line p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                <p class="text-sm text-ink-soft"><span class="font-semibold text-ink">{{ $especialidades->count() }}</span> especialidad(es) registrada(s)</p>
                <x-primary-button type="button" @click="nuevo()" class="w-full sm:w-auto">
                    <x-icon name="plus" class="h-5 w-5" /> Nueva especialidad
                </x-primary-button>
            </div>

            @if ($especialidades->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P2" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">No hay especialidades registradas.</p>
                </div>
            @else
                {{-- Tabla (desde 768 px) --}}
                <div class="hidden md:block">
                    <table class="dr-table">
                        <caption class="sr-only">Especialidades registradas</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Descripción</th>
                                <th scope="col" class="text-right">Usuarios</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($especialidades as $especialidad)
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">
                                        <a href="{{ route('especialidades.mostrar', $especialidad->id) }}" class="hover:text-brand-800 hover:underline">{{ $especialidad->nombre }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $especialidad->descripcion ?? '—' }}</td>
                                    <td class="text-right tabular-nums text-ink">{{ $especialidad->usuarios_count }}</td>
                                    <td><x-especialidades.acciones :e="$especialidad" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:hidden">
                    @foreach ($especialidades as $especialidad)
                        <li class="dr-item">
                            <a href="{{ route('especialidades.mostrar', $especialidad->id) }}" class="break-words font-display text-base font-bold text-ink hover:text-brand-800">{{ $especialidad->nombre }}</a>
                            <p class="break-words text-sm text-ink-soft">{{ $especialidad->descripcion ?? '—' }}</p>
                            <p class="text-sm text-ink-soft">Usuarios: <span class="font-semibold tabular-nums text-ink">{{ $especialidad->usuarios_count }}</span></p>
                            <x-especialidades.acciones :e="$especialidad" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Formulario de registro / modificación --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="titulo-formulario" class="text-lg font-bold text-ink" x-text="form.id ? 'Modificar especialidad' : 'Registrar especialidad'">Registrar especialidad</h3>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('especialidades.modificar')) }} : {{ Js::from(route('especialidades.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="max-w-2xl space-y-5">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <x-campo name="nombre" label="Nombre" :requerido="true" :errores="$erroresDe('nombre')">
                        <x-text-input id="nombre" name="nombre" type="text" x-model="form.nombre" maxlength="40" required :error="$volvioFormulario && $errors->has('nombre')" />
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
