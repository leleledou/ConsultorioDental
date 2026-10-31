{{-- Cabecera de página para el slot "header" del layout: enlace de regreso opcional, título y descripción.
     Uso: <x-slot name="header"><x-cabecera-pagina titulo="Pacientes" subtitulo="…" :volver="route('…')" volver-texto="Agenda" /></x-slot>
     El slot por defecto admite acciones o información extra bajo el título. --}}
@props(['titulo', 'subtitulo' => null, 'volver' => null, 'volverTexto' => 'Volver'])

@if ($volver)
    <a href="{{ $volver }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline">
        <x-icon name="arrow-left" class="h-4 w-4" /> {{ $volverTexto }}
    </a>
@endif
<h2>{{ $titulo }}</h2>
@if ($subtitulo)
    <p class="mt-1 text-sm text-ink-soft">{{ $subtitulo }}</p>
@endif
{{ $slot }}
