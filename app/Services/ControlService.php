<?php

namespace App\Services;

use App\Models\Control;
use App\Models\Paciente;

/**
 * Clase de control "CTR ControlService" del análisis.
 *
 * Reúne las reglas de los controles posteriores que comparten CU38 Programar control y
 * CU39 Actualizar controles vencidos (RF-79, RF-80). El vencimiento lo usan el comando
 * controles:vencer (actor Temporizador) y su disparo manual desde la lista de controles.
 */
class ControlService
{
    /**
     * RF-80: pasa a "vencido" los controles pendientes con fecha prevista anterior a hoy.
     * Un control que vence hoy todavía está a tiempo. Devuelve cuántos actualizó.
     */
    public function vencerPendientes(): int
    {
        return Control::where('estado', 'pendiente')
            ->whereDate('fecha_prevista', '<', today())
            ->update(['estado' => 'vencido']);
    }

    /**
     * Paciente del control. La tabla no guarda paciente_id (3NF): se deriva por
     * tratamiento_aplicado -> plan_tratamiento -> paciente.
     */
    public function pacienteDe(Control $control): Paciente
    {
        $control->loadMissing('tratamientoAplicado.plan.paciente');

        return $control->tratamientoAplicado->plan->paciente;
    }
}
