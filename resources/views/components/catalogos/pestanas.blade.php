{{-- Pestañas de «Piezas y estados» (CU22): enlaces entre los dos catálogos. $activa = 'estados' | 'piezas'. --}}
@props(['activa'])

@php
    $items = [
        'estados' => ['Estados de pieza', route('estados-pieza.listar')],
        'piezas' => ['Piezas dentales', route('piezas-dentales.listar')],
    ];
@endphp

<nav aria-label="Piezas y estados" class="flex gap-1 border-b border-line">
    @foreach ($items as $clave => [$texto, $url])
        <a href="{{ $url }}" @if ($activa === $clave) aria-current="page" @endif
           class="-mb-px inline-flex min-h-11 items-center border-b-2 px-4 text-sm font-semibold transition-colors {{ $activa === $clave ? 'border-brand-600 text-brand-800' : 'border-transparent text-ink-soft hover:border-brand-200 hover:text-ink' }}">
            {{ $texto }}
        </a>
    @endforeach
</nav>
