{{-- Insignia de estado genérica: punto de color y texto (nunca solo color). Uso: <x-insignia tono="exito">Concluido</x-insignia>
     Tonos: marca (turquesa), exito (verde), error (rojo), aviso (ámbar), info (azul), neutro (gris). --}}
@props(['tono' => 'neutro'])

@php
    [$clases, $punto] = match ($tono) {
        'marca' => ['bg-brand-50 text-brand-800 ring-brand-200', 'bg-brand-500'],
        'exito' => ['bg-green-50 text-green-800 ring-green-200', 'bg-green-600'],
        'error' => ['bg-red-50 text-red-800 ring-red-200', 'bg-red-600'],
        'aviso' => ['bg-amber-50 text-amber-900 ring-amber-300', 'bg-amber-600'],
        'info' => ['bg-blue-50 text-blue-800 ring-blue-200', 'bg-blue-600'],
        default => ['bg-gray-100 text-gray-700 ring-gray-200', 'bg-gray-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => "badge ring-1 ring-inset $clases"]) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $punto }}" aria-hidden="true"></span>{{ $slot }}
</span>
