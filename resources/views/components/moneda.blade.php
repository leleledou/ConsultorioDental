{{-- Importe en bolivianos, completo y sin saltos de línea: Bs 1.900,00. Uso: <x-moneda :monto="$plan->costo_total" /> --}}
@props(['monto'])

<span {{ $attributes->merge(['class' => 'whitespace-nowrap tabular-nums']) }}>Bs {{ number_format((float) $monto, 2, ',', '.') }}</span>
