{{-- Campo de formulario: etiqueta, control (slot), ayuda y errores.
     Uso: <x-campo name="ci" label="CI" :requerido="true" :errores="$errors->get('ci')"> <x-text-input .../> </x-campo> --}}
@props(['name', 'label', 'requerido' => false, 'ayuda' => null, 'errores' => []])

<div {{ $attributes }}>
    <label for="{{ $name }}" class="block text-sm font-semibold text-ink">
        {{ $label }}@if ($requerido)<span class="ms-0.5 text-red-700" aria-hidden="true">*</span>@endif
    </label>
    <div class="mt-1.5">{{ $slot }}</div>
    @if ($ayuda)
        <p class="mt-1.5 text-xs text-ink-soft">{{ $ayuda }}</p>
    @endif
    @if ($errores)
        <x-input-error :messages="$errores" class="mt-1.5" />
    @endif
</div>
