{{-- Formulario de anulación de un abono vigente (CU35). Se usa en la tabla y en la tarjeta del abono. El id viaja oculto; el abono no se borra (RNF-06). --}}
@props(['pago', 'sufijo' => 'x'])

<form method="POST" action="{{ route('pagos.anular') }}" class="flex flex-col gap-2 sm:flex-row sm:items-center"
      onsubmit="return confirm({{ Js::from('¿Anular el abono '.$pago->numero_comprobante.'? El saldo se recalculará.') }});">
    @csrf
    <input type="hidden" name="id" value="{{ $pago->id }}">
    <label class="sr-only" for="motivo-anulacion-{{ $pago->id }}-{{ $sufijo }}">Motivo de la anulación del abono {{ $pago->numero_comprobante }}</label>
    <input id="motivo-anulacion-{{ $pago->id }}-{{ $sufijo }}" name="motivo_anulacion" type="text" maxlength="150" placeholder="Motivo de la anulación" required
           class="dr-input min-w-0 py-1.5 text-[13px] sm:w-56">
    <x-danger-button :suave="true" class="btn-sm shrink-0"><x-icon name="ban" class="h-4 w-4" /> Anular</x-danger-button>
</form>
