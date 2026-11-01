{{-- Valor de una celda o total de un reporte: dinero con <x-moneda>, estados con su insignia y el resto con el texto
     que arma ReporteService::formatear (el mismo de la pantalla y el PDF). Uso: <x-reportes.valor :resultado="$resultado" :clave="$clave" :valor="$fila[$clave]" /> --}}
@props(['resultado', 'clave', 'valor'])

@php
    use App\Services\ReporteService;

    $esMonto = in_array($clave, $resultado['montos'], true);
    $esEstado = $clave === 'estado';
    // Los estados llegan con la inicial en mayúscula en algunos reportes: se comparan en minúsculas.
    $estado = $esEstado && is_string($valor) ? mb_strtolower($valor) : null;
@endphp

@if ($esMonto)
    <x-moneda :monto="$valor" />
@elseif ($esEstado && in_array($estado, ['programada', 'atendida', 'cancelada', 'no asistida'], true))
    <x-estado-cita :nombre="$estado" />
@elseif ($esEstado && in_array($estado, ['activo', 'planificado', 'en proceso', 'concluido', 'cancelado'], true))
    <x-estado-clinico :estado="$estado" />
@elseif ($esEstado && in_array($estado, ['vigente', 'anulado'], true))
    <x-insignia :tono="$estado === 'vigente' ? 'exito' : 'error'">{{ ucfirst($estado) }}</x-insignia>
@else
    {{ ReporteService::formatear($resultado, $clave, $valor) }}
@endif
