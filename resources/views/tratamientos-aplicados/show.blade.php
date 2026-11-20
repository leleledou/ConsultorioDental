{{-- CU29 Actualizar estado del tratamiento aplicado (RF-40). --}}
@php
    $confirmar = session('confirmar');
    $planActivo = $tratamiento->plan->estado === 'activo';
    $porcentaje = $tratamiento->sesiones_planificadas > 0 ? min(100, (int) round($realizadas / $tratamiento->sesiones_planificadas * 100)) : 0;
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Tratamiento aplicado: '.$tratamiento->tratamiento->nombre"
                           :subtitulo="'Paciente: '.$tratamiento->plan->paciente->nombreCompleto()"
                           :volver="route('planes.mostrar', $tratamiento->plan_tratamiento_id)" volver-texto="Plan de tratamiento" />
    </x-slot>

    <x-pagina x-data="{ estado: {{ Js::from(old('estado', '')) }}, confirmando: {{ Js::from((bool) $confirmar) }} }">
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

        {{-- Flujo A2: concluir con sesiones pendientes --}}
        @if ($confirmar)
            <x-alerta tipo="aviso" x-show="confirmando">
                <p>El tratamiento tiene {{ $confirmar }} {{ $confirmar > 1 ? 'sesiones pendientes' : 'sesión pendiente' }}. ¿Desea concluirlo igualmente?</p>

                <form method="POST" action="{{ route('tratamientos-aplicados.estado') }}" class="mt-3 flex flex-col gap-2 sm:flex-row">
                    @csrf
                    <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                    <input type="hidden" name="estado" value="concluido">
                    <input type="hidden" name="confirmado" value="1">
                    <x-primary-button class="btn-sm">Confirmar</x-primary-button>
                    <x-secondary-button class="btn-sm" @click="confirmando = false">Cancelar</x-secondary-button>
                </form>
            </x-alerta>
        @endif

        {{-- Resumen --}}
        <section class="dr-card flex flex-col gap-4 p-5 sm:p-6" aria-label="Resumen del tratamiento">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <p class="break-words font-display text-xl font-bold text-ink">{{ $tratamiento->tratamiento->nombre }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">Iniciado el {{ $tratamiento->fecha_inicio->format('d/m/Y') }}</p>
                </div>
                <x-estado-clinico :estado="$tratamiento->estado" class="self-start" />
            </div>

            <div class="grid grid-cols-1 gap-4 border-t border-line pt-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Costo acordado</p>
                    <p class="mt-1 font-display text-2xl font-bold text-ink"><x-moneda :monto="$tratamiento->costo_acordado" /></p>
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Avance</p>
                    <p class="mt-1 font-medium text-ink">Sesión {{ $realizadas }} de {{ $tratamiento->sesiones_planificadas }}</p>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-brand-100" aria-hidden="true"><div class="h-full rounded-full bg-brand-600" style="width: {{ $porcentaje }}%"></div></div>
                </div>
            </div>
        </section>

        {{-- Datos del tratamiento --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-tratamiento">
            <h3 id="datos-tratamiento" class="text-lg font-bold text-ink">Datos clínicos</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2 lg:grid-cols-3">
                <x-dato etiqueta="Paciente">
                    <a href="{{ route('pacientes.mostrar', $tratamiento->plan->paciente_id) }}" class="text-brand-700 hover:text-brand-900 hover:underline">{{ $tratamiento->plan->paciente->nombreCompleto() }}</a>
                </x-dato>
                <x-dato etiqueta="Pieza dental">{{ $tratamiento->piezaDental ? $tratamiento->piezaDental->codigo_fdi.' - '.$tratamiento->piezaDental->nombre : '—' }}</x-dato>
                <x-dato etiqueta="Diagnóstico">{{ $tratamiento->diagnostico->nombre }}</x-dato>
                <x-dato etiqueta="Fecha de inicio">{{ $tratamiento->fecha_inicio->format('d/m/Y') }}</x-dato>
                <x-dato etiqueta="Fecha de fin">{{ $tratamiento->fecha_fin?->format('d/m/Y') ?? '—' }}</x-dato>
                <x-dato etiqueta="Observación diagnóstica" class="sm:col-span-2 lg:col-span-3">
                    <span class="whitespace-pre-line">{{ $tratamiento->observacion_diagnostica ?? '—' }}</span>
                </x-dato>
                <x-dato etiqueta="Observaciones" class="sm:col-span-2 lg:col-span-3 !border-b-0 !pb-0">
                    <span class="whitespace-pre-line">{{ $tratamiento->observaciones ?? '—' }}</span>
                </x-dato>
            </dl>
        </section>

        {{-- Cambio de estado: solo los destinos permitidos (el id viaja oculto) --}}
        @if (! $planActivo)
            <x-alerta tipo="aviso">El plan no está activo.</x-alerta>
        @elseif ($destinos !== [])
            <section class="dr-card p-5 sm:p-6" aria-labelledby="cambiar-estado">
                <h3 id="cambiar-estado" class="text-lg font-bold text-ink">Cambiar estado</h3>

                <form method="POST" action="{{ route('tratamientos-aplicados.estado') }}" class="mt-5 space-y-5">
                    @csrf
                    <input type="hidden" name="id" value="{{ $tratamiento->id }}">
                    <input type="hidden" name="confirmado" value="0">

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="estado" label="Nuevo estado" :requerido="true" :errores="$errors->get('estado')">
                            <select id="estado" name="estado" class="dr-input @error('estado') dr-input-error @enderror" x-model="estado" required>
                                <option value="">Seleccione…</option>
                                @foreach ($destinos as $destino)
                                    <option value="{{ $destino }}">{{ ucfirst($destino) }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        {{-- El motivo solo se pide al cancelar --}}
                        <x-campo name="motivo" label="Motivo" :requerido="true" :errores="$errors->get('motivo')" x-show="estado === 'cancelado'" x-cloak>
                            <x-text-input id="motivo" name="motivo" type="text" :value="old('motivo')" maxlength="255" x-bind:required="estado === 'cancelado'" :error="$errors->has('motivo')" />
                        </x-campo>
                    </div>

                    <x-primary-button class="w-full sm:w-auto">Actualizar estado</x-primary-button>
                </form>
            </section>
        @endif

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('planes.mostrar', $tratamiento->plan_tratamiento_id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al plan
            </a>
        </div>
    </x-pagina>
</x-app-layout>
