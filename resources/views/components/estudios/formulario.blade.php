{{-- Formulario de estudio diagnóstico (CU32), compartido por el alta (estudios/index) y la modificación (estudios/show).
     Alterna con Alpine entre "Subir archivo" y "Registrar enlace": el control que no se usa queda deshabilitado
     y no viaja, así no se pueden llenar los dos. Sin $estudio es un alta. --}}
@props(['paciente', 'tipos', 'piezas', 'tratamientos', 'estudio' => null, 'esEnlace' => false, 'preseleccion' => []])

@php
    $alta = $estudio === null;
    $clase = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');

    // Modo inicial: el que volvió con error, el del estudio que se modifica, o archivo.
    $modoInicial = old('modo', $esEnlace ? 'enlace' : 'archivo');
    $enlaceInicial = old('enlace', $esEnlace ? $estudio->ruta_archivo : '');
@endphp

<form method="POST" action="{{ $alta ? route('estudios.guardar') : route('estudios.modificar') }}" enctype="multipart/form-data"
      class="space-y-6" x-data="{ modo: {{ Js::from($modoInicial) }} }">
    @csrf
    <input type="hidden" name="formulario" value="{{ $alta ? 'crear' : 'modificar' }}">
    <input type="hidden" name="modo" :value="modo">
    @if ($alta)
        <input type="hidden" name="paciente_id" value="{{ $paciente->id }}">
    @else
        <input type="hidden" name="id" value="{{ $estudio->id }}">
    @endif

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Estudio</legend>
        <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-campo name="tipo_estudio_id" label="Tipo de estudio" :requerido="true" :errores="$errors->get('tipo_estudio_id')">
                <select id="tipo_estudio_id" name="tipo_estudio_id" class="{{ $clase('tipo_estudio_id') }}" required>
                    <option value="">Seleccione…</option>
                    @foreach ($tipos as $tipo)
                        <option value="{{ $tipo->id }}" @selected(old('tipo_estudio_id', $estudio?->tipo_estudio_id ?? ($preseleccion['tipo_estudio_id'] ?? null)) == $tipo->id)>{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="fecha_estudio" label="Fecha del estudio" :requerido="true" ayuda="No puede ser futura." :errores="$errors->get('fecha_estudio')">
                <x-text-input id="fecha_estudio" name="fecha_estudio" type="date" max="{{ today()->toDateString() }}"
                              :value="old('fecha_estudio', $estudio?->fecha_estudio?->toDateString() ?? today()->toDateString())" required :error="$errors->has('fecha_estudio')" />
            </x-campo>

            <x-campo name="pieza_dental_id" label="Pieza dental (opcional)" :errores="$errors->get('pieza_dental_id')">
                <select id="pieza_dental_id" name="pieza_dental_id" class="{{ $clase('pieza_dental_id') }}">
                    <option value="">Ninguna</option>
                    @foreach ($piezas as $pieza)
                        <option value="{{ $pieza->id }}" @selected(old('pieza_dental_id', $estudio?->pieza_dental_id) == $pieza->id)>{{ $pieza->codigo_fdi }} · {{ $pieza->nombre }}</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="tratamiento_aplicado_id" label="Tratamiento aplicado (opcional)" :errores="$errors->get('tratamiento_aplicado_id')">
                <select id="tratamiento_aplicado_id" name="tratamiento_aplicado_id" class="{{ $clase('tratamiento_aplicado_id') }}">
                    <option value="">Ninguno</option>
                    @foreach ($tratamientos as $t)
                        <option value="{{ $t->id }}" @selected(old('tratamiento_aplicado_id', $estudio?->tratamiento_aplicado_id ?? ($preseleccion['tratamiento_aplicado_id'] ?? null)) == $t->id)>{{ $t->tratamiento->nombre }} ({{ $t->fecha_inicio->format('d/m/Y') }})</option>
                    @endforeach
                </select>
            </x-campo>

            <x-campo name="centro_radiologico" label="Centro radiológico (opcional)" class="sm:col-span-2" :errores="$errors->get('centro_radiologico')">
                <x-text-input id="centro_radiologico" name="centro_radiologico" type="text" maxlength="80" :value="old('centro_radiologico', $estudio?->centro_radiologico)" :error="$errors->has('centro_radiologico')" />
            </x-campo>
        </div>
    </fieldset>

    {{-- Archivo o enlace: uno solo --}}
    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Resultado del estudio</legend>

        <div class="mt-4 inline-flex rounded-lg border border-line p-1" role="radiogroup" aria-label="Forma de registrar el resultado">
            <button type="button" role="radio" :aria-checked="modo === 'archivo'" @click="modo = 'archivo'"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-semibold transition sm:min-h-9"
                    :class="modo === 'archivo' ? 'bg-brand-700 text-white' : 'text-ink-soft hover:text-ink'">
                <x-icon name="download" class="h-4 w-4 rotate-180" /> Subir archivo
            </button>
            <button type="button" role="radio" :aria-checked="modo === 'enlace'" @click="modo = 'enlace'"
                    class="inline-flex min-h-11 items-center gap-2 rounded-md px-3 text-sm font-semibold transition sm:min-h-9"
                    :class="modo === 'enlace' ? 'bg-brand-700 text-white' : 'text-ink-soft hover:text-ink'">
                <x-icon name="arrow" class="h-4 w-4" /> Registrar enlace
            </button>
        </div>

        <div class="mt-4">
            <x-campo name="archivo" :label="$alta ? 'Archivo' : 'Archivo nuevo'" :requerido="$alta"
                     :ayuda="$alta || $esEnlace ? 'PDF, JPG o PNG de hasta 10 MB.' : 'PDF, JPG o PNG de hasta 10 MB. Déjelo vacío para conservar el archivo actual.'"
                     :errores="$errors->get('archivo')" x-show="modo === 'archivo'">
                <input id="archivo" name="archivo" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                       :disabled="modo !== 'archivo'"
                       @class(['block w-full rounded-lg border border-dashed px-3 py-3 text-sm text-ink-soft file:me-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-brand-800 hover:file:bg-brand-100', 'border-red-400' => $errors->has('archivo'), 'border-line' => ! $errors->has('archivo')])
                       @if ($errors->has('archivo')) aria-invalid="true" @endif>
            </x-campo>

            <x-campo name="enlace" label="Enlace del centro radiológico" :requerido="$alta" ayuda="Dirección web que empiece con http:// o https://."
                     :errores="$errors->get('enlace')" x-show="modo === 'enlace'" x-cloak>
                <x-text-input id="enlace" name="enlace" type="url" maxlength="255" placeholder="https://" :value="$enlaceInicial"
                              x-bind:disabled="modo !== 'enlace'" :error="$errors->has('enlace')" />
            </x-campo>
        </div>
    </fieldset>

    <fieldset>
        <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Hallazgos</legend>
        <x-campo name="hallazgos" label="Hallazgos (opcional)" class="mt-4" :errores="$errors->get('hallazgos')">
            <textarea id="hallazgos" name="hallazgos" rows="3" class="{{ $clase('hallazgos') }}">{{ old('hallazgos', $estudio?->hallazgos) }}</textarea>
        </x-campo>
    </fieldset>

    <div class="flex flex-col-reverse gap-3 border-t border-line pt-5 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
        <div class="flex flex-col-reverse gap-3 sm:flex-row">
            <x-secondary-button @click="$dispatch('close-modal', {{ Js::from($alta ? 'nuevo-estudio' : 'modificar-estudio') }})">Cancelar</x-secondary-button>
            <x-primary-button>{{ $alta ? 'Registrar estudio' : 'Guardar cambios' }}</x-primary-button>
        </div>
    </div>
</form>
