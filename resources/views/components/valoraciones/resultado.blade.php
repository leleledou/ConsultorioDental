{{-- Resultado de la valoración para implante: apto = verde, apto con condiciones = amarillo, no apto = rojo.
     Uso: <x-valoraciones.resultado :resultado="$valoracion->resultado" /> --}}
@props(['resultado'])

<x-insignia :tono="['apto' => 'exito', 'apto con condiciones' => 'aviso', 'no apto' => 'error'][$resultado] ?? 'neutro'"
            {{ $attributes }}>{{ ucfirst($resultado) }}</x-insignia>
