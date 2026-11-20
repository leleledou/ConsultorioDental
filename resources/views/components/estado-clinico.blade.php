{{-- Insignia del estado de un plan de tratamiento o de un tratamiento aplicado (Atención Clínica).
     Uso: <x-estado-clinico :estado="$plan->estado" /> --}}
@props(['estado'])

{{-- Activo y en proceso = turquesa de marca; planificado = azul; concluido = verde; cancelado = rojo. --}}
<x-insignia :tono="match ($estado) { 'activo', 'en proceso' => 'marca', 'planificado' => 'info', 'concluido' => 'exito', 'cancelado' => 'error', default => 'neutro' }"
            {{ $attributes }}>{{ ucfirst($estado) }}</x-insignia>
