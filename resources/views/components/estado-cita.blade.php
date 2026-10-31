{{-- Insignia del estado de una cita, con su color y su texto (CU10 a CU13). Uso: <x-estado-cita :nombre="$cita->estado->nombre" /> --}}
@props(['nombre'])

@php
    // Programada = turquesa de marca; atendida = verde; cancelada = rojo; no asistida = ámbar.
    [$clases, $punto] = match ($nombre) {
        'programada' => ['bg-brand-50 text-brand-800 ring-brand-200', 'bg-brand-500'],
        'atendida' => ['bg-green-50 text-green-800 ring-green-200', 'bg-green-600'],
        'cancelada' => ['bg-red-50 text-red-800 ring-red-200', 'bg-red-600'],
        'no asistida' => ['bg-amber-50 text-amber-900 ring-amber-300', 'bg-amber-600'],
        default => ['bg-gray-100 text-gray-700 ring-gray-200', 'bg-gray-400'],
    };
@endphp

<span {{ $attributes->merge(['class' => "badge ring-1 ring-inset $clases"]) }}>
    <span class="h-1.5 w-1.5 rounded-full {{ $punto }}" aria-hidden="true"></span>{{ ucfirst($nombre) }}
</span>
