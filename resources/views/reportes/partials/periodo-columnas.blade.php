{{-- Periodo y casillas de columnas del reporte dinámico; va dentro del formulario, después de los filtros.
     Sin selección previa, van todas marcadas. --}}
@php
    $marcadas = old('columnas', request('columnas', array_keys($columnas)));
@endphp

<fieldset>
    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Periodo</legend>
    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-campo name="dinamico_fecha_inicio" label="Fecha inicial" :requerido="true">
            <x-text-input id="dinamico_fecha_inicio" name="fecha_inicio" type="date" :value="old('fecha_inicio', request('fecha_inicio'))" required />
        </x-campo>

        <x-campo name="dinamico_fecha_fin" label="Fecha final" :requerido="true">
            <x-text-input id="dinamico_fecha_fin" name="fecha_fin" type="date" :value="old('fecha_fin', request('fecha_fin'))" required />
        </x-campo>
    </div>
</fieldset>

<fieldset>
    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Columnas del reporte</legend>
    <div class="mt-4 grid grid-cols-1 gap-2 min-[420px]:grid-cols-2 lg:grid-cols-4">
        @foreach ($columnas as $clave => $encabezado)
            <label class="flex min-h-11 cursor-pointer items-center gap-3 rounded-lg border border-line bg-white px-3 py-2 text-sm text-ink transition-colors hover:border-brand-300 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                <input type="checkbox" name="columnas[]" value="{{ $clave }}" @checked(in_array($clave, (array) $marcadas, true))
                       class="h-4 w-4 shrink-0 rounded border-gray-300 text-brand-700 shadow-sm focus:ring-brand-600">
                <span class="min-w-0 break-words">{{ $encabezado }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

<div class="border-t border-line pt-5">
    <x-primary-button class="w-full sm:w-auto">Generar</x-primary-button>
</div>
