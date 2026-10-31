@php
    // Opciones del menú según el rol (RN-05), de App\Services\MenuService.
    $paquetes = \App\Services\MenuService::paquetes(Auth::user());

    // Un paquete es el actual si alguna de sus opciones es la página abierta.
    $paqueteActivo = fn ($paquete) => collect($paquete['opciones'])
        ->contains(fn ($opcion) => $opcion['activa'] && request()->routeIs($opcion['activa']));
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'DentalRox') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,600,700,800|figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen" x-data="{ menu: false }" @keydown.escape.window="menu = false">
            <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:left-3 focus:top-3 focus:z-[60] focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-brand-800 focus:shadow-pop">
                Saltar al contenido
            </a>

            @include('layouts.navigation')

            <div class="lg:pl-72">
                @include('layouts.topbar')

                <!-- Encabezado de la página -->
                @isset($header)
                    <header class="page-header mx-auto max-w-7xl px-4 pt-8 sm:px-6 lg:px-8">
                        {{ $header }}
                    </header>
                @endisset

                <!-- Contenido de la página -->
                <main id="contenido">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
