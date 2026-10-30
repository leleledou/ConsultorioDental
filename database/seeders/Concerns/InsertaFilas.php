<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Inserta en una tabla las filas copiadas de database/seeders/referencia/poblacion_referencia.sql.
 *
 * Se usa DB::table() y no Model::create() porque se conservan los id de la
 * referencia (no están en $fillable) y porque así se insertan por lotes.
 * Como se escriben los id, los seeders solo funcionan sobre una base vacía:
 * ejecutar siempre con php artisan migrate:fresh --seed.
 */
trait InsertaFilas
{
    /**
     * @param  list<string>  $columnas  Nombres de las columnas, en el orden de cada fila.
     * @param  list<list<mixed>>  $filas  Valores de cada registro.
     */
    protected function insertarFilas(string $tabla, array $columnas, array $filas): void
    {
        $registros = array_map(fn (array $fila) => array_combine($columnas, $fila), $filas);

        // Lotes de 200 para no pasar el límite de parámetros por consulta.
        foreach (array_chunk($registros, 200) as $lote) {
            DB::table($tabla)->insert($lote);
        }
    }
}
