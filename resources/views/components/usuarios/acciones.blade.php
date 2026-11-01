{{-- Acciones de una fila de usuario (CU04, CU05, CU06). Va dentro del x-data de usuarios/index (usa editar()). --}}
@props(['u', 'sufijo' => ''])

<div class="flex flex-wrap items-center gap-2">
    {{-- CU04: modificar (carga los datos en el formulario) --}}
    <x-secondary-button class="btn-sm"
        @click="editar({{ Js::from($u->only(['id', 'nombres', 'apellidos', 'ci', 'matricula_profesional', 'especialidad_id', 'correo', 'telefono', 'usuario'])) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- CU04 paso 8: inhabilitar / habilitar (el id viaja oculto) --}}
    @if ($u->estado === 'activo')
        <form method="POST" action="{{ route('usuarios.eliminar') }}"
              onsubmit="return confirm({{ Js::from('¿Inhabilitar al usuario '.$u->usuario.'? Sus registros se conservan.') }});">
            @csrf
            <input type="hidden" name="id" value="{{ $u->id }}">
            <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Inhabilitar</x-danger-button>
        </form>
    @else
        <form method="POST" action="{{ route('usuarios.habilitar') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $u->id }}">
            <x-secondary-button type="submit" class="btn-sm"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
        </form>
    @endif

    {{-- CU06: restablecer acceso --}}
    <form method="POST" action="{{ route('usuarios.restablecer') }}"
          onsubmit="return confirm({{ Js::from('¿Restablecer el acceso de '.$u->usuario.'? Se generará una contraseña temporal y se cerrarán sus sesiones.') }});">
        @csrf
        <input type="hidden" name="id" value="{{ $u->id }}">
        <x-secondary-button type="submit" class="btn-sm"><x-icon name="key" class="h-4 w-4" /> Restablecer acceso</x-secondary-button>
    </form>

    {{-- CU05: asignar rol --}}
    <form method="POST" action="{{ route('usuarios.asignarRol') }}" class="flex items-center gap-1.5">
        @csrf
        <input type="hidden" name="id" value="{{ $u->id }}">
        <label for="rol-{{ $u->id }}{{ $sufijo }}" class="sr-only">Rol de {{ $u->usuario }}</label>
        <select id="rol-{{ $u->id }}{{ $sufijo }}" name="rol" class="dr-input !w-auto py-1.5 text-[13px]">
            <option value="administrador" @selected($u->rol === 'administrador')>Administrador</option>
            <option value="odontologo" @selected($u->rol === 'odontologo')>Odontólogo</option>
        </select>
        <x-secondary-button type="submit" class="btn-sm">Asignar rol</x-secondary-button>
    </form>
</div>
