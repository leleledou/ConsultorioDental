{{-- Color de referencia de un estado de pieza (#RRGGBB): muestra de color + código. Sin color, un guion. --}}
@props(['color' => null])

@if ($color && preg_match('/^#[0-9A-Fa-f]{6}$/', $color))
    <span class="inline-flex items-center gap-2">
        <span class="h-5 w-5 shrink-0 rounded-full border border-line" style="background-color: {{ $color }}" aria-hidden="true"></span>
        <span class="font-medium tabular-nums uppercase text-ink">{{ $color }}</span>
    </span>
@else
    <span class="text-ink-mute">{{ $color ?: '—' }}</span>
@endif
