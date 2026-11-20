{{-- Acciones de una fila de paciente (CU08). Va dentro del x-data de pacientes/index (usa editar()). --}}
@props(['p'])

<div class="flex flex-wrap items-center gap-2">
    {{-- CU08: modificar (carga los datos en el formulario) --}}
    <x-secondary-button class="btn-sm"
        @click="editar({{ Js::from($p->only(['id', 'ci', 'nombres', 'apellidos', 'sexo', 'telefono', 'correo', 'direccion', 'observaciones']) + ['fecha_nacimiento' => $p->fecha_nacimiento->toDateString()]) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- CU08: inhabilitar / habilitar (el id viaja oculto) --}}
    @if ($p->estado === 'activo')
        <form method="POST" action="{{ route('pacientes.eliminar') }}"
              onsubmit="return confirm({{ Js::from('¿Inhabilitar al paciente '.$p->nombreCompleto().'? Su historia clínica se conserva.') }});">
            @csrf
            <input type="hidden" name="id" value="{{ $p->id }}">
            <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Inhabilitar</x-danger-button>
        </form>
    @else
        <form method="POST" action="{{ route('pacientes.habilitar') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $p->id }}">
            <x-secondary-button type="submit" class="btn-sm"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
        </form>
    @endif
</div>
