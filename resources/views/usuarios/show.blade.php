{{-- CU04 Consultar usuario y CU06 paso 2: detalle del usuario con el resumen de su acceso. --}}
@php
    $iniciales = mb_strtoupper(mb_substr($usuario->nombres, 0, 1).mb_substr($usuario->apellidos, 0, 1));
    $esAdministrador = $usuario->rol === 'administrador';
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Usuario: '.$usuario->nombres.' '.$usuario->apellidos"
                           subtitulo="Datos personales y estado de acceso."
                           :volver="route('usuarios.listar')" volver-texto="Usuarios" />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        {{-- Resumen --}}
        <section class="dr-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Resumen del usuario">
            <div class="flex min-w-0 items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                <div class="min-w-0">
                    <p class="break-words font-display text-xl font-bold text-ink">{{ $usuario->nombres }} {{ $usuario->apellidos }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">{{ $usuario->usuario }}</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2 self-start sm:self-center">
                <x-insignia :tono="$esAdministrador ? 'marca' : 'info'">{{ $esAdministrador ? 'Administrador' : 'Odontólogo' }}</x-insignia>
                <x-insignia-activo :activo="$usuario->estado === 'activo'" />
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
            {{-- Datos personales --}}
            <section class="dr-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="datos-usuario">
                <h3 id="datos-usuario" class="text-lg font-bold text-ink">Datos del usuario</h3>

                <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                    <x-dato etiqueta="Nombres">{{ $usuario->nombres }}</x-dato>
                    <x-dato etiqueta="Apellidos">{{ $usuario->apellidos }}</x-dato>
                    <x-dato etiqueta="CI"><span class="tabular-nums">{{ $usuario->ci }}</span></x-dato>
                    <x-dato etiqueta="Matrícula profesional">{{ $usuario->matricula_profesional }}</x-dato>
                    <x-dato etiqueta="Especialidad">{{ $usuario->especialidad?->nombre }}</x-dato>
                    <x-dato etiqueta="Teléfono">{{ $usuario->telefono }}</x-dato>
                    <x-dato etiqueta="Correo">{{ $usuario->correo }}</x-dato>
                    <x-dato etiqueta="Usuario">{{ $usuario->usuario }}</x-dato>
                    <x-dato etiqueta="Rol">{{ $esAdministrador ? 'Administrador' : 'Odontólogo' }}</x-dato>
                </dl>
            </section>

            {{-- Resumen del acceso (RN-03 y CU06) --}}
            <section class="dr-card p-5 sm:p-6" aria-labelledby="acceso-usuario">
                <h3 id="acceso-usuario" class="text-lg font-bold text-ink">Acceso</h3>

                <dl class="mt-5 space-y-5 text-sm">
                    <x-dato etiqueta="Estado"><x-insignia-activo :activo="$usuario->estado === 'activo'" /></x-dato>
                    <x-dato etiqueta="Intentos fallidos"><span class="tabular-nums">{{ $usuario->intentos_fallidos }}</span></x-dato>
                    <x-dato etiqueta="Bloqueo vigente">
                        @if ($usuario->estaBloqueado())
                            <x-insignia tono="error" class="!whitespace-normal">
                                Bloqueado hasta {{ $usuario->bloqueado_hasta->format('d/m/Y H:i') }}
                                ({{ $usuario->minutosBloqueoRestantes() }} min)
                            </x-insignia>
                        @else
                            Sin bloqueo
                        @endif
                    </x-dato>
                    <x-dato etiqueta="Debe cambiar la contraseña" class="!border-b-0 !pb-0">{{ $usuario->debe_cambiar_contrasena ? 'Sí' : 'No' }}</x-dato>
                </dl>
            </section>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('usuarios.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a usuarios
            </a>
        </div>
    </x-pagina>
</x-app-layout>
