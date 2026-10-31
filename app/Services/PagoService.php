<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\TratamientoAplicado;

/**
 * Clase de control "CTR PagoService" del análisis.
 *
 * Reúne las reglas de los abonos que comparten CU34 Registrar abono, CU35 Anular abono
 * y CU36 Consultar estado de cuenta. Los montos se comparan en centavos enteros, para
 * no arrastrar errores de coma flotante.
 */
class PagoService
{
    /**
     * Saldo = costo acordado − abonos vigentes. Nunca se guarda (RF-75).
     */
    public function saldoPendiente(TratamientoAplicado $tratamiento): float
    {
        return $this->saldoEnCentavos($tratamiento) / 100;
    }

    /**
     * RF-76: el monto no puede superar el saldo pendiente.
     */
    public function validarMonto(TratamientoAplicado $tratamiento, $monto): bool
    {
        return $this->aCentavos($monto) <= $this->saldoEnCentavos($tratamiento);
    }

    /**
     * Siguiente comprobante correlativo: 'REC-' + 4 dígitos (sigue sin recortar desde REC-10000).
     * Toma el mayor número existente, incluidos los anulados: un comprobante nunca se reutiliza.
     *
     * Debe llamarse dentro de DB::transaction: bloquea (lockForUpdate) los comprobantes hasta
     * el final de la transacción, para que dos abonos simultáneos no generen el mismo número.
     * Si MySQL aborta una de las dos por interbloqueo, la transacción se reintenta.
     */
    public function generarComprobante(): string
    {
        $mayor = Pago::where('numero_comprobante', 'like', 'REC-%')
            ->lockForUpdate()
            ->pluck('numero_comprobante')
            ->map(fn ($numero) => (int) substr($numero, 4))
            ->max();

        return 'REC-'.str_pad((string) (($mayor ?? 0) + 1), 4, '0', STR_PAD_LEFT);
    }

    // Costo acordado menos los abonos vigentes, en centavos.
    private function saldoEnCentavos(TratamientoAplicado $tratamiento): int
    {
        $abonado = Pago::where('tratamiento_aplicado_id', $tratamiento->id)
            ->where('estado', 'vigente')
            ->sum('monto');

        return $this->aCentavos($tratamiento->costo_acordado) - $this->aCentavos($abonado);
    }

    // Convierte un monto en bolivianos a centavos enteros.
    private function aCentavos($monto): int
    {
        return (int) round((float) $monto * 100);
    }
}
