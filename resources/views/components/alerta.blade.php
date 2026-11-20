{{-- Mensaje destacado. Uso: <x-alerta tipo="exito|error|aviso">Texto</x-alerta> --}}
@props(['tipo' => 'exito'])

@php
    [$clases, $icono] = match ($tipo) {
        'error' => ['border-red-200 bg-red-50 text-red-800', 'alert'],
        'aviso' => ['border-yellow-200 bg-yellow-50 text-yellow-800', 'alert'],
        default => ['border-green-200 bg-green-50 text-green-800', 'check-circle'],
    };
@endphp

<div {{ $attributes->merge(['class' => "flex items-start gap-3 rounded-xl border p-4 text-sm font-medium $clases", 'role' => $tipo === 'error' ? 'alert' : 'status']) }}>
    <x-icon :name="$icono" class="mt-0.5 h-5 w-5 shrink-0" />
    <div class="min-w-0">{{ $slot }}</div>
</div>
