{{-- Acciones de una fila de antecedente médico (CU21). Va dentro del x-data de antecedentes/index (usa editar()). --}}
@props(['a'])

<div class="flex flex-wrap items-center gap-2">
    <x-secondary-button class="btn-sm" @click="editar({{ Js::from($a->only(['id', 'nombre', 'descripcion'])) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- Eliminar: solo si ninguna historia clínica lo usa (el id viaja oculto) --}}
    <form method="POST" action="{{ route('antecedentes.eliminar') }}"
          onsubmit="return confirm({{ Js::from('¿Eliminar el antecedente '.$a->nombre.'?') }});">
        @csrf
        <input type="hidden" name="id" value="{{ $a->id }}">
        <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Eliminar</x-danger-button>
    </form>
</div>
