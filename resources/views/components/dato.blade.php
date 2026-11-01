{{-- Dato de una ficha: etiqueta en mayúsculas y valor. Va dentro de un <dl>. Uso: <x-dato etiqueta="Estado">…</x-dato> --}}
@props(['etiqueta'])

<div {{ $attributes->merge(['class' => 'min-w-0 border-b border-line pb-3']) }}>
    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">{{ $etiqueta }}</dt>
    <dd class="mt-1 break-words font-medium text-ink">{{ $slot }}</dd>
</div>
