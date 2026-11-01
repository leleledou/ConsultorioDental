{{-- CU21 Consultar antecedente: detalle del antecedente del catálogo (sin estado). --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Antecedente: '.$antecedente->nombre" subtitulo="Datos del antecedente médico del catálogo."
                           :volver="route('antecedentes.listar')" volver-texto="Antecedentes" />
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

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-antecedente">
            <h3 id="datos-antecedente" class="text-lg font-bold text-ink">Datos del antecedente</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm">
                <x-dato etiqueta="Nombre">{{ $antecedente->nombre }}</x-dato>
                <x-dato etiqueta="Descripción" class="!border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $antecedente->descripcion ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('antecedentes.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a antecedentes
            </a>

            {{-- Eliminar desde la ficha (el id viaja oculto; solo si ninguna historia lo usa) --}}
            <form method="POST" action="{{ route('antecedentes.eliminar') }}"
                  onsubmit="return confirm({{ Js::from('¿Eliminar el antecedente '.$antecedente->nombre.'?') }});">
                @csrf
                <input type="hidden" name="id" value="{{ $antecedente->id }}">
                <x-danger-button :suave="true" class="w-full sm:w-auto"><x-icon name="ban" class="h-4 w-4" /> Eliminar</x-danger-button>
            </form>
        </div>
    </x-pagina>
</x-app-layout>
