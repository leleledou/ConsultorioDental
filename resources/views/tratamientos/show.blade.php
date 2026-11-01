{{-- CU15 Consultar tratamiento: detalle del tratamiento del catálogo. --}}
@php $disponible = $tratamiento->estaDisponible(); @endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Tratamiento: '.$tratamiento->nombre" subtitulo="Datos del tratamiento del catálogo."
                           :volver="route('tratamientos.listar')" volver-texto="Tratamientos" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-tratamiento">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <h3 id="datos-tratamiento" class="text-lg font-bold text-ink">Datos del tratamiento</h3>
                <x-insignia :tono="$disponible ? 'exito' : 'neutro'" class="self-start">{{ $disponible ? 'Disponible' : 'No disponible' }}</x-insignia>
            </div>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Nombre">{{ $tratamiento->nombre }}</x-dato>
                <x-dato etiqueta="Especialidad">{{ $tratamiento->especialidad->nombre }}</x-dato>
                <x-dato etiqueta="Duración estimada"><span class="tabular-nums">{{ $tratamiento->duracion_estimada_minutos }}</span> minutos</x-dato>
                <x-dato etiqueta="Sesiones previstas"><span class="tabular-nums">{{ $tratamiento->numero_sesiones_previstas }}</span></x-dato>
                <x-dato etiqueta="Requiere varias sesiones">{{ $tratamiento->requiere_sesiones ? 'Sí' : 'No' }}</x-dato>
                <x-dato etiqueta="Estado"><x-insignia :tono="$disponible ? 'exito' : 'neutro'">{{ $disponible ? 'Disponible' : 'No disponible' }}</x-insignia></x-dato>
                <x-dato etiqueta="Descripción" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $tratamiento->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('tratamientos.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a tratamientos
            </a>

            {{-- Dar de baja / habilitar desde la ficha (el id viaja oculto) --}}
            @if ($disponible)
                <form method="POST" action="{{ route('tratamientos.eliminar') }}"
                      onsubmit="return confirm({{ Js::from('¿Dar de baja el tratamiento '.$tratamiento->nombre.'?') }});">
                    @csrf
                    <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                    <x-danger-button :suave="true" class="w-full sm:w-auto"><x-icon name="ban" class="h-4 w-4" /> Dar de baja</x-danger-button>
                </form>
            @else
                <form method="POST" action="{{ route('tratamientos.habilitar') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                    <x-secondary-button type="submit" class="w-full sm:w-auto"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
                </form>
            @endif
        </div>
    </x-pagina>
</x-app-layout>
