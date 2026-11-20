{{-- CU30 Registrar sesión de evolución: ficha de la sesión y sus materiales (RF-45, RF-46). --}}
@php
    $ta = $sesion->tratamientoAplicado;
    $paciente = $ta->plan->paciente;
    $editable = $sesion->estado !== 'anulada';
    $tonoEstado = ['realizada' => 'exito', 'pendiente' => 'aviso', 'anulada' => 'error'][$sesion->estado] ?? 'neutro';
    $cantidad = fn ($valor) => rtrim(rtrim(number_format((float) $valor, 2, ',', '.'), '0'), ',');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Sesión '.$sesion->numero_sesion.': '.$ta->tratamiento->nombre"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="route('sesiones.evolucion', $ta->id)" volver-texto="Evolución del tratamiento" />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        @unless ($editable)
            <x-alerta tipo="aviso">Esta sesión está anulada: sus materiales ya no se modifican.</x-alerta>
        @endunless

        {{-- Datos de la sesión --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-sesion">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-sesion" class="text-lg font-bold text-ink">Datos de la sesión</h3>
                <x-insignia :tono="$tonoEstado" class="self-start">{{ ucfirst($sesion->estado) }}</x-insignia>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <x-dato etiqueta="Número">Sesión {{ $sesion->numero_sesion }}</x-dato>
                <x-dato etiqueta="Fecha"><span class="tabular-nums">{{ $sesion->fecha->format('d/m/Y') }}</span></x-dato>
                <x-dato etiqueta="Duración real">{{ $sesion->duracion_real_minutos }} min</x-dato>
                <x-dato etiqueta="Próxima sesión"><span class="tabular-nums">{{ $sesion->fecha_proxima_sesion?->format('d/m/Y') ?? '—' }}</span></x-dato>
                <x-dato etiqueta="Odontólogo">{{ $sesion->odontologo->nombres }} {{ $sesion->odontologo->apellidos }}</x-dato>
                <x-dato etiqueta="Tratamiento">
                    <a href="{{ route('tratamientos-aplicados.mostrar', $ta->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $ta->tratamiento->nombre }}</a>
                </x-dato>
                <x-dato etiqueta="Paciente">
                    <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $paciente->nombreCompleto() }}</a>
                </x-dato>
                <x-dato etiqueta="Cita de origen">
                    @if ($sesion->cita)
                        <a href="{{ route('citas.mostrar', $sesion->cita_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $sesion->cita->fecha->format('d/m/Y') }} · {{ $sesion->cita->horaInicioCorta() }}</a>
                    @else
                        —
                    @endif
                </x-dato>
                <x-dato etiqueta="Avance técnico" class="sm:col-span-2 lg:col-span-4">
                    <span class="whitespace-pre-line">{{ $sesion->avance_tecnico ?? '—' }}</span>
                </x-dato>
                <x-dato etiqueta="Observaciones" class="sm:col-span-2 lg:col-span-4 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $sesion->observaciones ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- Materiales: cada fila lleva sus formularios, por eso una sola lista en todos los anchos --}}
        <section class="dr-card overflow-hidden" aria-labelledby="materiales-sesion">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="materiales-sesion" class="text-lg font-bold text-ink">Materiales usados <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $sesion->materiales->count() }})</span></h3>
            </header>

            @if ($sesion->materiales->isEmpty())
                <div class="px-6 py-10 text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="P2" class="h-6 w-6" />
                    </span>
                    <p class="mt-3 text-base font-semibold text-ink">No se registraron materiales en esta sesión.</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($sesion->materiales as $registro)
                        <li class="px-5 py-4 sm:px-6" x-data="{ editando: false }">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0">
                                    <p class="break-words font-semibold text-ink">{{ $registro->material->nombre }}</p>
                                    <p class="mt-0.5 text-sm text-ink-soft">
                                        <span class="font-semibold tabular-nums text-ink">{{ $cantidad($registro->cantidad) }}</span> {{ $registro->material->unidad_medida }}
                                        @if ($registro->observacion) · {{ $registro->observacion }} @endif
                                    </p>
                                </div>

                                @if ($editable)
                                    <div class="flex flex-wrap items-center gap-2" x-show="! editando">
                                        <x-secondary-button class="btn-sm" @click="editando = true"><x-icon name="edit" class="h-4 w-4" /> Modificar</x-secondary-button>
                                        {{-- Quitar (el id viaja oculto) --}}
                                        <form method="POST" action="{{ route('sesiones.material.quitar') }}"
                                              onsubmit="return confirm({{ Js::from('¿Quitar '.$registro->material->nombre.' de la sesión?') }});">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $registro->id }}">
                                            <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Quitar</x-danger-button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            @if ($editable)
                                {{-- Modificar cantidad y observación (el id viaja oculto) --}}
                                <form x-show="editando" x-cloak method="POST" action="{{ route('sesiones.material.modificar') }}"
                                      class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] sm:items-end">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $registro->id }}">
                                    <div>
                                        <label for="cantidad_{{ $registro->id }}" class="block text-sm font-semibold text-ink">Cantidad ({{ $registro->material->unidad_medida }})<span class="ms-0.5 text-red-700" aria-hidden="true">*</span></label>
                                        <input id="cantidad_{{ $registro->id }}" type="number" name="cantidad" min="0.01" max="999999.99" step="0.01" inputmode="decimal" value="{{ (float) $registro->cantidad }}" class="dr-input mt-1.5 tabular-nums" required>
                                    </div>
                                    <div>
                                        <label for="observacion_{{ $registro->id }}" class="block text-sm font-semibold text-ink">Observación</label>
                                        <input id="observacion_{{ $registro->id }}" type="text" name="observacion" value="{{ $registro->observacion }}" class="dr-input mt-1.5">
                                    </div>
                                    <div class="flex gap-2">
                                        <x-primary-button class="btn-sm">Guardar</x-primary-button>
                                        <x-secondary-button class="btn-sm" @click="editando = false">Cancelar</x-secondary-button>
                                    </div>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Agregar material --}}
        @if ($editable && $materiales->isNotEmpty())
            <form method="POST" action="{{ route('sesiones.material.guardar') }}" class="dr-card overflow-hidden" aria-labelledby="agregar-material">
                @csrf
                <input type="hidden" name="sesion_id" value="{{ $sesion->id }}">

                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="agregar-material" class="text-lg font-bold text-ink">Agregar material</h3>
                    <p class="mt-0.5 text-sm text-ink-soft">Solo se listan los materiales activos que todavía no están en la sesión.</p>
                </header>

                <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-3 sm:p-6">
                    <x-campo name="material_id" label="Material" :requerido="true" :errores="$errors->get('material_id')">
                        <select id="material_id" name="material_id" class="dr-input @error('material_id') dr-input-error @enderror" required>
                            <option value="">Seleccione…</option>
                            @foreach ($materiales as $m)
                                <option value="{{ $m->id }}" @selected(old('material_id') == $m->id)>{{ $m->nombre }} ({{ $m->unidad_medida }})</option>
                            @endforeach
                        </select>
                    </x-campo>

                    <x-campo name="cantidad" label="Cantidad" :requerido="true" ayuda="Mayor a 0." :errores="$errors->get('cantidad')">
                        <x-text-input id="cantidad" name="cantidad" type="number" min="0.01" max="999999.99" step="0.01" inputmode="decimal" class="tabular-nums" :value="old('cantidad')" required :error="$errors->has('cantidad')" />
                    </x-campo>

                    <x-campo name="observacion" label="Observación (opcional)" :errores="$errors->get('observacion')">
                        <x-text-input id="observacion" name="observacion" type="text" :value="old('observacion')" :error="$errors->has('observacion')" />
                    </x-campo>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                    <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                    <x-primary-button class="w-full sm:w-auto"><x-icon name="plus" class="h-4 w-4" /> Agregar material</x-primary-button>
                </div>
            </form>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('sesiones.evolucion', $ta->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Evolución del tratamiento
            </a>
            <a href="{{ route('tratamientos-aplicados.mostrar', $ta->id) }}" class="btn btn-secondary">Tratamiento aplicado</a>
        </div>
    </x-pagina>
</x-app-layout>
