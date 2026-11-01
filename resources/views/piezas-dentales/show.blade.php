{{-- CU22 Consultar pieza dental: detalle de la pieza del catálogo (solo lectura). --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Pieza dental: '.$pieza->codigo_fdi" subtitulo="Datos de la pieza dental del catálogo."
                           :volver="route('piezas-dentales.listar')" volver-texto="Piezas dentales" />
    </x-slot>

    <x-pagina>
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-pieza">
            <h3 id="datos-pieza" class="text-lg font-bold text-ink">Datos de la pieza dental</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Código FDI"><span class="tabular-nums">{{ $pieza->codigo_fdi }}</span></x-dato>
                <x-dato etiqueta="Nombre">{{ $pieza->nombre }}</x-dato>
                <x-dato etiqueta="Cuadrante"><span class="tabular-nums">{{ $pieza->cuadrante }}</span></x-dato>
                <x-dato etiqueta="Tipo de pieza">{{ ucfirst($pieza->tipo_pieza) }}</x-dato>
                <x-dato etiqueta="Dentición">{{ ucfirst($pieza->tipo_denticion) }}</x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('piezas-dentales.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a piezas dentales
            </a>
        </div>
    </x-pagina>
</x-app-layout>
