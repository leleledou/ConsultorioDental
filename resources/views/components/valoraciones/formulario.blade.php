{{-- Formulario de valoración para implante (CU33), compartido por el alta (valoraciones/index) y la
     modificación (valoraciones/show). Sin $valoracion es un alta.
     Al elegir hueso "insuficiente" se marca "Requiere injerto" como propuesta; el odontólogo puede desmarcarlo. --}}
@props(['paciente', 'piezas', 'estudios', 'tratamientos', 'valoracion' => null])

@php
    $alta = $valoracion === null;
    $clase = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');

    $huesoInicial = old('estado_hueso', $valoracion?->estado_hueso ?? '');
    $injertoInicial = (bool) old('requiere_injerto', $valoracion?->requiere_injerto ?? false);
@endphp

<form method="POST" action="{{ $alta ? route('valoraciones.guardar') : route('valoraciones.modificar') }}" class="space-y-6"
      x-data="{
          hueso: {{ Js::from($huesoInicial) }},
          injerto: {{ Js::from($injertoInicial) }},
          cambiarHueso() { if (this.hueso === 'insuficiente') this.injerto = true; },
      }">
    @csrf
    <input type="hidden" name="formulario" value="{{ $alta ? 'crear' : 'modificar' }}">
    @if ($alta)
        <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
    @else
        <input type="hidden" name="id" value="{{ $valoracion->id }}">
    @endif

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Pieza y fecha</legend>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-campo name="pieza_dental_id" label="Pieza dental" :requerido="true" :errores="$errors->get('pieza_dental_id')">
                <select id="pieza_dental_id" name="pieza_dental_id" class="{{ $clase('pieza_dental_id') }}" required>
                    <option value="">Seleccione…</option>
                    @foreach ($piezas as $pieza)
                        <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id', $valoracion?->pieza_dental_id) == $pieza->id)>{{ $pieza->codigo_fdi }} · {{ $pieza->nombre }}</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="fecha_valoracion" label="Fecha de la valoración" :requerido="true" ayuda="No puede ser futura." :errores="$errors->get('fecha_valoracion')">
                <x-text-input id="fecha_valoracion" name="fecha_valoracion" type="date" max="{{ today()->toDateString() }}"
                              :value="old('fecha_valoracion', $valoracion?->fecha_valoracion?->toDateString() ?? today()->toDateString())" required :error="$errors->has('fecha_valoracion')" />
            </x-campo>
        </div>
    </fieldset>

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Valoración</legend>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-campo name="estado_hueso" label="Estado del hueso" :requerido="true" :errores="$errors->get('estado_hueso')">
                <select id="estado_hueso" name="estado_hueso" class="{{ $clase('estado_hueso') }}" x-model="hueso" @change="cambiarHueso()" required>
                    <option value="">Seleccione…</option>
                    @foreach (['suficiente' => 'Suficiente', 'regular' => 'Regular', 'insuficiente' => 'Insuficiente'] as $valor => $texto)
                        <option value="{{ $valor }}">{{ $texto }}</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="resultado" label="Resultado" :requerido="true" :errores="$errors->get('resultado')">
                <select id="resultado" name="resultado" class="{{ $clase('resultado') }}" required>
                    <option value="">Seleccione…</option>
                    @foreach (['apto' => 'Apto', 'apto con condiciones' => 'Apto con condiciones', 'no apto' => 'No apto'] as $valor => $texto)
                        <option value="{{ $valor }}" @selected(old('resultado', $valoracion?->resultado) === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </x-campo>

            {{-- Casilla táctil; el 0 oculto viaja si se desmarca --}}
            <div class="sm:col-span-2">
                <label for="requiere_injerto" class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line px-3 py-2 text-sm font-semibold text-ink has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                    <input type="hidden" name="requiere_injerto" value="0">
                    <input id="requiere_injerto" type="checkbox" name="requiere_injerto" value="1" x-model="injerto"
                           class="h-4 w-4 rounded border-line text-brand-700 focus:ring-brand-500">
                    Requiere injerto óseo
                </label>
                <p class="mt-1.5 text-xs text-ink-soft" x-show="hueso === 'insuficiente'" x-cloak>Con hueso insuficiente se propone el injerto; puede desmarcarlo si no corresponde.</p>
            </div>

            <x-campo name="observaciones_clinicas" label="Observaciones clínicas (opcional)" class="sm:col-span-2" :errores="$errors->get('observaciones_clinicas')">
                <textarea id="observaciones_clinicas" name="observaciones_clinicas" rows="3" class="{{ $clase('observaciones_clinicas') }}">{{ old('observaciones_clinicas', $valoracion?->observaciones_clinicas) }}</textarea>
            </x-campo>
        </div>
    </fieldset>

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Respaldo y derivación</legend>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-campo name="estudio_diagnostico_id" label="Estudio que la respalda (opcional)" :errores="$errors->get('estudio_diagnostico_id')">
                <select id="estudio_diagnostico_id" name="estudio_diagnostico_id" class="{{ $clase('estudio_diagnostico_id') }}">
                    <option value="">Ninguno</option>
                    @foreach ($estudios as $e)
                        <option value="{{ $e->id }}" @selected(old('estudio_diagnostico_id', $valoracion?->estudio_diagnostico_id) == $e->id)>{{ $e->tipoEstudio->nombre }} ({{ $e->fecha_estudio->format('d/m/Y') }})</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="tratamiento_aplicado_id" label="Tratamiento derivado (opcional)" :errores="$errors->get('tratamiento_aplicado_id')">
                <select id="tratamiento_aplicado_id" name="tratamiento_aplicado_id" class="{{ $clase('tratamiento_aplicado_id') }}">
                    <option value="">Ninguno</option>
                    @foreach ($tratamientos as $t)
                        <option value="{{ $t->id }}" @selected(old('tratamiento_aplicado_id', $valoracion?->tratamiento_aplicado_id) == $t->id)>{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio->format('d/m/Y') }})</option>
                    @endforeach
                </select>
            </x-campo>
        </div>
    </fieldset>

    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
        <div class="flex flex-col-reverse gap-3 sm:flex-row">
            <x-secondary-button @click="$dispatch('close-modal', {{ Js::from($alta ? 'nueva-valoracion' : 'modificar-valoracion') }})">Cancelar</x-secondary-button>
            <x-primary-button>{{ $alta ? 'Registrar valoración' : 'Guardar cambios' }}</x-primary-button>
        </div>
    </div>
</form>
