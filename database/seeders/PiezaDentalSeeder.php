<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class PiezaDentalSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de piezas dentales con numeración FDI (32 permanentes y 20 temporales).
     * El id es el código FDI (ck_piezas_dentales_id), por eso siempre se escribe.
     *
     * 52 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: pieza_dental). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('piezas_dentales', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla piezas_dentales, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'codigo_fdi', 'nombre', 'cuadrante', 'tipo_pieza', 'tipo_denticion'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [11, 11, 'Incisivo central sup. der.', 1, 'incisivo', 'permanente'],
            [12, 12, 'Incisivo lateral sup. der.', 1, 'incisivo', 'permanente'],
            [13, 13, 'Canino sup. der.', 1, 'canino', 'permanente'],
            [14, 14, 'Primer premolar sup. der.', 1, 'premolar', 'permanente'],
            [15, 15, 'Segundo premolar sup. der.', 1, 'premolar', 'permanente'],
            [16, 16, 'Primer molar sup. der.', 1, 'molar', 'permanente'],
            [17, 17, 'Segundo molar sup. der.', 1, 'molar', 'permanente'],
            [18, 18, 'Tercer molar sup. der.', 1, 'molar', 'permanente'],
            [21, 21, 'Incisivo central sup. izq.', 2, 'incisivo', 'permanente'],
            [22, 22, 'Incisivo lateral sup. izq.', 2, 'incisivo', 'permanente'],
            [23, 23, 'Canino sup. izq.', 2, 'canino', 'permanente'],
            [24, 24, 'Primer premolar sup. izq.', 2, 'premolar', 'permanente'],
            [25, 25, 'Segundo premolar sup. izq.', 2, 'premolar', 'permanente'],
            [26, 26, 'Primer molar sup. izq.', 2, 'molar', 'permanente'],
            [27, 27, 'Segundo molar sup. izq.', 2, 'molar', 'permanente'],
            [28, 28, 'Tercer molar sup. izq.', 2, 'molar', 'permanente'],
            [31, 31, 'Incisivo central inf. izq.', 3, 'incisivo', 'permanente'],
            [32, 32, 'Incisivo lateral inf. izq.', 3, 'incisivo', 'permanente'],
            [33, 33, 'Canino inf. izq.', 3, 'canino', 'permanente'],
            [34, 34, 'Primer premolar inf. izq.', 3, 'premolar', 'permanente'],
            [35, 35, 'Segundo premolar inf. izq.', 3, 'premolar', 'permanente'],
            [36, 36, 'Primer molar inf. izq.', 3, 'molar', 'permanente'],
            [37, 37, 'Segundo molar inf. izq.', 3, 'molar', 'permanente'],
            [38, 38, 'Tercer molar inf. izq.', 3, 'molar', 'permanente'],
            [41, 41, 'Incisivo central inf. der.', 4, 'incisivo', 'permanente'],
            [42, 42, 'Incisivo lateral inf. der.', 4, 'incisivo', 'permanente'],
            [43, 43, 'Canino inf. der.', 4, 'canino', 'permanente'],
            [44, 44, 'Primer premolar inf. der.', 4, 'premolar', 'permanente'],
            [45, 45, 'Segundo premolar inf. der.', 4, 'premolar', 'permanente'],
            [46, 46, 'Primer molar inf. der.', 4, 'molar', 'permanente'],
            [47, 47, 'Segundo molar inf. der.', 4, 'molar', 'permanente'],
            [48, 48, 'Tercer molar inf. der.', 4, 'molar', 'permanente'],
            [51, 51, 'Inc. central temp. sup. der.', 5, 'incisivo', 'temporal'],
            [52, 52, 'Inc. lateral temp. sup. der.', 5, 'incisivo', 'temporal'],
            [53, 53, 'Canino temp. sup. der.', 5, 'canino', 'temporal'],
            [54, 54, 'Primer molar temp. sup. der.', 5, 'molar', 'temporal'],
            [55, 55, 'Segundo molar temp. sup. der.', 5, 'molar', 'temporal'],
            [61, 61, 'Inc. central temp. sup. izq.', 6, 'incisivo', 'temporal'],
            [62, 62, 'Inc. lateral temp. sup. izq.', 6, 'incisivo', 'temporal'],
            [63, 63, 'Canino temp. sup. izq.', 6, 'canino', 'temporal'],
            [64, 64, 'Primer molar temp. sup. izq.', 6, 'molar', 'temporal'],
            [65, 65, 'Segundo molar temp. sup. izq.', 6, 'molar', 'temporal'],
            [71, 71, 'Inc. central temp. inf. izq.', 7, 'incisivo', 'temporal'],
            [72, 72, 'Inc. lateral temp. inf. izq.', 7, 'incisivo', 'temporal'],
            [73, 73, 'Canino temp. inf. izq.', 7, 'canino', 'temporal'],
            [74, 74, 'Primer molar temp. inf. izq.', 7, 'molar', 'temporal'],
            [75, 75, 'Segundo molar temp. inf. izq.', 7, 'molar', 'temporal'],
            [81, 81, 'Inc. central temp. inf. der.', 8, 'incisivo', 'temporal'],
            [82, 82, 'Inc. lateral temp. inf. der.', 8, 'incisivo', 'temporal'],
            [83, 83, 'Canino temp. inf. der.', 8, 'canino', 'temporal'],
            [84, 84, 'Primer molar temp. inf. der.', 8, 'molar', 'temporal'],
            [85, 85, 'Segundo molar temp. inf. der.', 8, 'molar', 'temporal'],
        ];
    }
}
