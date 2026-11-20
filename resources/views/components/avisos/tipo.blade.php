{{-- Tipo de aviso interno (CU41), con un tono por tipo y el texto literal del enum.
     Uso: <x-avisos.tipo :tipo="$aviso->tipo" /> --}}
@props(['tipo'])

@php
    $tono = match ($tipo) {
        \App\Models\Aviso::CITA_PROXIMA => 'marca',
        \App\Models\Aviso::CONTROL_PENDIENTE => 'info',
        \App\Models\Aviso::SALDO_PENDIENTE => 'aviso',
        \App\Models\Aviso::ESTUDIO_PENDIENTE => 'error',
        default => 'neutro',
    };
@endphp

<x-insignia :tono="$tono" {{ $attributes }}>{{ ucfirst($tipo) }}</x-insignia>
