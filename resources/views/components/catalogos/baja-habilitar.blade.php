{{-- Dar de baja / habilitar un registro de catálogo con estado (diagnósticos, tipos de estudio, materiales).
     El id viaja oculto; no se borra (RNF-06). $ruta es el prefijo de las rutas (p. ej. "materiales").
     $boton son las clases del botón; las clases del componente van al formulario. --}}
@props(['registro', 'ruta', 'etiqueta', 'boton' => ''])

@if ($registro->estado === 'activo')
    <form method="POST" action="{{ route($ruta.'.eliminar') }}" {{ $attributes }}
          onsubmit="return confirm({{ Js::from('¿Dar de baja '.$etiqueta.' '.$registro->nombre.'?') }});">
        @csrf
        <input type="hidden" name="id" value="{{ $registro->id }}">
        <x-danger-button :suave="true" class="{{ $boton }}"><x-icon name="ban" class="h-4 w-4" /> Dar de baja</x-danger-button>
    </form>
@else
    <form method="POST" action="{{ route($ruta.'.habilitar') }}" {{ $attributes }}>
        @csrf
        <input type="hidden" name="id" value="{{ $registro->id }}">
        <x-secondary-button type="submit" class="{{ $boton }}"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
    </form>
@endif
