{{-- CU23 Consultar método de pago: detalle del método del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Método de pago: '.$metodoPago->nombre" subtitulo="Datos del método de pago del catálogo."
                           :volver="route('metodos-pago.listar')" volver-texto="Métodos de pago" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-metodo-pago">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-metodo-pago" class="text-lg font-bold text-ink">Datos del método de pago</h3>
                <x-insignia-activo :activo="$metodoPago->estado === 'activo'" class="self-start" />
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm">
                <x-dato etiqueta="Nombre">{{ $metodoPago->nombre }}</x-dato>
                <x-dato etiqueta="Descripción" class="!border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $metodoPago->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('metodos-pago.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a métodos de pago
            </a>

            {{-- Dar de baja / habilitar desde la ficha --}}
            <x-catalogos.baja-habilitar :registro="$metodoPago" ruta="metodos-pago" etiqueta="el método de pago" class="w-full sm:w-auto" boton="w-full sm:w-auto" />
        </div>
    </x-pagina>
</x-app-layout>
