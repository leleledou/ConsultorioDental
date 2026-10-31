{{-- Estado activo / inactivo de un registro (icono y texto, no solo color). --}}
@props(['activo'])

@if ($activo)
    <span {{ $attributes->merge(['class' => 'badge bg-green-50 text-green-800 ring-1 ring-inset ring-green-200']) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-green-600" aria-hidden="true"></span> Activo
    </span>
@else
    <span {{ $attributes->merge(['class' => 'badge bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200']) }}>
        <span class="h-1.5 w-1.5 rounded-full bg-gray-400" aria-hidden="true"></span> Inactivo
    </span>
@endif
