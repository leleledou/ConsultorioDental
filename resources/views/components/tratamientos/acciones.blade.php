{{-- Acciones de una fila de tratamiento (CU15, CU16, CU19). Va dentro del x-data de tratamientos/index (usa editar()). --}}
@props(['t'])

<div class="flex flex-wrap items-center gap-2">
    <x-secondary-button class="btn-sm"
        @click="editar({{ Js::from($t->only(['id', 'nombre', 'descripcion', 'especialidad_id', 'duracion_estimada_minutos', 'numero_sesiones_previstas', 'requiere_sesiones'])) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    {{-- CU16 Gestionar tarifas y CU19 Definir estudios requeridos --}}
    <a href="{{ route('tarifas.mostrar', $t->id) }}" class="btn btn-secondary btn-sm">Tarifas</a>
    <a href="{{ route('tratamientos.estudios', $t->id) }}" class="btn btn-secondary btn-sm">Estudios requeridos</a>

    {{-- Dar de baja / habilitar (el id viaja oculto; no se borra, RNF-06) --}}
    @if ($t->estaDisponible())
        <form method="POST" action="{{ route('tratamientos.eliminar') }}"
              onsubmit="return confirm({{ Js::from('¿Dar de baja el tratamiento '.$t->nombre.'? Ya no se ofrecerá al programar citas.') }});">
            @csrf
            <input type="hidden" name="id" value="{{ $t->id }}">
            <x-danger-button :suave="true" class="btn-sm"><x-icon name="ban" class="h-4 w-4" /> Dar de baja</x-danger-button>
        </form>
    @else
        <form method="POST" action="{{ route('tratamientos.habilitar') }}">
            @csrf
            <input type="hidden" name="id" value="{{ $t->id }}">
            <x-secondary-button type="submit" class="btn-sm"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
        </form>
    @endif
</div>
