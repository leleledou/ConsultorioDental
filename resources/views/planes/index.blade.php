{{-- CU27 Gestionar plan de tratamiento: buscador de paciente o planes del paciente. --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores se abre solo.
    $volvioFormulario = old('formulario') === 'datos';

    // Clase de un control, con borde rojo si el campo tiene error (solo cuando se vuelve del formulario).
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');

    if ($paciente) {
        $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
        $activo = $paciente->estado === 'activo';
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        @if ($paciente)
            <x-cabecera-pagina :titulo="'Planes de tratamiento: '.$paciente->nombreCompleto()"
                               subtitulo="Planes del paciente, con su costo y su estado."
                               :volver="route('pacientes.mostrar', $paciente->id)" volver-texto="Paciente" />
        @else
            <x-cabecera-pagina titulo="Plan de tratamiento" subtitulo="Busque al paciente para ver o crear sus planes de tratamiento." />
        @endif
    </x-slot>

    <x-pagina x-data="{ abierto: {{ Js::from($volvioFormulario) }} }">
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

        @if (! $paciente)
            {{-- Buscador de paciente (CU09) --}}
            <section class="dr-card overflow-hidden" aria-label="Buscar paciente">
                <form method="GET" action="{{ route('planes.listar') }}" role="search" class="flex flex-col gap-2 p-4 sm:flex-row sm:p-5">
                    <div class="relative flex-1">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-ink-soft" />
                        <label for="buscar" class="sr-only">Buscar paciente</label>
                        <x-text-input id="buscar" name="buscar" type="search" class="pl-10" :value="$buscar"
                                      placeholder="Buscar por CI, nombres o apellidos" />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button class="flex-1 sm:flex-none">Buscar</x-primary-button>
                        @if ($buscar)
                            <a href="{{ route('planes.listar') }}" class="btn btn-secondary flex-1 sm:flex-none">Limpiar</a>
                        @endif
                    </div>
                </form>

                <div class="border-t border-line bg-surface/60 px-4 py-2.5 text-sm text-ink-soft sm:px-5">
                    @if ($buscar)
                        <span class="font-semibold text-ink">{{ $pacientes->total() }}</span> resultado(s) para «{{ $buscar }}»
                    @else
                        <span class="font-semibold text-ink">{{ $pacientes->total() }}</span> paciente(s) registrado(s)
                    @endif
                </div>

                @if ($pacientes->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                            <x-icon name="users" class="h-7 w-7" />
                        </span>
                        <p class="mt-4 text-base font-semibold text-ink">
                            {{ $buscar ? 'No se encontraron pacientes con ese criterio de búsqueda.' : 'No hay pacientes registrados.' }}
                        </p>
                    </div>
                @else
                    {{-- Tabla (desde 768 px) --}}
                    <div class="hidden md:block">
                        <table class="dr-table">
                            <caption class="sr-only">Pacientes</caption>
                            <thead>
                                <tr>
                                    <th scope="col">CI</th>
                                    <th scope="col">Nombre completo</th>
                                    <th scope="col"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pacientes as $p)
                                    <tr>
                                        <td class="whitespace-nowrap font-medium tabular-nums text-ink">{{ $p->ci }}</td>
                                        <td class="font-semibold text-ink">{{ $p->nombreCompleto() }}</td>
                                        <td class="text-right">
                                            <a href="{{ route('planes.listar', ['paciente_id' => $p->id]) }}" class="btn btn-secondary btn-sm">Ver planes</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tarjetas (móvil) --}}
                    <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:hidden">
                        @foreach ($pacientes as $p)
                            <li class="dr-item">
                                <p class="min-w-0 break-words font-semibold text-ink">{{ $p->nombreCompleto() }}</p>
                                <p class="text-sm text-ink-soft">CI <span class="font-medium tabular-nums text-ink">{{ $p->ci }}</span></p>
                                <a href="{{ route('planes.listar', ['paciente_id' => $p->id]) }}" class="btn btn-secondary btn-sm w-full">Ver planes</a>
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
        @else
            {{-- Resumen del paciente y acción principal --}}
            <section class="dr-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Paciente">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                    <div class="min-w-0">
                        <p class="font-display text-xl font-bold text-ink">{{ $paciente->nombreCompleto() }}</p>
                        <p class="mt-0.5 text-sm text-ink-soft">CI {{ $paciente->ci }} · {{ $planes->count() }} plan(es)</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <x-insignia-activo :activo="$activo" />
                    @if ($activo)
                        <x-primary-button type="button" @click="abierto = true; $nextTick(() => $refs.formulario.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
                            <x-icon name="plus" class="h-4 w-4" /> Nuevo plan
                        </x-primary-button>
                    @endif
                </div>
            </section>

            {{-- Planes del paciente --}}
            <section class="dr-card overflow-hidden" aria-label="Planes de tratamiento">
                @if ($planes->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                            <x-icon name="P4" class="h-7 w-7" />
                        </span>
                        <p class="mt-4 text-base font-semibold text-ink">El paciente no tiene planes de tratamiento.</p>
                        @if ($activo)
                            <p class="mt-1 text-sm text-ink-soft">Cree el primero con el botón «Nuevo plan».</p>
                        @endif
                    </div>
                @else
                    {{-- Tabla (desde 1280 px) --}}
                    <div class="hidden xl:block">
                        <table class="dr-table">
                            <caption class="sr-only">Planes de tratamiento del paciente</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Fecha de creación</th>
                                    <th scope="col">Odontólogo responsable</th>
                                    <th scope="col">Descripción</th>
                                    <th scope="col" class="text-right">Costo total</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($planes as $plan)
                                    <tr class="align-top">
                                        <td class="whitespace-nowrap font-medium text-ink">{{ $plan->fecha_creacion->format('d/m/Y') }}</td>
                                        <td class="text-ink">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</td>
                                        <td class="whitespace-pre-line text-ink-soft">{{ $plan->descripcion ?? '—' }}</td>
                                        <td class="text-right font-semibold text-ink"><x-moneda :monto="$plan->costo_total" /></td>
                                        <td><x-estado-clinico :estado="$plan->estado" /></td>
                                        <td class="text-right">
                                            <a href="{{ route('planes.mostrar', $plan->id) }}" class="btn btn-secondary btn-sm">Abrir</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Tarjetas (móvil, tablet y laptop angosta): 1 columna, 2 desde 768 px --}}
                    <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                        @foreach ($planes as $plan)
                            <li class="dr-item">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="font-display text-base font-bold text-ink">{{ $plan->fecha_creacion->format('d/m/Y') }}</p>
                                    <x-estado-clinico :estado="$plan->estado" />
                                </div>
                                <p class="whitespace-pre-line break-words text-sm text-ink-soft">{{ $plan->descripcion ?? '—' }}</p>
                                <dl class="grid grid-cols-2 gap-2 text-sm">
                                    <div class="min-w-0"><dt class="text-xs text-ink-soft">Odontólogo responsable</dt><dd class="break-words font-medium text-ink">{{ $plan->odontologo->nombres }} {{ $plan->odontologo->apellidos }}</dd></div>
                                    <div class="min-w-0"><dt class="text-xs text-ink-soft">Costo total</dt><dd class="font-semibold text-ink"><x-moneda :monto="$plan->costo_total" /></dd></div>
                                </dl>
                                <a href="{{ route('planes.mostrar', $plan->id) }}" class="btn btn-secondary btn-sm w-full">Abrir plan</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- Formulario "Nuevo plan" --}}
            @if ($activo)
                <section x-ref="formulario" x-show="abierto" x-cloak
                         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                         class="dr-card scroll-mt-24 overflow-hidden" aria-labelledby="titulo-nuevo-plan">
                    <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                        <h3 id="titulo-nuevo-plan" class="text-lg font-bold text-ink">Nuevo plan</h3>
                        <p class="mt-0.5 text-sm text-ink-soft">El plan se crea activo, con la fecha de hoy y costo 0; el costo se calcula al agregar tratamientos.</p>
                    </header>

                    <form method="POST" action="{{ route('planes.guardar') }}" class="space-y-5 p-5 sm:p-6">
                        @csrf
                        <input type="hidden" name="formulario" value="datos">
                        <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">

                        @if ($volvioFormulario && $errors->any())
                            <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                        @endif

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="odontologo_id" label="Odontólogo responsable" :requerido="true"
                                     :errores="$volvioFormulario ? array_merge($errors->get('odontologo_id'), $errors->get('paciente_id')) : []">
                                <select id="odontologo_id" name="odontologo_id" class="{{ $claseCampo('odontologo_id') }}" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($odontologos as $o)
                                        <option value="{{ $o->id }}" @selected(old('odontologo_id') == $o->id)>{{ $o->nombres }} {{ $o->apellidos }}</option>
                                    @endforeach
                                </select>
                            </x-campo>

                            <x-campo name="descripcion" label="Descripción (opcional)" class="sm:col-span-2"
                                     :errores="$volvioFormulario ? $errors->get('descripcion') : []">
                                <textarea id="descripcion" name="descripcion" rows="3" class="{{ $claseCampo('descripcion') }}">{{ old('descripcion') }}</textarea>
                            </x-campo>
                        </div>

                        <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                                <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                                <x-primary-button>Crear plan</x-primary-button>
                            </div>
                        </div>
                    </form>
                </section>
            @endif

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                    <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
                </a>
            </div>
        @endif
    </x-pagina>
</x-app-layout>
