{{-- CU23 Gestionar historia clínica. No existe eliminar historia clínica (RF-10). --}}
@php
    $paciente = $historia->paciente;
    $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Historia clínica: '.$paciente->nombreCompleto()"
                           subtitulo="Antecedentes médicos, hábitos y observaciones generales del paciente."
                           :volver="route('pacientes.mostrar', $historia->paciente_id)" volver-texto="Paciente" />
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

        {{-- Resumen de la historia --}}
        <section class="dr-card p-5 sm:p-6" aria-label="Datos de la historia">
            <div class="flex min-w-0 items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                <div class="min-w-0">
                    <p class="font-display text-xl font-bold text-ink">{{ $paciente->nombreCompleto() }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">{{ $paciente->edad() }} años</p>
                </div>
            </div>
            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-4 border-t border-line pt-5 text-sm sm:grid-cols-3">
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">N.º de historia</dt>
                    <dd class="mt-1 font-medium tabular-nums text-ink">{{ $historia->id }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">CI</dt>
                    <dd class="mt-1 font-medium tabular-nums text-ink">{{ $paciente->ci }}</dd>
                </div>
                <div class="min-w-0">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Fecha de apertura</dt>
                    <dd class="mt-1 font-medium text-ink">{{ $historia->fecha_apertura->format('d/m/Y') }}</dd>
                </div>
            </dl>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
            <div class="min-w-0 space-y-6 lg:col-span-2">
                {{-- Antecedentes registrados en la historia: una sola lista (cada fila lleva sus propios formularios) --}}
                <section class="dr-card overflow-hidden" aria-labelledby="antecedentes-registrados">
                    <header class="flex items-center justify-between gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                        <h3 id="antecedentes-registrados" class="text-lg font-bold text-ink">Antecedentes médicos</h3>
                        <span class="badge bg-brand-50 text-brand-800 ring-1 ring-inset ring-brand-200">{{ $historia->antecedentes->count() }} registrado(s)</span>
                    </header>

                    @forelse ($historia->antecedentes as $a)
                        {{-- editando: muestra el formulario de Modificar observación de la fila --}}
                        <article class="border-b border-line px-5 py-4 last:border-b-0 sm:px-6" x-data="{ editando: false }">
                            <div class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1">
                                <h4 class="min-w-0 break-words font-display text-base font-bold text-ink">{{ $a->nombre }}</h4>
                                <p class="text-xs font-semibold text-ink-soft">Registrado el {{ \Carbon\Carbon::parse($a->pivot->fecha_registro)->format('d/m/Y') }}</p>
                            </div>

                            <div x-show="! editando" class="mt-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Observación</p>
                                <p class="mt-0.5 whitespace-pre-line break-words text-sm text-ink">{{ $a->pivot->observacion ?? '—' }}</p>
                            </div>

                            <form x-show="editando" x-cloak method="POST" action="{{ route('historias.antecedentes.modificar') }}" class="mt-3 space-y-3">
                                @csrf
                                <input type="hidden" name="id" value="{{ $a->pivot->id }}">
                                <label for="observacion-{{ $a->pivot->id }}" class="block text-sm font-semibold text-ink">Observación</label>
                                <textarea id="observacion-{{ $a->pivot->id }}" name="observacion" rows="3" class="dr-input">{{ $a->pivot->observacion }}</textarea>
                                <div class="flex flex-col gap-2 sm:flex-row">
                                    <x-primary-button class="btn-sm">Guardar</x-primary-button>
                                    <x-secondary-button class="btn-sm" @click="editando = false">Cancelar</x-secondary-button>
                                </div>
                            </form>

                            <div class="mt-3 flex flex-wrap items-center gap-2" x-show="! editando">
                                <x-secondary-button class="btn-sm" @click="editando = true">
                                    <x-icon name="edit" class="h-4 w-4" /> Modificar observación
                                </x-secondary-button>

                                {{-- Quitar (el id viaja oculto) --}}
                                <form method="POST" action="{{ route('historias.antecedentes.quitar') }}"
                                      onsubmit="return confirm({{ Js::from('¿Quitar el antecedente '.$a->nombre.' de la historia clínica?') }});">
                                    @csrf
                                    <input type="hidden" name="id" value="{{ $a->pivot->id }}">
                                    <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Quitar</x-danger-button>
                                </form>
                            </div>
                        </article>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                                <x-icon name="P4" class="h-7 w-7" />
                            </span>
                            <p class="mt-4 text-base font-semibold text-ink">La historia no tiene antecedentes registrados.</p>
                            <p class="mt-1 text-sm text-ink-soft">Asigne uno desde el formulario «Asignar antecedente».</p>
                        </div>
                    @endforelse
                </section>

                {{-- Asignar antecedente --}}
                <section class="dr-card p-5 sm:p-6" aria-labelledby="asignar-antecedente">
                    <h3 id="asignar-antecedente" class="text-lg font-bold text-ink">Asignar antecedente</h3>

                    <form method="POST" action="{{ route('historias.antecedentes.asignar') }}" class="mt-5 space-y-5">
                        @csrf
                        <input type="hidden" name="historia_clinica_id" value="{{ $historia->id }}">

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <x-campo name="antecedente_medico_id" label="Antecedente" :requerido="true" :errores="$errors->get('antecedente_medico_id')">
                                <select id="antecedente_medico_id" name="antecedente_medico_id" class="dr-input @error('antecedente_medico_id') dr-input-error @enderror" required>
                                    <option value="">Seleccione…</option>
                                    @foreach ($catalogo as $antecedente)
                                        <option value="{{ $antecedente->id }}" @selected(old('antecedente_medico_id') == $antecedente->id)>{{ $antecedente->nombre }}</option>
                                    @endforeach
                                </select>
                            </x-campo>

                            <x-campo name="observacion" label="Observación (opcional)" :errores="$errors->get('observacion')">
                                <textarea id="observacion" name="observacion" rows="3" class="dr-input @error('observacion') dr-input-error @enderror">{{ old('observacion') }}</textarea>
                            </x-campo>
                        </div>

                        <x-primary-button class="w-full sm:w-auto"><x-icon name="plus" class="h-4 w-4" /> Asignar</x-primary-button>
                    </form>
                </section>
            </div>

            {{-- Hábitos y observaciones generales (el id viaja oculto) --}}
            <section class="dr-card p-5 sm:p-6" aria-labelledby="habitos-observaciones">
                <h3 id="habitos-observaciones" class="text-lg font-bold text-ink">Hábitos y observaciones</h3>

                <form method="POST" action="{{ route('historias.modificar') }}" class="mt-5 space-y-5">
                    @csrf
                    <input type="hidden" name="id" value="{{ $historia->id }}">

                    <x-campo name="habitos" label="Hábitos (opcional)" :errores="$errors->get('habitos')">
                        <textarea id="habitos" name="habitos" rows="4" class="dr-input @error('habitos') dr-input-error @enderror">{{ old('habitos', $historia->habitos) }}</textarea>
                    </x-campo>

                    <x-campo name="observaciones_generales" label="Observaciones generales (opcional)" :errores="$errors->get('observaciones_generales')">
                        <textarea id="observaciones_generales" name="observaciones_generales" rows="4" class="dr-input @error('observaciones_generales') dr-input-error @enderror">{{ old('observaciones_generales', $historia->observaciones_generales) }}</textarea>
                    </x-campo>

                    <x-primary-button class="w-full sm:w-auto">Guardar</x-primary-button>
                </form>
            </section>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $historia->paciente_id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
        </div>
    </x-pagina>
</x-app-layout>
