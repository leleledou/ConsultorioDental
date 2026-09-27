{{-- CU08 Consultar paciente: ficha con la edad calculada (no se guarda). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Paciente: {{ $paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('exito'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm font-medium text-green-800 dark:text-green-200">
                    {{ session('exito') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    <ul class="space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Datos personales --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del paciente</h3>
                    @if ($paciente->estado === 'activo')
                        <span class="inline-flex rounded-full bg-green-100 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-800 dark:text-green-200">Activo</span>
                    @else
                        <span class="inline-flex rounded-full bg-gray-200 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-700 dark:text-gray-300">Inactivo</span>
                    @endif
                </div>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">CI</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->ci }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Nombres</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->nombres }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Apellidos</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de nacimiento</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->fecha_nacimiento->format('d/m/Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Edad</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->edad() }} años</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Sexo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->sexo === 'F' ? 'Femenino' : 'Masculino' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Teléfono</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->telefono ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Correo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->correo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Dirección</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->direccion ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de registro</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->fecha_registro->format('d/m/Y') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500 dark:text-gray-400">Observaciones</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $paciente->observaciones ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Historia clínica (se abre al registrar al paciente) --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Historia clínica</h3>

                @if ($paciente->historiaClinica)
                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">N.º de historia</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->historiaClinica->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Fecha de apertura</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $paciente->historiaClinica->fecha_apertura->format('d/m/Y') }}</dd>
                        </div>
                    </dl>
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        Los antecedentes, el odontograma y los tratamientos se registrarán en el paquete de Atención Clínica.
                    </p>
                @else
                    <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">El paciente no tiene historia clínica.</p>
                @endif
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('pacientes.listar') }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a pacientes
                </a>

                {{-- CU08: inhabilitar / habilitar desde la ficha (el id viaja oculto) --}}
                @if ($paciente->estado === 'activo')
                    <form method="POST" action="{{ route('pacientes.eliminar') }}"
                          onsubmit="return confirm({{ Js::from('¿Inhabilitar al paciente '.$paciente->nombreCompleto().'? Su historia clínica se conserva.') }});">
                        @csrf
                        <input type="hidden" name="id" value="{{ $paciente->id }}">
                        <x-danger-button>Inhabilitar</x-danger-button>
                    </form>
                @else
                    <form method="POST" action="{{ route('pacientes.habilitar') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $paciente->id }}">
                        <x-secondary-button type="submit">Habilitar</x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
