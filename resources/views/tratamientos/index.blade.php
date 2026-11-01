{{-- CU15 Gestionar catálogo de tratamientos (RF-25, solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'nombre' => $volvioFormulario ? old('nombre', '') : '',
        'descripcion' => $volvioFormulario ? old('descripcion', '') : '',
        'especialidad_id' => $volvioFormulario ? old('especialidad_id', '') : '',
        'duracion_estimada_minutos' => $volvioFormulario ? old('duracion_estimada_minutos', '') : '',
        'numero_sesiones_previstas' => $volvioFormulario ? old('numero_sesiones_previstas', 1) : 1,
        'requiere_sesiones' => $volvioFormulario ? (bool) old('requiere_sesiones') : false,
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Catálogo de tratamientos" subtitulo="Tratamientos que ofrece el consultorio, con su duración, sesiones, tarifas y estudios requeridos." />
    </x-slot>

    {{-- nuevo(): formulario vacío para registrar. editar(): formulario con los datos del tratamiento. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, nombre: '', descripcion: '', especialidad_id: '', duracion_estimada_minutos: '',
                              numero_sesiones_previstas: 1, requiere_sesiones: false };
                this.mostrarFormulario();
            },
            editar(tratamiento) {
                this.form = { ...tratamiento, descripcion: tratamiento.descripcion ?? '' };
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

        <section class="dr-card overflow-hidden" aria-label="Listado de tratamientos">
            {{-- Buscador y botón de registro --}}
            <div class="flex flex-col gap-4 border-b border-line p-4 sm:p-5 lg:flex-row lg:items-center lg:justify-between">
                <form method="GET" action="{{ route('tratamientos.listar') }}" role="search" class="flex w-full flex-col gap-2 sm:flex-row lg:max-w-xl">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar tratamiento</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar" placeholder="Buscar por nombre o especialidad" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('tratamientos.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <x-primary-button type="button" @click="nuevo()" class="w-full lg:w-auto">
                    <x-icon name="plus" class="h-5 w-5" /> Nuevo tratamiento
                </x-primary-button>
            </div>

            <div class="bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                @if ($buscar)
                    <span class="font-semibold text-ink">{{ $tratamientos->total() }}</span> resultado(s) para «{{ $buscar }}»
                @else
                    <span class="font-semibold text-ink">{{ $tratamientos->total() }}</span> tratamiento(s) registrado(s)
                @endif
            </div>

            @if ($tratamientos->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P2" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">
                        {{ $buscar ? 'No se encontraron tratamientos con ese criterio de búsqueda.' : 'No hay tratamientos registrados.' }}
                    </p>
                </div>
            @else
                {{-- Tabla (desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Catálogo de tratamientos</caption>
                        <thead>
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Especialidad</th>
                                <th scope="col">Duración estimada</th>
                                <th scope="col">Sesiones previstas</th>
                                <th scope="col">Estado</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tratamientos as $t)
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">
                                        <a href="{{ route('tratamientos.mostrar', $t->id) }}" class="hover:text-brand-800 hover:underline">{{ $t->nombre }}</a>
                                    </td>
                                    <td class="text-ink-soft">{{ $t->especialidad->nombre }}</td>
                                    <td class="whitespace-nowrap tabular-nums text-ink-soft">{{ $t->duracion_estimada_minutos }} min</td>
                                    <td class="text-ink-soft">
                                        <span class="tabular-nums">{{ $t->numero_sesiones_previstas }}</span>
                                        @if ($t->requiere_sesiones)
                                            <span class="text-xs">(por sesiones)</span>
                                        @endif
                                    </td>
                                    <td><x-insignia :tono="$t->estaDisponible() ? 'exito' : 'neutro'">{{ $t->estaDisponible() ? 'Disponible' : 'No disponible' }}</x-insignia></td>
                                    <td><x-tratamientos.acciones :t="$t" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($tratamientos as $t)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('tratamientos.mostrar', $t->id) }}" class="min-w-0 break-words font-display text-base font-bold text-ink hover:text-brand-800">{{ $t->nombre }}</a>
                                <x-insignia :tono="$t->estaDisponible() ? 'exito' : 'neutro'">{{ $t->estaDisponible() ? 'Disponible' : 'No disponible' }}</x-insignia>
                            </div>
                            <dl class="grid grid-cols-2 gap-x-3 gap-y-2 text-sm">
                                <div class="col-span-2 min-w-0"><dt class="text-xs text-ink-soft">Especialidad</dt><dd class="break-words font-medium text-ink">{{ $t->especialidad->nombre }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Duración estimada</dt><dd class="font-medium tabular-nums text-ink">{{ $t->duracion_estimada_minutos }} min</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Sesiones previstas</dt><dd class="font-medium text-ink"><span class="tabular-nums">{{ $t->numero_sesiones_previstas }}</span>@if ($t->requiere_sesiones) <span class="text-xs font-normal text-ink-soft">(por sesiones)</span>@endif</dd></div>
                            </dl>
                            <x-tratamientos.acciones :t="$t" />
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

        {{-- Formulario de registro / modificación --}}
        <section x-ref="formulario" x-show="abierto" x-cloak
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                 class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-formulario">
            <header class="flex items-start justify-between gap-4 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <div>
                    <h3 id="titulo-formulario" class="text-lg font-bold text-ink" x-text="form.id ? 'Modificar tratamiento' : 'Registrar tratamiento'">Registrar tratamiento</h3>
                    <p class="mt-0.5 text-sm text-ink-soft">La duración estimada se propone al programar una cita con este tratamiento (RN-08).</p>
                </div>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('tratamientos.modificar')) }} : {{ Js::from(route('tratamientos.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="space-y-8">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Datos del tratamiento</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="nombre" label="Nombre" :requerido="true" :errores="$erroresDe('nombre')">
                                <x-text-input id="nombre" name="nombre" type="text" x-model="form.nombre" maxlength="60" required :error="$volvioFormulario && $errors->has('nombre')" />
                            </x-campo>

                            <x-campo name="especialidad_id" label="Especialidad" :requerido="true" :errores="$erroresDe('especialidad_id')">
                                <select id="especialidad_id" name="especialidad_id" class="{{ $claseCampo('especialidad_id') }}" x-model="form.especialidad_id" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($especialidades as $e)
                                        <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                                    @endforeach
                                </select>
                            </x-campo>

                            <x-campo name="descripcion" label="Descripción (opcional)" class="sm:col-span-2" :errores="$erroresDe('descripcion')">
                                <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo('descripcion') }}" x-model="form.descripcion" maxlength="1000"></textarea>
                            </x-campo>
                        </div>
                    </fieldset>

                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Duración y sesiones</legend>
                        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="duracion_estimada_minutos" label="Duración estimada (minutos)" :requerido="true" :errores="$erroresDe('duracion_estimada_minutos')">
                                <x-text-input id="duracion_estimada_minutos" name="duracion_estimada_minutos" type="number" min="1" max="600" step="1" inputmode="numeric" x-model="form.duracion_estimada_minutos" required :error="$volvioFormulario && $errors->has('duracion_estimada_minutos')" />
                            </x-campo>

                            <x-campo name="numero_sesiones_previstas" label="Sesiones previstas" :requerido="true" :errores="$erroresDe('numero_sesiones_previstas')">
                                <x-text-input id="numero_sesiones_previstas" name="numero_sesiones_previstas" type="number" min="1" max="100" step="1" inputmode="numeric" x-model="form.numero_sesiones_previstas" required :error="$volvioFormulario && $errors->has('numero_sesiones_previstas')" />
                            </x-campo>

                            <div class="sm:col-span-2">
                                {{-- El oculto envía 0 cuando la casilla no está marcada --}}
                                <input type="hidden" name="requiere_sesiones" value="0">
                                <label for="requiere_sesiones" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink transition-colors hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                    <input id="requiere_sesiones" name="requiere_sesiones" type="checkbox" value="1" x-model="form.requiere_sesiones"
                                           class="h-4 w-4 shrink-0 rounded border-gray-300 text-brand-700 shadow-sm focus:ring-brand-600">
                                    <span>Requiere varias sesiones</span>
                                </label>
                                @if ($volvioFormulario && $errors->has('requiere_sesiones'))
                                    <x-input-error :messages="$errors->get('requiere_sesiones')" class="mt-1.5" />
                                @endif
                            </div>
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
    </x-pagina>
</x-app-layout>
