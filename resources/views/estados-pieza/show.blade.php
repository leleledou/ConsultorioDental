{{-- CU22 Consultar estado de pieza: detalle del estado del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Estado de pieza: '.$estadoPieza->nombre" subtitulo="Datos del estado de pieza del catálogo."
                           :volver="route('estados-pieza.listar')" volver-texto="Estados de pieza" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-estado-pieza">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-estado-pieza" class="text-lg font-bold text-ink">Datos del estado de pieza</h3>
                <x-insignia-activo :activo="$estadoPieza->estado === 'activo'" class="self-start" />
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Código">{{ $estadoPieza->codigo }}</x-dato>
                <x-dato etiqueta="Nombre">{{ $estadoPieza->nombre }}</x-dato>
                <x-dato etiqueta="Color de referencia"><x-estados-pieza.color :color="$estadoPieza->color_referencia" /></x-dato>
                <x-dato etiqueta="Descripción" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $estadoPieza->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('estados-pieza.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a estados de pieza
            </a>

            {{-- Dar de baja / habilitar desde la ficha --}}
            <x-catalogos.baja-habilitar :registro="$estadoPieza" ruta="estados-pieza" etiqueta="el estado de pieza" class="w-full sm:w-auto" boton="w-full sm:w-auto" />
        </div>
    </x-pagina>
</x-app-layout>
