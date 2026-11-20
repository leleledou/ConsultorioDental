{{-- suave: versión discreta para acciones dentro de tablas. --}}
@props(['suave' => false])

<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn '.($suave ? 'btn-danger-soft' : 'btn-danger')]) }}>
    {{ $slot }}
</button>
