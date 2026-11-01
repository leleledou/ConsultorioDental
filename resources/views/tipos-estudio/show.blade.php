{{-- CU18 Consultar tipo de estudio: detalle del tipo de estudio del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Tipo de estudio: '.$tipoEstudio->nombre" subtitulo="Datos del tipo de estudio del catálogo."
                           :volver="route('tipos-estudio.listar')" volver-texto="Tipos de estudio" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-tipo-estudio">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-tipo-estudio" class="text-lg font-bold text-ink">Datos del tipo de estudio</h3>
                <x-insignia-activo :activo="$tipoEstudio->estado === 'activo'" class="self-start" />
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm">
                <x-dato etiqueta="Nombre">{{ $tipoEstudio->nombre }}</x-dato>
                <x-dato etiqueta="Descripción" class="!border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $tipoEstudio->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('tipos-estudio.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a tipos de estudio
            </a>

            {{-- Dar de baja / habilitar desde la ficha --}}
            <x-catalogos.baja-habilitar :registro="$tipoEstudio" ruta="tipos-estudio" etiqueta="el tipo de estudio" class="w-full sm:w-auto" boton="w-full sm:w-auto" />
        </div>
    </x-pagina>
</x-app-layout>
