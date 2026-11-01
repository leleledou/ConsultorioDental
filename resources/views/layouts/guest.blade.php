<!DOCTYPE html>
<html lang="es">
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
    <body class="font-sans text-ink antialiased">
        <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
            {{-- Panel de marca (solo en pantallas grandes) --}}
            <aside class="relative hidden overflow-hidden bg-brand-900 lg:flex lg:flex-col lg:justify-between lg:p-14" aria-hidden="true">
                <svg class="pointer-events-none absolute -right-24 -top-24 h-[34rem] w-[34rem] text-brand-500/20" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <circle cx="100" cy="100" r="95" /><circle cx="100" cy="100" r="72" /><circle cx="100" cy="100" r="49" /><circle cx="100" cy="100" r="26" />
                </svg>
                <svg class="pointer-events-none absolute -bottom-40 -left-32 h-[30rem] w-[30rem] text-white/5" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1.2">
                    <circle cx="100" cy="100" r="95" /><circle cx="100" cy="100" r="72" /><circle cx="100" cy="100" r="49" />
                </svg>

                <div class="relative">
                    <span class="inline-block h-1 w-12 rounded-full bg-brand-500"></span>
                </div>

                <div class="relative max-w-md">
                    <h2 class="font-display text-4xl font-extrabold leading-tight text-white">
                        Su consultorio,<br>en orden y a la mano.
                    </h2>
                    <p class="mt-5 text-base leading-relaxed text-brand-100">
                        Sistema de información para la gestión de pacientes, tratamientos, citas y pagos del consultorio {{ config('app.name', 'DentalRox') }}.
                    </p>
                </div>

                <p class="relative text-sm text-brand-200">{{ config('app.name', 'DentalRox') }}</p>
            </aside>

            {{-- Formulario (fondo blanco: el logo oficial tiene fondo blanco) --}}
            <main class="flex flex-col items-center justify-center bg-white px-6 py-10 sm:px-10">
                <div class="w-full max-w-sm">
                    <a href="{{ route('login') }}" class="block" aria-label="{{ config('app.name', 'DentalRox') }}">
                        <img src="{{ asset('images/dentalrox-logo.png') }}" alt="{{ config('app.name', 'DentalRox') }}"
                             width="1983" height="793" class="mx-auto -my-8 h-auto w-80 max-w-full [filter:brightness(1.012)]">
                    </a>

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </main>
        </div>
    </body>
</html>
