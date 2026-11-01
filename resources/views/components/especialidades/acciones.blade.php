{{-- Acciones de una fila de especialidad (CU14). Va dentro del x-data de especialidades/index (usa editar()). --}}
@props(['e'])

<div class="flex flex-wrap items-center gap-2">
    <x-secondary-button class="btn-sm" @click="editar({{ Js::from($e->only(['id', 'nombre', 'descripcion'])) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- Eliminar: el id viaja oculto; el controlador verifica que nadie la use --}}
    <form method="POST" action="{{ route('especialidades.eliminar') }}"
          onsubmit="return confirm({{ Js::from('¿Eliminar la especialidad '.$e->nombre.'?') }});">
        @csrf
        <input type="hidden" name="id" value="{{ $e->id }}">
        <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Eliminar</x-danger-button>
    </form>
</div>
