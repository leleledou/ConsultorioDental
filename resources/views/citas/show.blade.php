{{-- Ficha de una cita (CU10). Desde aquí se reprograma, cancela (CU11) o cambia el estado (CU12). --}}
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Cita N.º {{ $cita->id }}
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

            {{-- Datos de la cita --}}
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="flex items-start justify-between gap-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos de la cita</h3>
                    <x-estado-cita :nombre="$cita->estado->nombre" />
                </div>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium">
                            <a href="{{ route('pacientes.mostrar', $cita->paciente->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                                {{ $cita->paciente->nombreCompleto() }}
                            </a>
                            <span class="text-gray-500 dark:text-gray-400">· CI {{ $cita->paciente->ci }}</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Odontólogo responsable</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->odontologo->nombres }} {{ $cita->odontologo->apellidos }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($cita->fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Horario</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->horaInicioCorta() }} – {{ $cita->horaFinCorta() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Duración</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->duracion_minutos }} minutos</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Fecha de registro</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->fecha_registro->format('d/m/Y') }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Motivo</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $cita->motivo ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <dt class="text-gray-500 dark:text-gray-400">Observaciones</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100 whitespace-pre-line">{{ $cita->observaciones ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('agenda', ['fecha' => $cita->fecha->toDateString()]) }}" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700">
                    Volver a la agenda
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
