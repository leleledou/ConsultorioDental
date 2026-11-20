{{-- Página de error de DentalRox (403, 404, 419, 500 y los demás códigos). Documento HTML completo e independiente del layout
     de la aplicación: no depende de la base de datos y nunca muestra el mensaje de la excepción ni datos internos. --}}
@props(['codigo', 'titulo', 'mensaje'])

@php
    // Una página de error no puede fallar por consultar la sesión o las rutas.
    try { $conSesion = auth()->check(); } catch (\Throwable) { $conSesion = false; }
    try { $destino = $conSesion ? route('panel') : route('login'); } catch (\Throwable) { $destino = url('/'); }
@endphp
<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">
        <title>{{ $titulo }} · {{ config('app.name', 'DentalRox') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:500,600,700,800|figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @php try { echo app(\Illuminate\Foundation\Vite::class)(['resources/css/app.css']); } catch (\Throwable) {} @endphp
    </head>
    <body class="font-sans antialiased">
        <main class="flex min-h-screen items-center justify-center px-4 py-10">
            <section class="dr-card w-full max-w-md p-8 text-center sm:p-10" aria-labelledby="error-titulo">
                <img src="{{ asset('images/dentalrox-logo.png') }}" alt="DentalRox" width="800" height="320" class="mx-auto h-12 w-auto" style="filter: brightness(1.012)">

                <p class="mt-8 font-display text-6xl font-extrabold leading-none text-brand-700" aria-hidden="true">{{ $codigo }}</p>
                <h1 id="error-titulo" class="mt-4 font-display text-2xl font-bold text-ink">{{ $titulo }}</h1>
                <p class="mt-2 text-base text-ink-soft">{{ $mensaje }}</p>
                <p class="sr-only">Código de error {{ $codigo }}.</p>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
                    <a href="{{ $destino }}" class="btn btn-primary min-h-11">{{ $conSesion ? 'Ir al panel' : 'Ir al inicio de sesión' }}</a>
                    <button type="button" class="btn btn-secondary min-h-11" onclick="history.length > 1 ? history.back() : (location.href = {{ Js::from($destino) }})">Volver</button>
                </div>
            </section>
        </main>
    </body>
</html>
