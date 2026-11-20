{{-- CU17 Consultar diagnóstico: detalle del diagnóstico del catálogo. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Diagnóstico: '.$diagnostico->nombre" subtitulo="Datos del diagnóstico del catálogo."
                           :volver="route('diagnosticos.listar')" volver-texto="Diagnósticos" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-diagnostico">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-diagnostico" class="text-lg font-bold text-ink">Datos del diagnóstico</h3>
                <x-insignia-activo :activo="$diagnostico->estado === 'activo'" class="self-start" />
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Código">{{ $diagnostico->codigo }}</x-dato>
                <x-dato etiqueta="Nombre" class="lg:col-span-2">{{ $diagnostico->nombre }}</x-dato>
                <x-dato etiqueta="Descripción" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $diagnostico->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('diagnosticos.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a diagnósticos
            </a>

            {{-- Dar de baja / habilitar desde la ficha --}}
            <x-catalogos.baja-habilitar :registro="$diagnostico" ruta="diagnosticos" etiqueta="el diagnóstico" class="w-full sm:w-auto" boton="w-full sm:w-auto" />
        </div>
    </x-pagina>
</x-app-layout>
