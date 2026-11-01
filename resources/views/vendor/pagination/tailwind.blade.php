{{-- Paginación de DentalRox (sustituye la vista por defecto de Laravel; la usan todos los listados con ->links()). --}}
@if ($paginator->hasPages())
    @php
        $base = 'inline-flex h-9 min-w-[2.25rem] items-center justify-center rounded-lg border px-3 text-sm font-semibold transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500';
    @endphp

    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-sm text-ink-soft">
            Mostrando <span class="font-semibold text-ink">{{ $paginator->firstItem() }}</span>
            a <span class="font-semibold text-ink">{{ $paginator->lastItem() }}</span>
            de <span class="font-semibold text-ink">{{ $paginator->total() }}</span> resultados
        </p>

        <div class="flex items-center gap-1.5">
            {{-- Anterior --}}
            @if ($paginator->onFirstPage())
                <span class="{{ $base }} cursor-not-allowed border-line bg-surface text-ink-mute" aria-disabled="true">
                    <x-icon name="chevron-left" class="h-4 w-4" /><span class="ms-1 hidden sm:inline">Anterior</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $base }} border-gray-300 bg-white text-ink hover:border-brand-300 hover:bg-brand-50">
                    <x-icon name="chevron-left" class="h-4 w-4" /><span class="ms-1 hidden sm:inline">Anterior</span>
                </a>
            @endif

            {{-- Números de página (en móvil solo el indicador de la página actual) --}}
            <span class="px-2 text-sm font-semibold text-ink sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

            <div class="hidden items-center gap-1.5 sm:flex">
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="px-1 text-ink-soft" aria-hidden="true">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="{{ $base }} border-brand-700 bg-brand-700 text-white">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="{{ $base }} border-gray-300 bg-white text-ink hover:border-brand-300 hover:bg-brand-50" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Siguiente --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $base }} border-gray-300 bg-white text-ink hover:border-brand-300 hover:bg-brand-50">
                    <span class="me-1 hidden sm:inline">Siguiente</span><x-icon name="chevron-right" class="h-4 w-4" />
                </a>
            @else
                <span class="{{ $base }} cursor-not-allowed border-line bg-surface text-ink-mute" aria-disabled="true">
                    <span class="me-1 hidden sm:inline">Siguiente</span><x-icon name="chevron-right" class="h-4 w-4" />
                </span>
            @endif
        </div>
    </nav>
@endif
