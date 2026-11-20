<?php

namespace App\Services;

use App\Models\Sesion;
use App\Models\TratamientoAplicado;

/**
 * Clase de control "CTR SesionService" del análisis.
 *
 * Reúne las reglas de numeración y ampliación de sesiones de CU30 Registrar sesión
 * de evolución (RF-43 a RF-50).
 */
class SesionService
{
    /**
     * Número de la próxima sesión: el mayor número registrado + 1, o 1 si no hay sesiones.
     * Cuenta también las anuladas: un número nunca se reutiliza (uq_sesiones_numero).
     */
    public function numeroSiguiente(TratamientoAplicado $ta): int
    {
        return (int) Sesion::where('tratamiento_aplicado_id', $ta->id)->max('numero_sesion') + 1;
    }

    /**
     * RF-50: true si la próxima sesión supera las sesiones planificadas.
     */
    public function excedeSesionesPlanificadas(TratamientoAplicado $ta): bool
    {
        return $this->numeroSiguiente($ta) > $ta->sesiones_planificadas;
    }

    /**
     * RF-50: sube las sesiones planificadas y deja constancia en las observaciones del
     * tratamiento aplicado. Debe llamarse dentro de la transacción del que lo usa.
     */
    public function ampliarSesiones(TratamientoAplicado $ta, int $nuevo): void
    {
        $anterior = $ta->sesiones_planificadas;

        $ta->sesiones_planificadas = $nuevo;
        $ta->observaciones = trim(($ta->observaciones ?? '')."\n[Sesiones ampliadas el ".today()->format('d/m/Y')." de {$anterior} a {$nuevo}]");
        $ta->save();

        BitacoraService::registrar(BitacoraService::AMPLIAR_SESIONES, auth()->id(), 'tratamientos_aplicados', $ta->id);
    }
}
