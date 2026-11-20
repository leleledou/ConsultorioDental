{{-- CU41 Consultar avisos (RF-82): avisos del usuario, no leídos primero; y disparo manual de CU40 Generar avisos internos. --}}
@php
    $sinLeer = (int) $contadores->sum('sin_leer');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina titulo="Avisos"
                           subtitulo="Citas próximas, controles, saldos, estudios y sesiones que requieren su atención." />
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

        {{-- Contadores por tipo (enlazan al filtro) --}}
        <nav class="flex flex-wrap gap-2" aria-label="Filtrar avisos por tipo">
            <a href="{{ route('avisos.listar') }}" @class(['btn btn-sm', 'btn-primary' => ! $tipo, 'btn-secondary' => $tipo]) @if (! $tipo) aria-current="page" @endif>
                Todos <span class="tabular-nums">({{ $sinLeer }} sin leer)</span>
            </a>
            @foreach (\App\Models\Aviso::TIPOS as $t)
                @php $c = $contadores->get($t); @endphp
                <a href="{{ route('avisos.listar', ['tipo' => $t]) }}" @class(['btn btn-sm', 'btn-primary' => $tipo === $t, 'btn-secondary' => $tipo !== $t]) @if ($tipo === $t) aria-current="page" @endif>
                    {{ ucfirst($t) }}
                    <span @class(['inline-flex min-w-6 items-center justify-center rounded-full px-1.5 text-xs font-bold tabular-nums', 'bg-red-600 text-white' => ($c->sin_leer ?? 0) > 0, 'bg-surface text-ink-soft' => ($c->sin_leer ?? 0) == 0])>{{ (int) ($c->sin_leer ?? 0) }}</span>
                    <span class="sr-only">sin leer de {{ (int) ($c->total ?? 0) }}</span>
                </a>
            @endforeach
        </nav>

        {{-- CU40, flujo alternativo: el Temporizador los genera cada día a las 00:15 --}}
        <section class="dr-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5" aria-labelledby="generar-avisos">
            <div class="min-w-0">
                <h3 id="generar-avisos" class="font-bold text-ink">Generar avisos</h3>
                <p class="mt-0.5 text-sm text-ink-soft">El sistema los genera solo todos los días a las 00:15. Con este botón se generan ahora; no se repite un aviso que todavía no leyó.</p>
            </div>
            <form method="POST" action="{{ route('avisos.generar') }}" class="shrink-0">
                @csrf
                <x-secondary-button type="submit" class="w-full sm:w-auto"><x-icon name="refresh" class="h-4 w-4" /> Generar avisos ahora</x-secondary-button>
            </form>
        </section>

        <section class="dr-card overflow-hidden" aria-labelledby="lista-avisos">
            <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <h3 id="lista-avisos" class="text-lg font-bold text-ink">
                    {{ $tipo ? ucfirst($tipo) : 'Todos los avisos' }} <span class="ms-1 text-sm font-semibold text-ink-soft">({{ $avisos->total() }})</span>
                </h3>
                @if ($sinLeer > 0)
                    <form method="POST" action="{{ route('avisos.leer') }}">
                        @csrf
                        <input type="hidden" name="todos" value="1">
                        <x-secondary-button type="submit" class="btn-sm w-full sm:w-auto"><x-icon name="check-circle" class="h-4 w-4" /> Marcar todos como leídos</x-secondary-button>
                    </form>
                @endif
            </header>

            @if ($avisos->isEmpty())
                <div class="px-6 py-12 text-center">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                        <x-icon name="check-circle" class="h-7 w-7" />
                    </span>
                    <p class="mt-4 text-base font-semibold text-ink">{{ $tipo ? 'No tiene avisos de este tipo.' : 'No tiene avisos pendientes.' }}</p>
                </div>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($avisos as $aviso)
                        <li @class([
                            'relative flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6',
                            'bg-brand-50/60' => ! $aviso->leido,
                        ])>
                            @unless ($aviso->leido)
                                {{-- Marca lateral de no leído --}}
                                <span class="absolute inset-y-0 left-0 w-1 bg-brand-500" aria-hidden="true"></span>
                            @endunless

                            <div class="min-w-0 space-y-1.5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-avisos.tipo :tipo="$aviso->tipo" />
                                    @if ($aviso->leido)
                                        <span class="text-xs font-semibold text-ink-mute">Leído</span>
                                    @else
                                        <x-insignia tono="error">Sin leer</x-insignia>
                                    @endif
                                </div>
                                <p @class(['break-words', 'font-semibold text-ink' => ! $aviso->leido, 'text-ink-soft' => $aviso->leido])>{{ $aviso->mensaje }}</p>
                                <p class="text-xs tabular-nums text-ink-soft">Generado el {{ $aviso->fecha_generacion->format('d/m/Y H:i') }}</p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2">
                                @isset($enlaces[$aviso->id])
                                    <a href="{{ $enlaces[$aviso->id] }}" class="btn btn-secondary btn-sm">Ver <x-icon name="arrow" class="h-4 w-4" /></a>
                                @endisset
                                @unless ($aviso->leido)
                                    {{-- Marcar como leído (el id viaja oculto) --}}
                                    <form method="POST" action="{{ route('avisos.leer') }}">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $aviso->id }}">
                                        <x-secondary-button type="submit" class="btn-sm"><x-icon name="check-circle" class="h-4 w-4" /> Marcar como leído</x-secondary-button>
                                    </form>
                                @endunless
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($avisos->hasPages())
                    <div class="border-t border-line px-5 py-4 sm:px-6">{{ $avisos->links() }}</div>
                @endif
            @endif
        </section>
    </x-pagina>
</x-app-layout>
