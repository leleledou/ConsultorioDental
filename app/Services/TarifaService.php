<?php

namespace App\Services;

use App\Models\Tarifa;
use Carbon\Carbon;

/**
 * Clase de control "CTR TarifaService" del análisis.
 *
 * Reúne las reglas de vigencia de las tarifas: CU16 Gestionar tarifas cierra la
 * vigente al registrar un precio y CU28 consulta el precio de una fecha.
 */
class TarifaService
{
    /**
     * Precio de la tarifa del tratamiento cuyo rango de vigencia contiene la fecha.
     * Si hubiera varias, gana la de inicio más reciente.
     *
     * @return string|null El precio, o null si ninguna tarifa cubre esa fecha.
     */
    public function precioVigente(int $tratamientoId, $fecha): ?string
    {
        return Tarifa::where('tratamiento_id', $tratamientoId)
            ->whereDate('fecha_inicio_vigencia', '<=', $fecha)
            ->where(function ($vigencia) use ($fecha) {
                $vigencia->whereNull('fecha_fin_vigencia')
                    ->orWhereDate('fecha_fin_vigencia', '>=', $fecha);
            })
            ->orderByDesc('fecha_inicio_vigencia')
            ->value('precio');
    }

    /**
     * CU16: cierra la tarifa vigente el día anterior a la fecha de la nueva.
     *
     * Debe llamarse dentro de DB::transaction: bloquea (lockForUpdate) la tarifa vigente
     * hasta el final de la transacción, para que dos registros simultáneos no la cierren
     * los dos.
     *
     * @return string|null El mensaje del flujo A2 (no se cerró nada), o null si se puede continuar.
     */
    public function cerrarVigente(int $tratamientoId, string $fecha): ?string
    {
        $vigente = Tarifa::where('tratamiento_id', $tratamientoId)
            ->where('estado', 'vigente')
            ->lockForUpdate()
            ->first();

        if (! $vigente) {
            return null;
        }

        $inicioNueva = Carbon::parse($fecha);

        if ($inicioNueva->lte($vigente->fecha_inicio_vigencia)) {
            return "La nueva tarifa debe comenzar después del {$vigente->fecha_inicio_vigencia->format('d/m/Y')}";
        }

        $vigente->fecha_fin_vigencia = $inicioNueva->copy()->subDay();
        $vigente->estado = 'cerrada';
        $vigente->save();

        return null;
    }
}
