{{-- CU36 Consultar estado de cuenta. Desde aquí se registra (CU34) y se anula (CU35) un abono. --}}
@php
    $iniciales = mb_strtoupper(mb_substr($paciente->nombres, 0, 1).mb_substr($paciente->apellidos, 0, 1));
@endphp

<x-app-layout>
    <x-slot name="header">
        <x-cabecera-pagina :titulo="'Estado de cuenta: '.$paciente->nombreCompleto()"
                           subtitulo="Costos acordados, abonos y saldo pendiente de cada tratamiento."
                           :volver="route('pacientes.mostrar', $paciente->id)" volver-texto="Paciente" />
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

        {{-- Paciente y totales (los calcula el controlador) --}}
        <section class="dr-card p-5 sm:p-6" aria-label="Resumen del paciente">
            <div class="flex min-w-0 items-center gap-4">
                <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-brand-700 font-display text-lg font-bold text-white" aria-hidden="true">{{ $iniciales }}</span>
                <div class="min-w-0">
                    <p class="font-display text-xl font-bold text-ink">{{ $paciente->nombreCompleto() }}</p>
                    <p class="mt-0.5 text-sm text-ink-soft">CI {{ $paciente->ci }}</p>
                </div>
            </div>

            @if ($tratamientos->isNotEmpty())
                <div class="mt-5 border-t border-line pt-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-brand-800">Totales del paciente</h3>
                    <dl class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl bg-surface px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Costos acordados</dt>
                            <dd class="mt-1 font-display text-xl font-bold text-ink"><x-moneda :monto="$totalCostos" /></dd>
                        </div>
                        <div class="rounded-xl bg-green-50 px-4 py-3">
                            <dt class="text-xs font-semibold uppercase tracking-wide text-green-800">Total abonado</dt>
                            <dd class="mt-1 font-display text-xl font-bold text-green-900"><x-moneda :monto="$totalAbonado" /></dd>
                        </div>
                        <div @class(['rounded-xl px-4 py-3', 'bg-amber-50' => $saldoTotal > 0, 'bg-surface' => $saldoTotal <= 0])>
                            <dt @class(['text-xs font-semibold uppercase tracking-wide', 'text-amber-900' => $saldoTotal > 0, 'text-ink-soft' => $saldoTotal <= 0])>Saldo total</dt>
                            <dd class="mt-1 font-display text-xl font-bold text-ink"><x-moneda :monto="$saldoTotal" /></dd>
                        </div>
                    </dl>
                </div>
            @endif
        </section>

        @forelse ($tratamientos as $t)
            <section class="dr-card overflow-hidden" aria-labelledby="tratamiento-{{ $t->id }}">
                <header class="flex flex-col gap-3 border-b border-line bg-surface/60 px-5 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                            <h3 id="tratamiento-{{ $t->id }}" class="font-display text-lg font-bold text-ink">{{ $t->tratamiento->nombre }}</h3>
                            <x-estado-clinico :estado="$t->estado" />
                        </div>
                        <a href="{{ route('planes.mostrar', $t->plan_tratamiento_id) }}" class="mt-1 inline-block break-words max-sm:py-2.5 text-sm font-medium text-brand-700 hover:text-brand-900 hover:underline">
                            Plan del {{ $t->plan->fecha_creacion->format('d/m/Y') }}{{ $t->plan->descripcion ? ': '.$t->plan->descripcion : '' }}
                        </a>
                    </div>

                    @if ($saldos[$t->id] > 0 && $t->plan->estado !== 'cancelado')
                        <a href="{{ route('pagos.crear', ['tratamiento_aplicado_id' => $t->id]) }}" class="btn btn-primary btn-sm shrink-0 self-start">
                            <x-icon name="plus" class="h-4 w-4" /> Registrar abono
                        </a>
                    @endif
                </header>

                <dl class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-3 sm:p-5">
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Costo acordado</dt>
                        <dd class="mt-1 text-lg font-bold text-ink"><x-moneda :monto="$t->costo_acordado" /></dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Total abonado</dt>
                        <dd class="mt-1 text-lg font-bold text-green-800"><x-moneda :monto="$abonados[$t->id]" /></dd>
                    </div>
                    <div class="min-w-0">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Saldo pendiente</dt>
                        <dd class="mt-1 flex flex-wrap items-center gap-2 text-lg font-bold text-ink">
                            <x-moneda :monto="$saldos[$t->id]" />
                            @if ($saldos[$t->id] <= 0)
                                <x-insignia tono="exito">Saldado</x-insignia>
                            @endif
                        </dd>
                    </div>
                </dl>

                {{-- Detalle de abonos del tratamiento --}}
                <div class="border-t border-line">
                    <h4 class="px-5 pt-4 text-xs font-bold uppercase tracking-wider text-brand-800 sm:px-6">Abonos</h4>

                    @if ($t->pagos->isEmpty())
                        <p class="px-5 pb-5 pt-2 text-sm text-ink-soft sm:px-6">Este tratamiento todavía no tiene abonos registrados.</p>
                    @else
                        {{-- Tabla (desde 1280 px) --}}
                        <div class="mt-2 hidden xl:block">
                            <table class="dr-table">
                                <caption class="sr-only">Abonos de {{ $t->tratamiento->nombre }}</caption>
                                <thead>
                                    <tr>
                                        <th scope="col">Comprobante</th>
                                        <th scope="col">Fecha de pago</th>
                                        <th scope="col">Método</th>
                                        <th scope="col" class="text-right">Monto</th>
                                        <th scope="col">Estado</th>
                                        <th scope="col">Anulación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($t->pagos as $pago)
                                        <tr class="align-top">
                                            <td class="whitespace-nowrap font-medium tabular-nums text-ink">{{ $pago->numero_comprobante ?? '—' }}</td>
                                            <td class="whitespace-nowrap text-ink-soft">{{ $pago->fecha_pago->format('d/m/Y') }}</td>
                                            <td class="text-ink-soft">{{ $pago->metodoPago->nombre }}</td>
                                            <td @class(['text-right font-semibold', 'text-ink' => $pago->estado !== 'anulado', 'text-ink-mute line-through' => $pago->estado === 'anulado'])><x-moneda :monto="$pago->monto" /></td>
                                            <td>
                                                <x-insignia :tono="$pago->estado === 'vigente' ? 'exito' : 'error'">{{ ucfirst($pago->estado) }}</x-insignia>
                                            </td>
                                            <td>
                                                {{-- CU35 Anular abono (el id viaja oculto; no se borra, RNF-06) --}}
                                                @if ($pago->estado === 'vigente')
                                                    <x-pagos.anular :pago="$pago" sufijo="t" />
                                                @elseif ($pago->estado === 'anulado')
                                                    <p class="max-w-xs break-words text-xs text-ink-soft">{{ $pago->motivo_anulacion }}</p>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Tarjetas (móvil y tablet) --}}
                        <ul class="grid grid-cols-1 gap-3 p-3 sm:p-4 md:grid-cols-2 xl:hidden">
                            @foreach ($t->pagos as $pago)
                                <li @class(['dr-item', 'bg-surface/70' => $pago->estado === 'anulado'])>
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="font-semibold tabular-nums text-ink">{{ $pago->numero_comprobante ?? '—' }}</p>
                                            <p class="text-sm text-ink-soft">{{ $pago->fecha_pago->format('d/m/Y') }} · {{ $pago->metodoPago->nombre }}</p>
                                        </div>
                                        <x-insignia :tono="$pago->estado === 'vigente' ? 'exito' : 'error'">{{ ucfirst($pago->estado) }}</x-insignia>
                                    </div>

                                    <p @class(['font-display text-xl font-bold', 'text-ink' => $pago->estado !== 'anulado', 'text-ink-mute line-through' => $pago->estado === 'anulado'])><x-moneda :monto="$pago->monto" /></p>

                                    @if ($pago->estado === 'vigente')
                                        <x-pagos.anular :pago="$pago" sufijo="m" />
                                    @elseif ($pago->estado === 'anulado')
                                        <p class="break-words text-sm text-ink-soft"><span class="font-semibold text-ink">Motivo de anulación:</span> {{ $pago->motivo_anulacion }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </section>
        @empty
            <section class="dr-card px-6 py-14 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-700">
                    <x-icon name="P5" class="h-7 w-7" />
                </span>
                <p class="mt-4 text-base font-semibold text-ink">El paciente no tiene tratamientos con costo registrado</p>
            </section>
        @endforelse

        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('pacientes.mostrar', $paciente->id) }}" class="btn btn-secondary">
                <x-icon name="arrow-left" class="h-4 w-4" /> Volver al paciente
            </a>
        </div>
    </x-pagina>
</x-app-layout>
