{{-- Insignia del estado de una cita, con su color y su texto (CU10 a CU13). Uso: <x-estado-cita :nombre="$cita->estado->nombre" /> --}}
@props(['nombre'])

{{-- Programada = turquesa de marca; atendida = verde; cancelada = rojo; no asistida = ámbar. --}}
<x-insignia :tono="match ($nombre) { 'programada' => 'marca', 'atendida' => 'exito', 'cancelada' => 'error', 'no asistida' => 'aviso', default => 'neutro' }"
            {{ $attributes }}>{{ ucfirst($nombre) }}</x-insignia>
