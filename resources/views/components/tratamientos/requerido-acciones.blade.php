{{-- Acciones de un estudio requerido (CU19). Va dentro del x-data de tratamientos/estudios (usa editar()). --}}
@props(['r'])

<div class="flex flex-wrap items-center gap-2">
    <x-secondary-button class="btn-sm"
        @click="editar({{ Js::from($r->only(['id', 'tipo_estudio_id', 'obligatorio', 'momento_clinico', 'observacion'])) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- Quitar (el id viaja oculto) --}}
    <form method="POST" action="{{ route('tratamientos.estudios.quitar') }}"
          onsubmit="return confirm({{ Js::from('¿Quitar el estudio requerido '.$r->tipoEstudio->nombre.'?') }});">
        @csrf
        <input type="hidden" name="id" value="{{ $r->id }}">
        <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Quitar</x-danger-button>
    </form>
</div>
