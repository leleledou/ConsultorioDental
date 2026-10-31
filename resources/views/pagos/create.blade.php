{{-- CU34 Registrar abono (RF-75, RF-76). --}}
@php
    $paciente = $tratamiento->plan->paciente;

    // Clase de un control, con borde rojo si el campo tiene error.
    $claseCampo = fn (string $campo) => 'dr-input'.($errors->has($campo) ? ' dr-input-error' : '');
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Registrar abono: '.$paciente->nombreCompleto()"
                           subtitulo="El monto del abono no puede superar el saldo pendiente del tratamiento."
                           :volver="route('pagos.cuenta', $tratamiento->plan->paciente_id)" volver-texto="Estado de cuenta" />
    </x-slot>

    <x-pagina ancho="4xl">
        {{-- Errores de negocio (el tratamiento dejó de admitir abonos) --}}
        @error('operacion')
            <x-alerta tipo="error">{{ $message }}</x-alerta>
        @enderror

        {{-- Datos del tratamiento --}}
        <section class="dr-card p-5 sm:p-6" aria-labelledby="datos-tratamiento">
            <h3 id="datos-tratamiento" class="text-lg font-bold text-ink">Datos del tratamiento</h3>

            <dl class="mt-5 grid grid-cols-1 gap-x-6 gap-y-5 text-sm sm:grid-cols-2">
                <x-dato etiqueta="Paciente">{{ $paciente->nombreCompleto() }}</x-dato>
                <x-dato etiqueta="Tratamiento">{{ $tratamiento->tratamiento->nombre }}</x-dato>
            </dl>

            <dl class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-surface px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Costo acordado</dt>
                    <dd class="mt-1 font-display text-xl font-bold text-ink"><x-moneda :monto="$tratamiento->costo_acordado" /></dd>
                </div>
                <div class="rounded-xl bg-green-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-green-800">Total abonado</dt>
                    <dd class="mt-1 font-display text-xl font-bold text-green-900"><x-moneda :monto="$abonado" /></dd>
                </div>
                <div class="rounded-xl bg-amber-50 px-4 py-3">
                    <dt class="text-xs font-semibold uppercase tracking-wide text-amber-900">Saldo pendiente</dt>
                    <dd class="mt-1 font-display text-xl font-bold text-ink"><x-moneda :monto="$saldo" /></dd>
                </div>
            </dl>
        </section>

        {{-- Formulario del abono --}}
        <form method="POST" action="{{ route('pagos.guardar') }}" class="dr-card overflow-hidden">
            @csrf
            <input type="hidden" name="tratamiento_aplicado_id" value="{{ $tratamiento->id }}">

            <div class="space-y-8 p-5 sm:p-6">
                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Importe y fecha</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="monto" label="Monto" :requerido="true" :ayuda="'Saldo pendiente: Bs '.number_format((float) $saldo, 2, ',', '.').'. Hasta 2 decimales.'" :errores="$errors->get('monto')">
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink-soft" aria-hidden="true">Bs</span>
                                <x-text-input id="monto" name="monto" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" class="pl-10 text-lg font-semibold tabular-nums" :value="old('monto')" required :error="$errors->has('monto')" />
                            </div>
                        </x-campo>

                        <x-campo name="fecha_pago" label="Fecha de pago" :requerido="true" :errores="$errors->get('fecha_pago')">
                            <x-text-input id="fecha_pago" name="fecha_pago" type="date" :value="old('fecha_pago', today()->toDateString())" required :error="$errors->has('fecha_pago')" />
                        </x-campo>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="text-xs font-bold uppercase tracking-wider text-brand-800">Método y observaciones</legend>
                    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-campo name="metodo_pago_id" label="Método de pago" :requerido="true" :errores="$errors->get('metodo_pago_id')">
                            <select id="metodo_pago_id" name="metodo_pago_id" class="{{ $claseCampo('metodo_pago_id') }}" required>
                                <option value="">Seleccione…</option>
                                @foreach ($metodos as $metodo)
                                    <option value="{{ $metodo->id }}" @selected(old('metodo_pago_id') == $metodo->id)>{{ $metodo->nombre }}</option>
                                @endforeach
                            </select>
                        </x-campo>

                        <x-campo name="observaciones" label="Observaciones (opcional)" class="sm:col-span-2" :errores="$errors->get('observaciones')">
                            <textarea id="observaciones" name="observaciones" rows="3" class="{{ $claseCampo('observaciones') }}">{{ old('observaciones') }}</textarea>
                        </x-campo>
                    </div>
                </fieldset>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p class="text-xs text-ink-soft"><span class="text-red-700" aria-hidden="true">*</span> Campo obligatorio</p>
                <div class="flex flex-col-reverse gap-3 sm:flex-row">
                    <a href="{{ route('pagos.cuenta', $tratamiento->plan->paciente_id) }}" class="btn btn-secondary">Volver al estado de cuenta</a>
                    <x-primary-button>Registrar abono</x-primary-button>
                </div>
            </div>
        </form>
    </x-pagina>
</x-app-layout>
