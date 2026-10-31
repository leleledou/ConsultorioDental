{{-- CU08 Consultar paciente: ficha con la edad calculada (no se guarda). --}}
@php
    $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
    $activo = $paciente->estado === 'activo';
@endphp

<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('pacientes.listar') }}" class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-brand-700 hover:text-brand-900 hover:underline">
            <x-icon name="arrow-left" class="h-4 w-4" /> Pacientes
        </a>
        <h2>Paciente: {{ $paciente->nombreCompleto() }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto min-w-0 max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
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

            {{-- Resumen --}}
            <section class="dr-card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6" aria-label="Resumen del paciente">
                <div class="flex min-w-0 items-center gap-4">
                    <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                    <div class="min-w-0">
                        <p class="truncate font-display text-xl font-bold text-ink">{{ $paciente->nombreCompleto() }}</p>
                        <p class="mt-0.5 text-sm text-ink-soft">CI {{ $paciente->ci }} · {{ $paciente->edad() }} años</p>
                    </div>
                </div>
                <x-insignia-activo :activo="$activo" class="self-start sm:self-center" />
            </section>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                {{-- Datos personales --}}
                <section class="dr-card p-5 sm:p-6 lg:col-span-2" aria-labelledby="datos-paciente">
                    <h3 id="datos-paciente" class="text-lg font-bold text-ink">Datos del paciente</h3>

                    <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                        @foreach ([
                            'CI' => $paciente->ci,
                            'Nombres' => $paciente->nombres,
                            'Apellidos' => $paciente->apellidos,
                            'Fecha de nacimiento' => $paciente->fecha_nacimiento->format('d/m/Y'),
                            'Edad' => $paciente->edad().' años',
                            'Sexo' => $paciente->sexo === 'F' ? 'Femenino' : 'Masculino',
                            'Teléfono' => $paciente->telefono ?? '—',
                            'Correo' => $paciente->correo ?? '—',
                            'Dirección' => $paciente->direccion ?? '—',
                            'Fecha de registro' => $paciente->fecha_registro->format('d/m/Y'),
                        ] as $etiqueta => $valor)
                            <div class="min-w-0 border-b border-line pb-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">{{ $etiqueta }}</dt>
                                <dd class="mt-1 break-words font-medium text-ink">{{ $valor }}</dd>
                            </div>
                        @endforeach
                        <div class="min-w-0 sm:col-span-2">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Observaciones</dt>
                            <dd class="mt-1 whitespace-pre-line break-words font-medium text-ink">{{ $paciente->observaciones ?? '—' }}</dd>
                        </div>
                    </dl>
                </section>

                {{-- Historia clínica (se abre al registrar al paciente) --}}
                <section class="dr-card h-fit p-5 sm:p-6" aria-labelledby="historia-clinica">
                    <h3 id="historia-clinica" class="text-lg font-bold text-ink">Historia clínica</h3>

                    @if ($paciente->historiaClinica)
                        <dl class="mt-5 space-y-4 text-sm">
                            <div class="border-b border-line pb-3">
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">N.º de historia</dt>
                                <dd class="mt-1 font-medium text-ink">{{ $paciente->historiaClinica->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Fecha de apertura</dt>
                                <dd class="mt-1 font-medium text-ink">{{ $paciente->historiaClinica->fecha_apertura->format('d/m/Y') }}</dd>
                            </div>
                        </dl>
                        <p class="mt-5 rounded-lg bg-brand-50 p-3 text-sm text-brand-900">
                            Los antecedentes y demás datos clínicos se consultan desde el enlace «Historia clínica».
                        </p>
                    @else
                        <p class="mt-4 text-sm text-ink-soft">El paciente no tiene historia clínica.</p>
                    @endif
                </section>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('pacientes.listar') }}" class="btn btn-secondary">
                    <x-icon name="arrow-left" class="h-4 w-4" /> Volver a pacientes
                </a>

                {{-- CU23 Gestionar historia clínica y CU27 Gestionar plan de tratamiento --}}
                <a href="{{ route('historias.mostrar', $paciente->id) }}" class="btn btn-secondary">
                    Historia clínica
                </a>
                <a href="{{ route('planes.listar', ['paciente_id' => $paciente->id]) }}" class="btn btn-secondary">
                    Planes de tratamiento
                </a>
                {{-- CU36 Consultar estado de cuenta --}}
                <a href="{{ route('pagos.cuenta', $paciente->id) }}" class="btn btn-secondary">
                    Estado de cuenta
                </a>

                {{-- CU08: inhabilitar / habilitar desde la ficha (el id viaja oculto) --}}
                @if ($activo)
                    <form method="POST" action="{{ route('pacientes.eliminar') }}"
                          onsubmit="return confirm({{ Js::from('¿Inhabilitar al paciente '.$paciente->nombreCompleto().'? Su historia clínica se conserva.') }});">
                        @csrf
                        <input type="hidden" name="id" value="{{ $paciente->id }}">
                        <x-danger-button :suave="true"><x-icon name="ban" class="h-4 w-4" /> Inhabilitar</x-danger-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('pacientes.habilitar') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $paciente->id }}">
                        <x-secondary-button type="submit"><x-icon name="check-circle" class="h-4 w-4" /> Habilitar</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
