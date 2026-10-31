{{-- Cita dibujada en la agenda diaria (enlace a su ficha). El color del borde y del fondo sigue el estado, igual que <x-estado-cita>.
     Uso: <x-agenda.cita :cita="$c" style="grid-column: 2;" /> --}}
@props(['cita'])

@php
    $colores = [
        'programada' => 'border-brand-500 bg-brand-50',
        'atendida' => 'border-green-600 bg-green-50',
        'cancelada' => 'border-red-500 bg-red-50 opacity-80',
        'no asistida' => 'border-amber-500 bg-amber-50',
    ];
    $cancelada = $cita->estado->nombre === 'cancelada';
@endphp

<a href="{{ route('citas.mostrar', $cita->id) }}"
   {{ $attributes->merge(['class' => 'block min-w-0 rounded-lg border-l-4 px-2.5 py-1.5 text-xs shadow-sm transition hover:shadow-card hover:ring-2 hover:ring-brand-300 sm:text-sm '.($colores[$cita->estado->nombre] ?? 'border-gray-400 bg-gray-50')]) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-2 gap-y-1">
        <span class="font-bold tabular-nums text-ink">{{ $cita->horaInicioCorta() }}–{{ $cita->horaFinCorta() }}</span>
        <x-estado-cita :nombre="$cita->estado->nombre" />
    </div>
    <div @class(['mt-0.5 truncate font-semibold text-ink', 'line-through' => $cancelada])>{{ $cita->paciente->nombreCompleto() }}</div>
    <div class="truncate text-ink-soft">{{ $cita->motivo ?? '—' }} · {{ $cita->odontologo->nombres }}</div>
</a>
