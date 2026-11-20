{{-- Acción de un registro de la bitácora: el texto es exactamente el que guarda el backend (en monoespaciado); solo el color
     de la insignia depende del verbo con que empieza. Uso: <x-bitacora.accion :accion="$registro->accion" /> --}}
@props(['accion'])

@php
    $tono = match (true) {
        str_starts_with($accion, 'LOGIN_FALLIDO'), str_starts_with($accion, 'INTENTO_LOGIN'), str_starts_with($accion, 'LOGIN_RECHAZADO'),
        $accion === 'CUENTA_BLOQUEADA', str_starts_with($accion, 'BAJA_'), str_starts_with($accion, 'DESACTIVAR_'),
        str_starts_with($accion, 'INHABILITAR_'), str_starts_with($accion, 'ELIMINAR_'), str_starts_with($accion, 'QUITAR_'),
        str_starts_with($accion, 'ANULAR_'), str_starts_with($accion, 'CANCELAR_') => 'error',
        str_starts_with($accion, 'CREAR_'), str_starts_with($accion, 'REGISTRAR_'), str_starts_with($accion, 'PROGRAMAR_'),
        str_starts_with($accion, 'ASIGNAR_'), str_starts_with($accion, 'ACTIVAR_'), str_starts_with($accion, 'HABILITAR_'),
        $accion === 'INICIO_SESION_EXITOSO' => 'exito',
        str_starts_with($accion, 'MODIFICAR_'), str_starts_with($accion, 'CAMBIO_'), str_starts_with($accion, 'REPROGRAMAR_'),
        str_starts_with($accion, 'RESTABLECER_') => 'marca',
        default => 'neutro',
    };
@endphp

<x-insignia :tono="$tono" {{ $attributes->merge(['class' => 'font-mono !text-[11px] !whitespace-normal break-all text-left']) }}>{{ $accion }}</x-insignia>
