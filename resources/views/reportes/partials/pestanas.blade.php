{{-- Navegación entre los reportes clínicos, de agenda y financieros, y errores de validación de los filtros --}}
@php
    $secciones = [
        ['reportes.clinicos', 'Reportes clínicos'],
        ['reportes.agenda', 'Reportes de agenda'],
        ['reportes.financieros', 'Reportes financieros'],
    ];
@endphp

<nav class="flex flex-wrap gap-2" aria-label="Tipos de reporte">
    @foreach ($secciones as [$ruta, $texto])
        <a href="{{ route($ruta) }}" @class([
            'btn btn-sm',
            'btn-primary' => request()->routeIs($ruta),
            'btn-secondary' => ! request()->routeIs($ruta),
        ]) @if (request()->routeIs($ruta)) aria-current="page" @endif>{{ $texto }}</a>
    @endforeach
</nav>

@if ($errors->any())
    <x-alerta tipo="error">
        <ul class="space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alerta>
@endif
