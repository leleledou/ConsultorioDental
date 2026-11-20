{{-- Cómo está registrado el resultado de un estudio: enlace externo, imagen o PDF (punto y texto, no solo color).
     Uso: <x-estudios.formato :ruta="$estudio->ruta_archivo" /> --}}
@props(['ruta'])

@php
    $esEnlace = app(\App\Services\EstudioDiagnosticoService::class)->esEnlace($ruta);
    $extension = $esEnlace ? null : strtolower(pathinfo((string) $ruta, PATHINFO_EXTENSION));

    [$tono, $texto] = match (true) {
        $esEnlace => ['info', 'Enlace'],
        in_array($extension, ['jpg', 'jpeg', 'png'], true) => ['marca', 'Imagen'],
        $extension === 'pdf' => ['neutro', 'PDF'],
        default => ['aviso', 'Sin archivo'],
    };
@endphp

<x-insignia :tono="$tono" {{ $attributes }}>{{ $texto }}</x-insignia>
