{{-- error: marca el campo con borde rojo y aria-invalid. --}}
@props(['disabled' => false, 'error' => false])

<input @disabled($disabled) @if ($error) aria-invalid="true" @endif {{ $attributes->class(['dr-input', 'dr-input-error' => $error]) }}>
