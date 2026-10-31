{{-- Reporte estático: tipo y periodo (mismo método del controlador con modo=estatico) --}}
<section class="dr-card overflow-hidden" aria-labelledby="reportes-predefinidos">
    <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
        <h3 id="reportes-predefinidos" class="text-lg font-bold text-ink">Reportes predefinidos</h3>
        <p class="mt-0.5 text-sm text-ink-soft">Elija un reporte y el periodo que desea consultar.</p>
    </header>

    <form method="GET" action="{{ $accion }}" class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-4">
        <input type="hidden" name="modo" value="estatico">

        <x-campo name="tipo" label="Reporte" :requerido="true" class="sm:col-span-2">
            <select id="tipo" name="tipo" class="dr-input" required>
                <option value="">Seleccione…</option>
                @foreach ($estaticos as $clave => $titulo)
                    <option value="{{ $clave }}" @selected(old('tipo', request('tipo')) === $clave)>{{ $titulo }}</option>
                @endforeach
            </select>
        </x-campo>

        <x-campo name="estatico_fecha_inicio" label="Fecha inicial" :requerido="true">
            <x-text-input id="estatico_fecha_inicio" name="fecha_inicio" type="date" :value="old('fecha_inicio', request('fecha_inicio'))" required />
        </x-campo>

        <x-campo name="estatico_fecha_fin" label="Fecha final" :requerido="true">
            <x-text-input id="estatico_fecha_fin" name="fecha_fin" type="date" :value="old('fecha_fin', request('fecha_fin'))" required />
        </x-campo>

        <div class="sm:col-span-2 lg:col-span-4">
            <x-primary-button class="w-full sm:w-auto">Generar</x-primary-button>
        </div>
    </form>
</section>
