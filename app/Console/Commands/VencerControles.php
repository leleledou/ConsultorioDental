<?php

namespace App\Console\Commands;

use App\Services\BitacoraService;
use App\Services\ControlService;
use Illuminate\Console\Command;

/**
 * CU39 Actualizar controles vencidos (RF-80). Actor: Temporizador (Laravel Scheduler).
 * Se programa en routes/console.php todos los días a las 00:05 (hora de Bolivia).
 * Flujo alternativo: el botón «Actualizar controles vencidos» de la lista de controles.
 */
class VencerControles extends Command
{
    /**
     * Nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'controles:vencer';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Marca como vencidos los controles pendientes cuya fecha prevista ya pasó.';

    public function handle(ControlService $controles): int
    {
        $cantidad = $controles->vencerPendientes();

        if ($cantidad === 0) {
            // Sin cambios no se escribe en la bitácora, para no llenarla de ruido diario.
            $this->info('No hay controles vencidos por actualizar.');

            return self::SUCCESS;
        }

        // Sin usuario: lo ejecuta el sistema.
        BitacoraService::registrar(BitacoraService::VENCER_CONTROLES, null, 'controles', null, 'sistema');

        $this->info("Se actualizaron {$cantidad} controles vencidos.");

        return self::SUCCESS;
    }
}
