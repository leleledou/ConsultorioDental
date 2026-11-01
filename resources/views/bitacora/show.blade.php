{{-- CU07 paso 5: detalle de un registro de la bitácora (solo lectura, RN-02). --}}
<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Registro de bitácora #'.$registro->id"
                           subtitulo="Detalle de una acción registrada. Solo se puede consultar."
                           :volver="route('bitacora.listar')" volver-texto="Bitácora" />
    </x-slot>

    <x-pagina>
        {{-- Resumen --}}
        <section class="dr-card flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Resumen del registro">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Acción</p>
                <div class="mt-1.5"><x-bitacora.accion :accion="$registro->accion" class="!text-xs" /></div>
            </div>
            <div class="sm:text-right">
                <p class="font-display text-xl font-bold tabular-nums text-ink">{{ $registro->fecha_hora->format('d/m/Y') }}</p>
                <p class="text-sm tabular-nums text-ink-soft">{{ $registro->fecha_hora->format('H:i:s') }}</p>
            </div>
        </section>

        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-registro">
            <h3 id="datos-registro" class="text-lg font-bold text-ink">Datos del registro</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Usuario">
                    @if ($registro->usuario)
                        {{ $registro->usuario->nombres }} {{ $registro->usuario->apellidos }} ({{ $registro->usuario->usuario }})
                    @else
                        —
                    @endif
                </x-dato>
                <x-dato etiqueta="Usuario digitado en el login">{{ $registro->usuario_digitado ?? '—' }}</x-dato>
                <x-dato etiqueta="Acción"><span class="font-mono">{{ $registro->accion }}</span></x-dato>
                <x-dato etiqueta="Tabla afectada">{{ $registro->tabla_afectada ?? '—' }}</x-dato>
                <x-dato etiqueta="Registro afectado"><span class="tabular-nums">{{ $registro->registro_id ?? '—' }}</span></x-dato>
                <x-dato etiqueta="IP"><span class="tabular-nums">{{ $registro->ip }}</span></x-dato>
                <x-dato etiqueta="Fecha">{{ $registro->fecha_hora->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Hora"><span class="tabular-nums">{{ $registro->fecha_hora->format('H:i:s') }}</span></x-dato>
            </dl>
        </section>

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('bitacora.listar') }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a la bitácora
            </a>
        </div>
    </x-pagina>
</x-app-layout>
