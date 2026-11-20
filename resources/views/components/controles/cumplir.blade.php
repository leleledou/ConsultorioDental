{{-- CU38: marcar un control como cumplido (también un vencido: se atendió tarde). El id viaja oculto.
     Botón que despliega la fecha de cumplimiento (hoy por defecto, no futura) y las observaciones.
     Uso: <x-controles.cumplir :control="$control" /> — no se dibuja si el control ya está cumplido. --}}
@props(['control'])

@if ($control->estado !== 'cumplido')
    <div x-data="{ abierto: false }" {{ $attributes }}>
        <x-secondary-button class="btn-sm" x-show="! abierto" @click="abierto = true">
            <x-icon name="check-circle" class="h-4 w-4" /> Marcar cumplido
        </x-secondary-button>

        <form x-show="abierto" x-cloak method="POST" action="{{ route('controles.cumplir') }}"
              class="mt-1 grid grid-cols-1 gap-3 rounded-xl border border-line bg-surface/60 p-3 sm:grid-cols-[auto_minmax(0,1fr)] sm:items-end">
            @csrf
            <input type="hidden" name="id" value="{{ $control->id }}">
            <div>
                <label for="fecha_cumplimiento_{{ $control->id }}" class="block text-sm font-semibold text-ink">Fecha de cumplimiento</label>
                <input id="fecha_cumplimiento_{{ $control->id }}" type="date" name="fecha_cumplimiento" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="dr-input mt-1.5">
            </div>
            <div>
                <label for="observaciones_{{ $control->id }}" class="block text-sm font-semibold text-ink">Observaciones (opcional)</label>
                <input id="observaciones_{{ $control->id }}" type="text" name="observaciones" value="{{ $control->observaciones }}" class="dr-input mt-1.5">
            </div>
            <div class="flex gap-2 sm:col-span-2">
                <x-primary-button class="btn-sm">Confirmar</x-primary-button>
                <x-secondary-button class="btn-sm" @click="abierto = false">Cancelar</x-secondary-button>
            </div>
        </form>
    </div>
@endif
