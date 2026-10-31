{{-- CU08 Gestionar pacientes y CU09 Buscar paciente (los dos roles). --}}
@php
    // El formulario de registro/modificación envía un campo oculto "formulario" = "datos".
    // Así, si vuelve con errores, se sabe que los datos anteriores (old) son de este
    // formulario y no de las acciones por fila (inhabilitar / habilitar).
    $volvioFormulario = old('formulario') === 'datos';

    // Datos iniciales del formulario: los que se enviaron antes (si hubo errores) o vacíos.
    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'ci' => $volvioFormulario ? old('ci', '') : '',
        'nombres' => $volvioFormulario ? old('nombres', '') : '',
        'apellidos' => $volvioFormulario ? old('apellidos', '') : '',
        'fecha_nacimiento' => $volvioFormulario ? old('fecha_nacimiento', '') : '',
        'sexo' => $volvioFormulario ? old('sexo', '') : '',
        'telefono' => $volvioFormulario ? old('telefono', '') : '',
        'correo' => $volvioFormulario ? old('correo', '') : '',
        'direccion' => $volvioFormulario ? old('direccion', '') : '',
        'observaciones' => $volvioFormulario ? old('observaciones', '') : '',
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2>Gestionar pacientes</h2>
        <p class="mt-1 text-sm text-ink-soft">Registre, busque y actualice los pacientes del consultorio.</p>
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar (CU08 paso 3).
         editar(): formulario con los datos del paciente elegido para modificar. --}}
    <div class="py-8"
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, ci: '', nombres: '', apellidos: '', fecha_nacimiento: '', sexo: '',
                              telefono: '', correo: '', direccion: '', observaciones: '' };
                this.mostrarFormulario();
            },
            editar(paciente) {
                this.form = { ...paciente };
                this.mostrarFormulario();
            },
            mostrarFormulario() {
                this.abierto = true;
                this.$nextTick(() => {
                    this.$refs.formulario.scrollIntoView({ behavior: 'smooth' });
                    this.$refs.formulario.querySelector('input[name=ci]').focus({ preventScroll: true });
                });
            },
         }"
         x-init="if (abierto) $nextTick(() => $refs.formulario.scrollIntoView({ behavior: 'smooth' }))">
        <div class="mx-auto min-w-0 max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            <section class="dr-card overflow-hidden" aria-label="Listado de pacientes">
                {{-- CU09 paso 1: buscador y botón de registro --}}
                <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                    <form method="GET" action="{{ route('pacientes.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                        <div class="relative flex-1">
                            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                            <label for="buscar" class="sr-only">Buscar paciente</label>
                            <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar"
                                          placeholder="Buscar por CI, nombres o apellidos" />
                        </div>
                        <div class="flex gap-2">
                            <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                            @if ($buscar)
                                <a href="{{ route('pacientes.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                            @endif
                        </div>
                    </form>

                    <x-primary-button type="button" @click="nuevo()" class="w-full lg:w-auto">
                        <x-icon name="plus" class="h-5 w-5" /> Nuevo paciente
                    </x-primary-button>
                </div>

                <div class="flex items-center justify-between gap-3 bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                    <p>
                        @if ($buscar)
                            <span class="font-semibold text-ink">{{ $pacientes->total() }}</span> resultado(s) para «{{ $buscar }}»
                        @else
                            <span class="font-semibold text-ink">{{ $pacientes->total() }}</span> paciente(s) registrado(s)
                        @endif
                    </p>
                </div>

                @if ($pacientes->isEmpty())
                    {{-- Estado vacío --}}
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                            <x-icon name="users" class="h-7 w-7" />
                        </span>
                        <p class="mt-4 text-base font-semibold text-ink">
                            {{ $buscar ? 'No se encontraron pacientes con ese criterio de búsqueda.' : 'No hay pacientes registrados.' }}
                        </p>
                        <p class="mt-1 text-sm text-ink-soft">
                            {{ $buscar ? 'Revise el CI, los nombres o los apellidos e intente de nuevo.' : 'Registre al primer paciente para comenzar.' }}
                        </p>
                    </div>
                @else
                    {{-- Tabla (escritorio ancho, desde 1280 px) --}}
                    <div class="hidden xl:block">
                        <table class="min-w-full text-sm">
                            <caption class="sr-only">Pacientes registrados</caption>
                            <thead class="border-b border-line bg-surface/60 text-left text-xs font-semibold uppercase tracking-wider text-ink-soft">
                                <tr>
                                    <th scope="col" class="px-5 py-3">CI</th>
                                    <th scope="col" class="px-3 py-3">Paciente</th>
                                    <th scope="col" class="px-3 py-3">Edad</th>
                                    <th scope="col" class="px-3 py-3">Teléfono</th>
                                    <th scope="col" class="px-3 py-3">Estado</th>
                                    <th scope="col" class="px-5 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-line">
                                @foreach ($pacientes as $p)
                                    <tr class="transition-colors hover:bg-brand-50/50">
                                        <td class="whitespace-nowrap px-5 py-3.5 font-medium tabular-nums text-ink">{{ $p->ci }}</td>
                                        <td class="px-3 py-3.5">
                                            <a href="{{ route('pacientes.mostrar', $p->id) }}" class="group flex items-center gap-3">
                                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-800 ring-1 ring-brand-100" aria-hidden="true">
                                                    {{ mb_strtoupper(mb_substr($p->nombres, 0, 1).mb_substr($p->apellidos, 0, 1)) }}
                                                </span>
                                                <span class="min-w-0 font-semibold text-ink group-hover:text-brand-800 group-hover:underline">
                                                    {{ $p->apellidos }}, {{ $p->nombres }}
                                                </span>
                                            </a>
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-ink-soft">{{ $p->edad() }} años</td>
                                        <td class="whitespace-nowrap px-3 py-3.5 text-ink-soft">{{ $p->telefono ?? '—' }}</td>
                                        <td class="px-3 py-3.5"><x-insignia-activo :activo="$p->estado === 'activo'" /></td>
                                        <td class="px-5 py-3.5"><x-pacientes.acciones :p="$p" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                    <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                        @foreach ($pacientes as $p)
                            <li class="space-y-3 rounded-xl border border-line bg-white p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ route('pacientes.mostrar', $p->id) }}" class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-bold text-brand-800 ring-1 ring-brand-100" aria-hidden="true">
                                            {{ mb_strtoupper(mb_substr($p->nombres, 0, 1).mb_substr($p->apellidos, 0, 1)) }}
                                        </span>
                                        <span class="min-w-0 font-semibold text-ink">{{ $p->apellidos }}, {{ $p->nombres }}</span>
                                    </a>
                                    <x-insignia-activo :activo="$p->estado === 'activo'" />
                                </div>

                                <dl class="grid grid-cols-3 gap-2 text-sm">
                                    <div><dt class="text-xs text-ink-soft">CI</dt><dd class="font-medium tabular-nums text-ink">{{ $p->ci }}</dd></div>
                                    <div><dt class="text-xs text-ink-soft">Edad</dt><dd class="font-medium text-ink">{{ $p->edad() }} años</dd></div>
                                    <div><dt class="text-xs text-ink-soft">Teléfono</dt><dd class="truncate font-medium text-ink">{{ $p->telefono ?? '—' }}</dd></div>
                                </dl>

                                <x-pacientes.acciones :p="$p" />
                            </li>
                        @endforeach
                    </ul>

                    @if ($pacientes->hasPages())
                        <div class="border-t border-line p-4 sm:px-5">
                            {{ $pacientes->links() }}
                        </div>
                    @endif
                @endif
            </section>

            {{-- CU08 pasos 3 a 5: formulario de registro / modificación --}}
            <section x-ref="formulario" x-show="abierto" x-cloak
                     x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                     class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
                <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <div>
                        <h3 id="titulo-formulario" class="text-lg font-bold text-ink"
                            x-text="form.id ? 'Modificar paciente' : 'Registrar paciente'">Registrar paciente</h3>
                        <p class="mt-0.5 text-sm text-ink-soft" x-show="! form.id">
                            Al registrar al paciente se abre su historia clínica con la fecha de hoy.
                        </p>
                    </div>
                    <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                        <x-icon name="close" />
                    </button>
                </header>

                <form method="POST" :action="form.id ? {{ Js::from(route('pacientes.modificar')) }} : {{ Js::from(route('pacientes.guardar')) }}" class="p-5 sm:p-6">
                    @csrf
                    <input type="hidden" name="formulario" value="datos">
                    {{-- El id viaja oculto solo al modificar --}}
                    <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                    <div class="space-y-8">

                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos personales</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="ci" label="CI" :requerido="true" :errores="$erroresDe('ci')">
                                <x-text-input id="ci" name="ci" type="text" x-model="form.ci" maxlength="15" required :error="$volvioFormulario && $errors->has('ci')" />
                            </x-campo>

                            <x-campo name="fecha_nacimiento" label="Fecha de nacimiento" :requerido="true" :errores="$erroresDe('fecha_nacimiento')">
                                <x-text-input id="fecha_nacimiento" name="fecha_nacimiento" type="date" x-model="form.fecha_nacimiento" max="{{ today()->toDateString() }}" required :error="$volvioFormulario && $errors->has('fecha_nacimiento')" />
                            </x-campo>

                            <x-campo name="nombres" label="Nombres" :requerido="true" :errores="$erroresDe('nombres')">
                                <x-text-input id="nombres" name="nombres" type="text" x-model="form.nombres" maxlength="50" required :error="$volvioFormulario && $errors->has('nombres')" />
                            </x-campo>

                            <x-campo name="apellidos" label="Apellidos" :requerido="true" :errores="$erroresDe('apellidos')">
                                <x-text-input id="apellidos" name="apellidos" type="text" x-model="form.apellidos" maxlength="50" required :error="$volvioFormulario && $errors->has('apellidos')" />
                            </x-campo>

                            <x-campo name="sexo" label="Sexo" :requerido="true" :errores="$erroresDe('sexo')">
                                <select id="sexo" name="sexo" class="{{ $claseCampo('sexo') }}" x-model="form.sexo" required>
                                    <option value="">Seleccione…</option>
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                </select>
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Contacto</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="telefono" label="Teléfono" :errores="$erroresDe('telefono')">
                                <x-text-input id="telefono" name="telefono" type="text" x-model="form.telefono" maxlength="15" :error="$volvioFormulario && $errors->has('telefono')" />
                            </x-campo>

                            <x-campo name="correo" label="Correo" :errores="$erroresDe('correo')">
                                <x-text-input id="correo" name="correo" type="email" x-model="form.correo" maxlength="100" :error="$volvioFormulario && $errors->has('correo')" />
                            </x-campo>

                            <x-campo name="direccion" label="Dirección" class="sm:col-span-2" :errores="$erroresDe('direccion')">
                                <x-text-input id="direccion" name="direccion" type="text" x-model="form.direccion" maxlength="150" :error="$volvioFormulario && $errors->has('direccion')" />
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Observaciones</legend>
                        <div class="mt-4">
                            <x-campo name="observaciones" label="Observaciones" :errores="$erroresDe('observaciones')">
                                <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo('observaciones') }}" x-model="form.observaciones" maxlength="1000"></textarea>
                            </x-campo>
                        </div>
                    </fieldset>

                    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                        <div class="flex flex-col-reverse gap-3 sm:flex-row">
                            <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                            <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Registrar'">Guardar</x-primary-button>
                        </div>
                    </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
