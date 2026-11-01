{{-- Acciones de una fila de catálogo con estado: Modificar + Dar de baja / Habilitar.
     Va dentro del x-data del listado (usa editar()). $campos son los atributos que se cargan en el formulario. --}}
@props(['registro', 'ruta', 'etiqueta', 'campos'])

<div class="flex flex-wrap items-center gap-2">
    <x-secondary-button class="btn-sm" @click="editar({{ Js::from($registro->only($campos)) }})">
        <x-icon name="edit" class="h-4 w-4" /> Modificar
    </x-secondary-button>

    <x-catalogos.baja-habilitar :registro="$registro" :ruta="$ruta" :etiqueta="$etiqueta" boton="btn-sm" />
</div>
