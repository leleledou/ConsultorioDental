{{-- Iconos SVG en línea, mismo trazo (1.7) y tamaño base 24. Uso: <x-icon name="home" class="h-5 w-5" /> --}}
@props(['name'])

@php
    $trazos = [
        'home' => 'M3 11.5 12 4l9 7.5M5.5 10v9.5a.5.5 0 0 0 .5.5h4v-5h4v5h4a.5.5 0 0 0 .5-.5V10',
        'P1' => 'M12 3 4.5 6v5.5c0 4.5 3.2 8 7.5 9.5 4.3-1.5 7.5-5 7.5-9.5V6L12 3zM9 12l2 2 4-4',
        'P2' => 'M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01',
        'P3' => 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
        'P4' => 'M9 3h6a1 1 0 0 1 1 1v1H8V4a1 1 0 0 1 1-1zM8 5H6a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V6a1 1 0 0 0-1-1h-2M9 13h6M9 17h4',
        'P5' => 'M4 19V5M4 19h16M8 16v-5M12 16V8M16 16v-3',
        'chevron' => 'M6 9l6 6 6-6',
        'menu' => 'M4 6h16M4 12h16M4 18h16',
        'close' => 'M6 6l12 12M18 6 6 18',
        'arrow' => 'M5 12h14M13 6l6 6-6 6',
        'logout' => 'M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 8l-4 4 4 4M6 12h10',
        'key' => 'M15 7a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM12.5 11 4 19.5V21h2.5v-1.5H8V18h1.5l3-3',
        'user' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4.5 20a7.5 7.5 0 0 1 15 0',
        'search' => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.3-4.3',
        'plus' => 'M12 5v14M5 12h14',
        'edit' => 'M4 20h4L19 9a2.1 2.1 0 0 0-4-4L4 16v4zM13.5 6.5l4 4',
        'ban' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM5.7 5.7l12.6 12.6',
        'check-circle' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM8.5 12.5l2.5 2.5 4.5-5',
        'alert' => 'M12 3 2.5 20h19L12 3zM12 10v4M12 17.5h.01',
        'users' => 'M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM2.5 20a6.5 6.5 0 0 1 13 0M16 4.2a4 4 0 0 1 0 7.6M18 14.2a6.5 6.5 0 0 1 3.5 5.8',
        'chevron-left' => 'M15 6l-6 6 6 6',
        'chevron-right' => 'M9 6l6 6-6 6',
        'arrow-left' => 'M19 12H5M11 6l-6 6 6 6',
        'calendar' => 'M7 3v3M17 3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z',
        'refresh' => 'M20 11a8 8 0 0 0-14.9-3M4 5v4h4M4 13a8 8 0 0 0 14.9 3M20 19v-4h-4',
        'clock' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
        'download' => 'M12 4v11m0 0-4-4m4 4 4-4M5 20h14',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'h-5 w-5']) }} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    <path d="{{ $trazos[$name] ?? '' }}" />
</svg>
