{{-- Reporte estático: tipo y periodo (mismo método del controlador con modo=estatico) --}}
@php
    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Reportes predefinidos</h3>

    <form method="GET" action="{{ $accion }}" class="mt-6 grid grid-cols-1 sm:grid-cols-4 gap-6 items-end">
        <input type="hidden" name="modo" value="estatico">

        <div class="sm:col-span-2">
            <x-input-label for="tipo" value="Reporte" />
            <select id="tipo" name="tipo" class="{{ $claseCampo }}" required>
                <option value="">Seleccione…</option>
                @foreach ($estaticos as $clave => $titulo)
                    <option value="{{ $clave }}" @selected(old('tipo', request('tipo')) === $clave)>{{ $titulo }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <x-input-label for="estatico_fecha_inicio" value="Fecha inicial" />
            <x-text-input id="estatico_fecha_inicio" name="fecha_inicio" type="date" class="mt-1 block w-full" :value="old('fecha_inicio', request('fecha_inicio'))" required />
        </div>

        <div>
            <x-input-label for="estatico_fecha_fin" value="Fecha final" />
            <x-text-input id="estatico_fecha_fin" name="fecha_fin" type="date" class="mt-1 block w-full" :value="old('fecha_fin', request('fecha_fin'))" required />
        </div>

        <div class="sm:col-span-4">
            <x-primary-button>Generar</x-primary-button>
        </div>
    </form>
</div>
