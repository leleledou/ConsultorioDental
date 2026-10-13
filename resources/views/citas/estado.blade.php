{{-- CU12 Actualizar estado de cita (RF-71): citas de un día con el cambio de estado. --}}
@php
    $claseCampo = 'block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm text-sm';
    $claseEnlace = 'inline-flex items-center px-3 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
    $esFutura = $fecha->gt(today());
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Citas del día — {{ ucfirst($fecha->locale('es')->isoFormat('dddd DD/MM/YYYY')) }}
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100 space-y-4">
                    {{-- Selector de fecha --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('citas.listar', ['fecha' => $fecha->copy()->subDay()->toDateString()]) }}" class="{{ $claseEnlace }}" aria-label="Día anterior">&larr;</a>
                            <form method="GET" action="{{ route('citas.listar') }}" class="flex gap-2">
                                <x-text-input name="fecha" type="date" :value="$fecha->toDateString()" onchange="this.form.submit()" />
                                <noscript><x-primary-button>Ver</x-primary-button></noscript>
                            </form>
                            <a href="{{ route('citas.listar', ['fecha' => $fecha->copy()->addDay()->toDateString()]) }}" class="{{ $claseEnlace }}" aria-label="Día siguiente">&rarr;</a>
                            <a href="{{ route('citas.listar') }}" class="{{ $claseEnlace }}">Hoy</a>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('agenda', ['fecha' => $fecha->toDateString()]) }}" class="{{ $claseEnlace }}">Ver en la agenda</a>
                            <a href="{{ route('citas.crear', ['fecha' => $fecha->toDateString()]) }}" class="inline-flex items-center px-3 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white">
                                Programar cita
                            </a>
                        </div>
                    </div>

                    @if ($esFutura)
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Es un día futuro: sus citas solo se pueden cancelar. «Atendida» y «No asistida» se marcan el mismo día o después.
                        </p>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="px-3 py-2">Hora</th>
                                    <th class="px-3 py-2">Paciente</th>
                                    <th class="px-3 py-2">Odontólogo</th>
                                    <th class="px-3 py-2">Motivo</th>
                                    <th class="px-3 py-2">Estado</th>
                                    <th class="px-3 py-2">Nuevo estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($citas as $c)
                                    <tr class="align-top">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <a href="{{ route('citas.mostrar', $c->id) }}" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                                {{ $c->horaInicioCorta() }} – {{ $c->horaFinCorta() }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-3">{{ $c->paciente->apellidos }}, {{ $c->paciente->nombres }}</td>
                                        <td class="px-3 py-3">{{ $c->odontologo->nombres }} {{ $c->odontologo->apellidos }}</td>
                                        <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ $c->motivo ?? '—' }}</td>
                                        <td class="px-3 py-3"><x-estado-cita :nombre="$c->estado->nombre" /></td>
                                        <td class="px-3 py-3">
                                            @if ($c->estaProgramada())
                                                {{-- El id viaja oculto; el controlador valida el estado destino --}}
                                                <form method="POST" action="{{ route('citas.estado') }}" class="flex flex-col sm:flex-row gap-2 min-w-[16rem]">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $c->id }}">
                                                    <select name="estado_cita_id" class="{{ $claseCampo }}" required aria-label="Nuevo estado">
                                                        <option value="">Elegir…</option>
                                                        @foreach ($estadosDestino as $e)
                                                            {{-- Atendida / no asistida: solo hoy o días anteriores --}}
                                                            <option value="{{ $e->id }}" @disabled($esFutura && $e->nombre !== 'cancelada')>{{ ucfirst($e->nombre) }}</option>
                                                        @endforeach
                                                    </select>
                                                    <x-text-input name="observacion" type="text" class="block w-full text-sm" maxlength="255" placeholder="Observación (opcional)" />
                                                    <x-primary-button>Guardar</x-primary-button>
                                                </form>
                                            @else
                                                <span class="text-gray-400 dark:text-gray-500">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            No hay citas registradas para este día.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
