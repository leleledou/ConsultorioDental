{{-- Estado activo / inactivo de un registro (punto y texto, no solo color). --}}
@props(['activo'])

<x-insignia :tono="$activo ? 'exito' : 'neutro'" {{ $attributes }}>{{ $activo ? 'Activo' : 'Inactivo' }}</x-insignia>
