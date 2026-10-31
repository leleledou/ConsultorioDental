{{-- CU34 Registrar abono (RF-75, RF-76). --}}
@php
    // Formato boliviano de los montos: Bs 1.250,00.
    $bs = fn ($monto) => 'Bs '.number_format((float) $monto, 2, ',', '.');

    $claseCampo = 'mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Registrar abono: {{ $tratamiento->plan->paciente->nombreCompleto() }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            {{-- Errores de negocio (el tratamiento dejó de admitir abonos) --}}
            @error('operacion')
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm font-medium text-red-800 dark:text-red-200">
                    {{ $message }}
                </div>
            @enderror

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Datos del tratamiento</h3>

                <dl class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-6 gap-y-4 text-sm">
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Paciente</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->plan->paciente->nombreCompleto() }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Tratamiento</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $tratamiento->tratamiento->nombre }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Costo acordado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($tratamiento->costo_acordado) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Total abonado</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($abonado) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Saldo pendiente</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $bs($saldo) }}</dd>
                    </div>
                </dl>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <form method="POST" action="{{ route('pagos.guardar') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="tratamiento_aplicado_id" value="{{ $tratamiento->id }}">

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <x-input-label for="monto" value="Monto" />
                            <x-text-input id="monto" name="monto" type="number" min="0.01" max="99999999.99" step="0.01" class="mt-1 block w-full" :value="old('monto')" required />
                            <x-input-error :messages="$errors->get('monto')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fecha_pago" value="Fecha de pago" />
                            <x-text-input id="fecha_pago" name="fecha_pago" type="date" class="mt-1 block w-full" :value="old('fecha_pago', today()->toDateString())" required />
                            <x-input-error :messages="$errors->get('fecha_pago')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="metodo_pago_id" value="Método de pago" />
                            <select id="metodo_pago_id" name="metodo_pago_id" class="{{ $claseCampo }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($metodos as $metodo)
                                    <option value="{{ $metodo->id }}" @selected(old('metodo_pago_id') == $metodo->id)>{{ $metodo->nombre }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('metodo_pago_id')" class="mt-2" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="observaciones" value="Observaciones (opcional)" />
                            <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo }}">{{ old('observaciones') }}</textarea>
                            <x-input-error :messages="$errors->get('observaciones')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>Registrar abono</x-primary-button>
                        <a href="{{ route('pagos.cuenta', $tratamiento->plan->paciente_id) }}" class="text-sm text-gray-600 dark:text-gray-400 underline">Volver al estado de cuenta</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
