{{-- Estado del hueso de la valoración para implante: suficiente = verde, regular = amarillo, insuficiente = rojo.
     Uso: <x-valoraciones.hueso :estado="$valoracion->estado_hueso" /> --}}
@props(['estado'])

<x-insignia :tono="['suficiente' => 'exito', 'regular' => 'aviso', 'insuficiente' => 'error'][$estado] ?? 'neutro'"
            {{ $attributes }}>Hueso {{ $estado }}</x-insignia>
