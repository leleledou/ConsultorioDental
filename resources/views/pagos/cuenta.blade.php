{{-- CU36 Consultar estado de cuenta. Desde aquí se registra (CU34) y se anula (CU35) un abono. --}}
@php
    // Formato boliviano de los montos: Bs 1.250,00.
    $bs = fn ($monto) => 'Bs '.number_format((float) $monto, 2, ',', '.');

    $claseEnlace = 'inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Estado de cuenta: {{ $paciente->nombreCompleto() }}
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

            @forelse ($tratamientos as $t)
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg space-y-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $t->tratamiento->nombre }}</h3>
                            <a href="{{ route('planes.mostrar', $t->plan_tratamiento_id) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                                Plan del {{ $t->plan->fecha_creacion->format('d/m/Y') }}{{ $t->plan->descripcion ? ': '.$t->plan->descripcion : '' }}
                            </a>
                        </div>

                        @if ($saldos[$t->id] > 0 && $t->plan->estado !== 'cancelado')
                            <a href="{{ route('pagos.crear', ['tratamiento_aplicado_id' => $t->id]) }}" class="{{ $claseEnlace }}">Registrar abono</a>
                        @endif
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Estado</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ ucfirst($t->estado) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Costo acordado</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($t->costo_acordado) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Total abonado</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($abonados[$t->id]) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Saldo pendiente</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($saldos[$t->id]) }}</dd>
                        </div>
                    </dl>

                    {{-- Detalle de abonos del tratamiento --}}
                    @if ($t->pagos->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        <th class="px-3 py-2">Comprobante</th>
                                        <th class="px-3 py-2">Fecha de pago</th>
                                        <th class="px-3 py-2">Método</th>
                                        <th class="px-3 py-2">Monto</th>
                                        <th class="px-3 py-2">Estado</th>
                                        <th class="px-3 py-2">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($t->pagos as $pago)
                                        <tr class="align-top">
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $pago->numero_comprobante ?? '—' }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                            <td class="px-3 py-3">{{ $pago->metodoPago->nombre }}</td>
                                            <td class="px-3 py-3 whitespace-nowrap">{{ $bs($pago->monto) }}</td>
                                            <td class="px-3 py-3">
                                                {{ ucfirst($pago->estado) }}
                                                @if ($pago->estado === 'anulado')
                                                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $pago->motivo_anulacion }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                {{-- CU35 Anular abono (el id viaja oculto; no se borra, RNF-06) --}}
                                                @if ($pago->estado === 'vigente')
                                                    <form method="POST" action="{{ route('pagos.anular') }}" class="flex flex-wrap items-center gap-2"
                                                          onsubmit="return confirm({{ Js::from('¿Anular el abono '.$pago->numero_comprobante.'? El saldo se recalculará.') }});">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $pago->id }}">
                                                        <x-text-input name="motivo_anulacion" type="text" class="block" maxlength="150" placeholder="Motivo" required />
                                                        <x-danger-button>Anular</x-danger-button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            @empty
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg text-center text-sm text-gray-500 dark:text-gray-400">
                    El paciente no tiene tratamientos con costo registrado
                </div>
            @endforelse

            {{-- Totales del paciente --}}
            @if ($tratamientos->isNotEmpty())
                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Totales del paciente</h3>

                    <dl class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Costos acordados</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($totalCostos) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Total abonado</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($totalAbonado) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Saldo total</dt>
                            <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($saldoTotal) }}</dd>
                        </div>
                    </dl>
                </div>
            @endif

            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="{{ $claseEnlace }}">Volver al paciente</a>
            </div>
        </div>
    </div>
</x-app-layout>
