<?php

namespace App\Services;

use App\Models\PlanTratamiento;
use App\Models\TratamientoAplicado;

/**
 * Clase de control "CTR PlanTratamientoService" del análisis.
 *
 * Mantiene el costo total del plan, que comparten CU27 Gestionar plan de tratamiento,
 * CU28 Registrar tratamiento aplicado y CU29 Actualizar estado del tratamiento aplicado.
 */
class PlanTratamientoService
{
    /**
     * El costo total del plan es la suma de los costos acordados de sus tratamientos
     * no cancelados. Debe llamarse dentro de la transacción del que lo usa.
     */
    public function recalcularCosto(PlanTratamiento $plan): void
    {
        $total = TratamientoAplicado::where('plan_tratamiento_id', $plan->id)
            ->where('estado', '!=', 'cancelado')
            ->sum('costo_acordado');

        $plan->costo_total = $total;
        $plan->save();
    }
}
