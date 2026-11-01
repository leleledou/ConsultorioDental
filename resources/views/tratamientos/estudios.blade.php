{{-- CU19 Definir estudios requeridos por tratamiento (RF-31, solo administrador). --}}
@php
    // El formulario envía un campo oculto "formulario" = "datos"; si vuelve con errores,
    // los datos anteriores (old) son de este formulario y no de las acciones por fila.
    $volvioFormulario = old('formulario') === 'datos';

    $formularioInicial = [
        'id' => $volvioFormulario ? old('id') : null,
        'tipo_estudio_id' => $volvioFormulario ? old('tipo_estudio_id', '') : '',
        'obligatorio' => $volvioFormulario ? (bool) old('obligatorio') : false,
        'momento_clinico' => $volvioFormulario ? old('momento_clinico', '') : '',
        'observacion' => $volvioFormulario ? old('observacion', '') : '',
    ];

    // Errores de un campo del formulario (solo si lo que volvió es ese formulario).
    $erroresDe = fn (string $campo) => $volvioFormulario ? $errors->get($campo) : [];
    $claseCampo = fn (string $campo) => 'dr-input'.($volvioFormulario && $errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Estudios requeridos: '.$tratamiento->nombre"
                           subtitulo="Estudios que se piden antes, durante o después de este tratamiento."
                           :volver="route('tratamientos.mostrar', $tratamiento->id)" volver-texto="Tratamiento" />
    </x-slot>

    {{-- nuevo(): formulario vacío para agregar. editar(): formulario con los datos del requerido. --}}
    <x-pagina
         x-data="{
            abierto: {{ Js::from($volvioFormulario) }},
            form: {{ Js::from($formularioInicial) }},
            nuevo() {
                this.form = { id: null, tipo_estudio_id: '', obligatorio: false, momento_clinico: '', observacion: '' };
                this.mostrarFormulario();
            },
            editar(requerido) {
                this.form = { ...requerido, obligatorio: Boolean(requerido.obligatorio), observacion: requerido.observacion ?? '' };
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

        {{-- Errores de las acciones por fila --}}
        @if ($errors->any() && ! $volvioFormulario)
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        {{-- Tratamiento --}}
        <section class="dr-card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Tratamiento">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Tratamiento</p>
                <p class="mt-0.5 break-words font-display text-xl font-bold text-ink">{{ $tratamiento->nombre }}</p>
                <p class="mt-0.5 text-sm text-ink-soft"><span class="font-semibold tabular-nums text-ink">{{ $requeridos->count() }}</span> estudio(s) requerido(s)</p>
            </div>
            <x-primary-button type="button" @click="nuevo()" class="w-full sm:w-auto">
                <x-icon name="plus" class="h-5 w-5" /> Agregar estudio
            </x-primary-button>
        </section>

        <section class="dr-card overflow-hidden" aria-label="Estudios requeridos">
            @if ($requeridos->isEmpty())
                <div class="px-6 py-14 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P4" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">Este tratamiento no tiene estudios requeridos.</p>
                    <p class="mt-1 text-sm text-ink-soft">Agregue el primero con el botón «Agregar estudio».</p>
                </div>
            @else
                {{-- Tabla (desde 1280 px) --}}
                <div class="hidden xl:block">
                    <table class="dr-table">
                        <caption class="sr-only">Estudios requeridos de {{ $tratamiento->nombre }}</caption>
                        <thead>
                            <tr>
                                <th scope="col">Tipo de estudio</th>
                                <th scope="col">Obligatorio/Recomendado</th>
                                <th scope="col">Momento clínico</th>
                                <th scope="col">Observación</th>
                                <th scope="col">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requeridos as $r)
                                <tr class="align-top">
                                    <td class="font-semibold text-ink">{{ $r->tipoEstudio->nombre }}</td>
                                    <td><x-insignia :tono="$r->obligatorio ? 'marca' : 'neutro'">{{ $r->obligatorio ? 'Obligatorio' : 'Recomendado' }}</x-insignia></td>
                                    <td class="text-ink-soft">{{ ucfirst($r->momento_clinico) }}</td>
                                    <td class="whitespace-pre-line text-ink-soft">{{ $r->observacion ?? '—' }}</td>
                                    <td><x-tratamientos.requerido-acciones :r="$r" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tarjetas (móvil y tablet) --}}
                <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                    @foreach ($requeridos as $r)
                        <li class="dr-item">
                            <div class="flex items-start justify-between gap-3">
                                <p class="min-w-0 break-words font-display text-base font-bold text-ink">{{ $r->tipoEstudio->nombre }}</p>
                                <x-insignia :tono="$r->obligatorio ? 'marca' : 'neutro'">{{ $r->obligatorio ? 'Obligatorio' : 'Recomendado' }}</x-insignia>
                            </div>
                            <dl class="grid grid-cols-1 gap-2 text-sm">
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Momento clínico</dt><dd class="font-medium text-ink">{{ ucfirst($r->momento_clinico) }}</dd></div>
                                <div class="min-w-0"><dt class="text-xs text-ink-soft">Observación</dt><dd class="whitespace-pre-line break-words font-medium text-ink">{{ $r->observacion ?? '—' }}</dd></div>
                            </dl>
                            <x-tratamientos.requerido-acciones :r="$r" />
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
                <h3 id="titulo-formulario" class="text-lg font-bold text-ink" x-text="form.id ? 'Modificar estudio requerido' : 'Agregar estudio requerido'">Agregar estudio requerido</h3>
                <button type="button" @click="abierto = false" class="-me-2 rounded-lg p-2 text-ink-soft hover:bg-brand-50 hover:text-brand-800" aria-label="Cerrar formulario">
                    <x-icon name="close" />
                </button>
            </header>

            <form method="POST" :action="form.id ? {{ Js::from(route('tratamientos.estudios.modificar')) }} : {{ Js::from(route('tratamientos.estudios.guardar')) }}" class="p-5 sm:p-6">
                @csrf
                <input type="hidden" name="formulario" value="datos">
                <input type="hidden" name="tratamiento_id" value="{{ $tratamiento->id }}">
                {{-- El id viaja oculto solo al modificar --}}
                <input type="hidden" name="id" :value="form.id" :disabled="! form.id">

                <div class="max-w-3xl space-y-5">
                    @if ($volvioFormulario && $errors->any())
                        <x-alerta tipo="error">Revise los datos marcados en el formulario.</x-alerta>
                    @endif

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="tipo_estudio_id" label="Tipo de estudio" :requerido="true" :errores="$erroresDe('tipo_estudio_id')">
                            <select id="tipo_estudio_id" name="tipo_estudio_id" class="{{ $claseCampo('tipo_estudio_id') }}" x-model="form.tipo_estudio_id" required>
                                <option value="">Seleccione…</option>
                                @foreach ($tipos as $tipo)
                                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="momento_clinico" label="Momento clínico" :requerido="true" :errores="$erroresDe('momento_clinico')">
                            <select id="momento_clinico" name="momento_clinico" class="{{ $claseCampo('momento_clinico') }}" x-model="form.momento_clinico" required>
                                <option value="">Seleccione…</option>
                                @foreach (\App\Models\TratamientoTipoEstudio::MOMENTOS_CLINICOS as $momento)
                                    <option value="{{ $momento }}">{{ ucfirst($momento) }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <div class="sm:col-span-2">
                            {{-- El oculto envía 0 cuando la casilla no está marcada --}}
                            <input type="hidden" name="obligatorio" value="0">
                            <label for="obligatorio" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink transition-colors hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input id="obligatorio" name="obligatorio" type="checkbox" value="1" x-model="form.obligatorio"
                                       class="h-4 w-4 shrink-0 rounded border-gray-300 text-brand-700 shadow-sm focus:ring-brand-600">
                                <span>Obligatorio <span class="text-ink-soft">(si no se marca, es recomendado)</span></span>
                            </label>
                            @if ($volvioFormulario && $errors->has('obligatorio'))
                                <x-input-error :messages="$errors->get('obligatorio')" class="mt-1.5" />
                            @endif
                        </div>

                        <x-campo name="observacion" label="Observación (opcional)" class="sm:col-span-2" :errores="$erroresDe('observacion')">
                            <textarea id="observacion" name="observacion" rows="3" class="{{ $claseCampo('observacion') }}" x-model="form.observacion"></textarea>
                        </x-campo>
                    </div>
                </div>

                <div class="mt-8 flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <div class="flex flex-col-reverse gap-3 sm:flex-row">
                        <x-secondary-button @click="abierto = false">Cancelar</x-secondary-button>
                        <x-primary-button x-text="form.id ? 'Guardar cambios' : 'Agregar'">Guardar</x-primary-button>
                    </div>
                </div>
            </form>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('tratamientos.mostrar', $tratamiento->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al tratamiento
            </a>
        </div>
    </x-pagina>
</x-app-layout>
