{{-- CU32 Gestionar estudios diagnósticos: ficha del estudio con su resultado (imagen, PDF o enlace). --}}
@php
    $paciente = $estudio->paciente;
    $esImagen = in_array($extension, ['jpg', 'jpeg', 'png'], true);
    $tieneArchivo = ! $esEnlace && $estudio->ruta_archivo;
    $abrirModificar = $errors->any() && old('formulario') === 'modificar';
    $urlArchivo = $tieneArchivo ? \Illuminate\Support\Facades\Storage::disk(\App\Services\EstudioDiagnosticoService::DISCO)->url($estudio->ruta_archivo) : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Estudio: '.$estudio->tipoEstudio->nombre"
                           :subtitulo="'Paciente: '.$paciente->nombreCompleto()"
                           :volver="route('estudios.listar', $paciente->id)" volver-texto="Estudios del paciente" />
    </x-slot>

    <x-pagina>
        @if (session('exito'))
            <x-alerta tipo="exito">{{ session('exito') }}</x-alerta>
        @endif

        @if ($errors->any())
            <x-alerta tipo="error">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-alerta>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5 lg:items-start">
            {{-- Datos del estudio --}}
            <section class="dr-card p-5 sm:p-6 lg:col-span-3" aria-labelledby="datos-estudio">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <h3 id="datos-estudio" class="text-lg font-bold text-ink">Datos del estudio</h3>
                    <x-estudios.formato :ruta="$estudio->ruta_archivo" class="self-start" />
                </div>

                <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                    <x-dato etiqueta="Tipo de estudio">{{ $estudio->tipoEstudio->nombre }}</x-dato>
                    <x-dato etiqueta="Fecha del estudio"><span class="tabular-nums">{{ $estudio->fecha_estudio->format('d/m/Y') }}</span></x-dato>
                    <x-dato etiqueta="Paciente">
                        <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $paciente->nombreCompleto() }}</a>
                    </x-dato>
                    <x-dato etiqueta="Pieza dental">{{ $estudio->pieza ? $estudio->pieza->codigo_fdi.' · '.$estudio->pieza->nombre : '—' }}</x-dato>
                    <x-dato etiqueta="Tratamiento aplicado">
                        @if ($estudio->tratamientoAplicado)
                            <a href="{{ route('tratamientos-aplicados.mostrar', $estudio->tratamiento_aplicado_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $estudio->tratamientoAplicado->tratamiento->nombre }}</a>
                        @else
                            —
                        @endif
                    </x-dato>
                    <x-dato etiqueta="Centro radiológico">{{ $estudio->centro_radiologico ?? '—' }}</x-dato>
                    <x-dato etiqueta="Registrado por">{{ $estudio->odontologo->nombres }} {{ $estudio->odontologo->apellidos }}</x-dato>
                    <x-dato etiqueta="Fecha de registro"><span class="tabular-nums">{{ $estudio->fecha_registro->format('d/m/Y') }}</span></x-dato>
                    <x-dato etiqueta="Hallazgos" class="sm:col-span-2 !border-b-0 !pb-0">
                        <span class="whitespace-pre-line">{{ $estudio->hallazgos ?? '—' }}</span>
                    </x-dato>
                </dl>
            </section>

            {{-- Resultado: vista previa, PDF o enlace externo --}}
            <section class="dr-card overflow-hidden lg:col-span-2" aria-labelledby="resultado-estudio">
                <header class="border-b border-line bg-surface/60 px-5 py-4 sm:px-6">
                    <h3 id="resultado-estudio" class="text-lg font-bold text-ink">Resultado</h3>
                </header>

                <div class="space-y-4 p-5 sm:p-6">
                    @if ($esEnlace)
                        <p class="text-sm text-ink-soft">El resultado está en el sistema del centro radiológico.</p>
                        <p class="break-all rounded-lg bg-surface px-3 py-2 font-mono text-xs text-ink ring-1 ring-line">{{ $estudio->ruta_archivo }}</p>
                        <a href="{{ $estudio->ruta_archivo }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary w-full sm:w-auto">
                            Abrir enlace externo <x-icon name="arrow" class="h-4 w-4" />
                        </a>
                    @elseif ($esImagen)
                        <a href="{{ $urlArchivo }}" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl ring-1 ring-line">
                            <img src="{{ $urlArchivo }}" alt="Imagen del estudio {{ $estudio->tipoEstudio->nombre }} del {{ $estudio->fecha_estudio->format('d/m/Y') }}"
                                 class="h-auto max-h-96 w-full bg-surface object-contain" loading="lazy">
                        </a>
                        <a href="{{ route('estudios.descargar', $estudio->id) }}" class="btn btn-secondary w-full sm:w-auto"><x-icon name="download" class="h-4 w-4" /> Descargar imagen</a>
                    @elseif ($tieneArchivo)
                        <div class="flex items-center gap-3 rounded-xl bg-surface p-4 ring-1 ring-line">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-red-50 text-xs font-bold text-red-800 ring-1 ring-red-200">PDF</span>
                            <p class="min-w-0 text-sm text-ink-soft">Documento PDF del estudio.</p>
                        </div>
                        <a href="{{ route('estudios.descargar', $estudio->id) }}" class="btn btn-primary w-full sm:w-auto"><x-icon name="download" class="h-4 w-4" /> Descargar PDF</a>
                    @else
                        <p class="text-sm text-ink-soft">El estudio no tiene archivo ni enlace registrado.</p>
                    @endif
                </div>
            </section>
        </div>

        {{-- Acciones --}}
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
            <a href="{{ route('estudios.listar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver a los estudios
            </a>
            <x-secondary-button x-data @click="$dispatch('open-modal', 'modificar-estudio')"><x-icon name="edit" class="h-4 w-4" /> Modificar</x-secondary-button>

            @if ($estudio->valoraciones_count > 0)
                <p class="text-sm text-ink-soft">No se puede eliminar: respalda {{ $estudio->valoraciones_count === 1 ? 'una valoración' : $estudio->valoraciones_count.' valoraciones' }} para implante.</p>
            @else
                {{-- Eliminar (el id viaja oculto); borra también el archivo --}}
                <form method="POST" action="{{ route('estudios.eliminar') }}"
                      onsubmit="return confirm({{ Js::from('¿Eliminar el estudio '.$estudio->tipoEstudio->nombre.' del '.$estudio->fecha_estudio->format('d/m/Y').'? También se borrará su archivo.') }});">
                    @csrf
                    <input type="hidden" name="id" value="{{ $estudio->id }}">
                    <x-danger-button :suave="true" class="w-full sm:w-auto"><x-icon name="ban" class="h-4 w-4" /> Eliminar</x-danger-button>
                </form>
            @endif
        </div>

        {{-- Modificar estudio --}}
        <x-modal name="modificar-estudio" :show="$abrirModificar" focusable>
            <div class="p-6">
                <header class="mb-5 flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700"><x-icon name="edit" class="h-5 w-5" /></span>
                    <div>
                        <h2 class="text-lg font-bold text-ink">Modificar estudio</h2>
                        <p class="mt-0.5 text-sm text-ink-soft">Si sube un archivo nuevo o registra un enlace, el anterior se reemplaza.</p>
                    </div>
                </header>

                <x-estudios.formulario :paciente="$paciente" :tipos="$tipos" :piezas="$piezas" :tratamientos="$tratamientos" :estudio="$estudio" :es-enlace="$esEnlace" />
            </div>
        </x-modal>
    </x-pagina>
</x-app-layout>
