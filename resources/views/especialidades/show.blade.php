{{-- CU14 Consultar especialidad: detalle con los usuarios que la tienen. --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Especialidad: '.$especialidad->nombre" subtitulo="Datos de la especialidad y usuarios que la tienen."
                           :volver="route('especialidades.listar')" volver-texto="Especialidades" />
    </x-slot>

    <x-pagina>
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-especialidad">
            <h3 id="datos-especialidad" class="text-lg font-bold text-ink">Datos de la especialidad</h3>
            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-3">
                <x-dato etiqueta="Nombre">{{ $especialidad->nombre }}</x-dato>
                <x-dato etiqueta="Descripción" class="sm:col-span-2">{{ $especialidad->descripcion ?? '—' }}</x-dato>
            </dl>
        </section>

        <section class="dr-card overflow-hidden" aria-labelledby="usuarios-especialidad">
            <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                <h3 id="usuarios-especialidad" class="text-lg font-bold text-ink">
                    Usuarios con esta especialidad <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $especialidad->usuarios_count }})</span>
                </h3>
            </header>

            @if ($especialidad->usuarios->isEmpty())
                <p class="px-5 py-8 text-center text-sm text-ink-soft sm:px-6">Ningún usuario tiene esta especialidad.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($especialidad->usuarios as $u)
                        <li class="flex flex-col gap-1.5 px-5 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <a href="{{ route('usuarios.mostrar', $u->id) }}" class="min-w-0 break-words font-semibold text-brand-700 hover:text-brand-900 hover:underline">
                                {{ $u->apellidos }}, {{ $u->nombres }}
                            </a>
                            <span class="flex flex-wrap items-center gap-2 text-sm text-ink-soft">
                                {{ $u->usuario }}
                                <x-insignia-activo :activo="$u->estado === 'activo'" />
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('especialidades.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a especialidades
            </a>
        </div>
    </x-pagina>
</x-app-layout>
