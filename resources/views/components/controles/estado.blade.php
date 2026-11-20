{{-- Estado de un control posterior: pendiente = azul, cumplido = verde, vencido = rojo (punto y texto).
     Uso: <x-controles.estado :estado="$control->estado" /> --}}
@props(['estado'])

<x-insignia :tono="['pendiente' => 'info', 'cumplido' => 'exito', 'vencido' => 'error'][$estado] ?? 'neutro'"
            {{ $attributes }}>{{ ucfirst($estado) }}</x-insignia>
