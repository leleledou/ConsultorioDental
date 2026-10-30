<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class SesionMaterialSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Materiales consumidos en cada sesión.
     *
     * 77 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: sesion_material). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('sesiones_materiales', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla sesiones_materiales, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'sesion_id', 'material_id', 'cantidad', 'observacion'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 2, 1, 1.00, 'Resina A2 para la cara oclusal'],
            [2, 3, 4, 1.00, 'Arco de níquel titanio 0.014'],
            [3, 6, 4, 1.00, 'Arco de níquel titanio 0.018'],
            [4, 8, 4, 1.00, 'Arco de acero 0.019 x 0.025'],
            [5, 9, 3, 1.00, 'Implante de titanio de 4.0 x 10 mm'],
            [6, 9, 5, 3.60, 'Dos carpules de anestésico'],
            [7, 12, 5, 1.80, 'Un carpule de anestésico'],
            [8, 13, 2, 3.00, 'Conos para el conducto distal'],
            [9, 15, 1, 1.00, 'Resina A1 para molar temporal'],
            [10, 16, 6, 2.00, 'Cemento para el provisional'],
            [11, 17, 5, 1.80, 'Consumo de la sesión'],
            [12, 18, 5, 1.80, 'Consumo de la sesión'],
            [13, 19, 1, 1.00, 'Consumo de la sesión'],
            [14, 20, 2, 4.00, 'Consumo de la sesión'],
            [15, 20, 5, 1.80, 'Consumo de la sesión'],
            [16, 21, 2, 4.00, 'Consumo de la sesión'],
            [17, 21, 5, 1.80, 'Consumo de la sesión'],
            [18, 22, 2, 4.00, 'Consumo de la sesión'],
            [19, 22, 5, 1.80, 'Consumo de la sesión'],
            [20, 23, 2, 4.00, 'Consumo de la sesión'],
            [21, 23, 5, 1.80, 'Consumo de la sesión'],
            [22, 24, 6, 2.00, 'Consumo de la sesión'],
            [23, 25, 6, 2.00, 'Consumo de la sesión'],
            [24, 26, 7, 1.00, 'Consumo de la sesión'],
            [25, 27, 5, 1.80, 'Consumo de la sesión'],
            [26, 28, 2, 4.00, 'Consumo de la sesión'],
            [27, 28, 5, 1.80, 'Consumo de la sesión'],
            [28, 29, 2, 4.00, 'Consumo de la sesión'],
            [29, 29, 5, 1.80, 'Consumo de la sesión'],
            [30, 30, 2, 4.00, 'Consumo de la sesión'],
            [31, 30, 5, 1.80, 'Consumo de la sesión'],
            [32, 31, 2, 4.00, 'Consumo de la sesión'],
            [33, 31, 5, 1.80, 'Consumo de la sesión'],
            [34, 32, 6, 2.00, 'Consumo de la sesión'],
            [35, 33, 6, 2.00, 'Consumo de la sesión'],
            [36, 34, 5, 1.80, 'Consumo de la sesión'],
            [37, 34, 9, 1.00, 'Consumo de la sesión'],
            [38, 35, 10, 30.00, 'Consumo de la sesión'],
            [39, 36, 10, 30.00, 'Consumo de la sesión'],
            [40, 37, 10, 30.00, 'Consumo de la sesión'],
            [41, 38, 1, 1.00, 'Consumo de la sesión'],
            [42, 39, 6, 2.00, 'Consumo de la sesión'],
            [43, 40, 6, 2.00, 'Consumo de la sesión'],
            [44, 41, 5, 1.80, 'Consumo de la sesión'],
            [45, 45, 7, 1.00, 'Consumo de la sesión'],
            [46, 46, 5, 1.80, 'Consumo de la sesión'],
            [47, 47, 5, 1.80, 'Consumo de la sesión'],
            [48, 48, 5, 1.80, 'Consumo de la sesión'],
            [49, 49, 5, 1.80, 'Consumo de la sesión'],
            [50, 49, 9, 1.00, 'Consumo de la sesión'],
            [51, 50, 3, 1.00, 'Consumo de la sesión'],
            [52, 50, 5, 3.60, 'Consumo de la sesión'],
            [53, 51, 3, 1.00, 'Consumo de la sesión'],
            [54, 51, 5, 3.60, 'Consumo de la sesión'],
            [55, 52, 8, 20.00, 'Consumo de la sesión'],
            [56, 52, 4, 1.00, 'Consumo de la sesión'],
            [57, 53, 8, 20.00, 'Consumo de la sesión'],
            [58, 53, 4, 1.00, 'Consumo de la sesión'],
            [59, 54, 8, 20.00, 'Consumo de la sesión'],
            [60, 54, 4, 1.00, 'Consumo de la sesión'],
            [61, 55, 8, 20.00, 'Consumo de la sesión'],
            [62, 55, 4, 1.00, 'Consumo de la sesión'],
            [63, 56, 8, 20.00, 'Consumo de la sesión'],
            [64, 56, 4, 1.00, 'Consumo de la sesión'],
            [65, 57, 8, 20.00, 'Consumo de la sesión'],
            [66, 57, 4, 1.00, 'Consumo de la sesión'],
            [67, 58, 5, 1.80, 'Consumo de la sesión'],
            [68, 59, 1, 1.00, 'Consumo de la sesión'],
            [69, 60, 1, 1.00, 'Consumo de la sesión'],
            [70, 61, 5, 1.80, 'Consumo de la sesión'],
            [71, 64, 5, 1.80, 'Consumo de la sesión'],
            [72, 64, 9, 1.00, 'Consumo de la sesión'],
            [73, 65, 5, 1.80, 'Consumo de la sesión'],
            [74, 67, 7, 1.00, 'Consumo de la sesión'],
            [75, 68, 6, 2.00, 'Consumo de la sesión'],
            [76, 69, 6, 2.00, 'Consumo de la sesión'],
            [77, 70, 6, 2.00, 'Consumo de la sesión'],
        ];
    }
}
