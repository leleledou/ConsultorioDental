{{-- Insignia del estado de una cita, con su color (CU10 a CU13). Uso: <x-estado-cita :nombre="$cita->estado->nombre" /> --}}
@props(['nombre'])

@php
    $colores = [
        'programada' => 'bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200',
        'atendida' => 'bg-green-100 dark:bg-green-900/40 text-green-800 dark:text-green-200',
        'cancelada' => 'bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200',
        'no asistida' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap '.($colores[$nombre] ?? 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300')]) }}>
    {{ ucfirst($nombre) }}
</span>
