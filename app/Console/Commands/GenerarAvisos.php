<?php

namespace App\Console\Commands;

use App\Services\AvisoService;
use App\Services\BitacoraService;
use Illuminate\Console\Command;

/**
 * CU40 Generar avisos internos (RF-81). Actor: Temporizador (Laravel Scheduler).
 * Se programa en routes/console.php todos los días a las 00:15 (hora de Bolivia),
 * después de controles:vencer, para que los controles ya figuren como vencidos.
 * Flujo alternativo: el botón «Generar avisos ahora» de la lista de avisos.
 */
class GenerarAvisos extends Command
{
    /**
     * Nombre y firma del comando.
     *
     * @var string
     */
    protected $signature = 'avisos:generar';

    /**
     * Descripción del comando.
     *
     * @var string
     */
    protected $description = 'Genera los avisos internos de citas, controles, saldos, estudios y sesiones.';

    public function handle(AvisoService $avisos): int
    {
        $detalle = $avisos->generar();
        $total = array_sum($detalle);

        $this->table(['Tipo de aviso', 'Generados'], collect($detalle)->map(fn ($n, $tipo) => [$tipo, $n])->values()->all());

        if ($total === 0) {
            $this->info('No hay avisos nuevos que generar.');

            return self::SUCCESS;
        }

        // Sin usuario: lo ejecuta el sistema.
        BitacoraService::registrar(BitacoraService::GENERAR_AVISOS, null, 'avisos', null, 'sistema');

        $this->info("Se generaron {$total} avisos.");

        return self::SUCCESS;
    }
}
