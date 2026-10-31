{{-- Periodo y casillas de columnas del reporte dinámico. Sin selección previa, van todas marcadas. --}}
@php
    $marcadas = old('columnas', request('columnas', array_keys($columnas)));
@endphp

<div>
    <x-input-label for="dinamico_fecha_inicio" value="Fecha inicial" />
    <x-text-input id="dinamico_fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio', request('fecha_inicio'))" required />
</div>

<div>
    <x-input-label for="dinamico_fecha_fin" value="Fecha final" />
    <x-text-input id="dinamico_fecha_fin" name="fecha_fin" type="date" class="mt-1 block w-full" :value="old('fecha_fin', request('fecha_fin'))" required />
</div>

<fieldset class="sm:col-span-4">
    <legend class="block font-medium text-sm text-gray-700 dark:text-gray-300">Columnas</legend>
    <div class="mt-2 grid grid-cols-2 sm:grid-cols-4 gap-2">
        @foreach ($columnas as $clave => $encabezado)
            <label class="inline-flex items-center gap-2">
                <input type="checkbox" name="columnas[]" value="{{ $clave }}" @checked(in_array($clave, (array) $marcadas, true))
                       class="rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600">
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ $encabezado }}</span>
            </label>
        @endforeach
    </div>
</fieldset>

<div class="sm:col-span-4">
    <x-primary-button>Generar</x-primary-button>
</div>
