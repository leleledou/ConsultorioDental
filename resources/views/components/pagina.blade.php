{{-- Contenedor del contenido de una página: ancho máximo, margen lateral responsive y separación vertical.
     Uso: <x-pagina> …secciones… </x-pagina>. Con ancho="4xl" (u otro max-w de Tailwind) se estrecha, p. ej. en formularios. --}}
@props(['ancho' => '7xl'])

<div class="py-8">
    <div {{ $attributes->merge(['class' => 'mx-auto min-w-0 space-y-6 px-4 sm:px-6 lg:px-8 '.($ancho === '7xl' ? 'max-w-7xl' : "max-w-$ancho")]) }}>
        {{ $slot }}
    </div>
</div>
