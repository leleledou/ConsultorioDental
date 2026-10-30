<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class TarifaSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Tarifas de los tratamientos: la vigente de 2026 y, para varios, la cerrada de
     * 2025, para probar el historial de precios.
     *
     * 26 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: tarifa). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('tarifas', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla tarifas, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'tratamiento_id', 'precio', 'fecha_inicio_vigencia', 'fecha_fin_vigencia', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 150.00, '2026-01-01', null, 'vigente'],
            [2, 2, 280.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [3, 2, 300.00, '2026-01-01', null, 'vigente'],
            [4, 3, 200.00, '2026-01-01', null, 'vigente'],
            [5, 4, 900.00, '2026-01-01', null, 'vigente'],
            [6, 5, 3500.00, '2026-01-01', null, 'vigente'],
            [7, 6, 600.00, '2026-01-01', null, 'vigente'],
            [8, 7, 4500.00, '2026-01-01', null, 'vigente'],
            [9, 8, 700.00, '2026-01-01', null, 'vigente'],
            [10, 9, 100.00, '2026-01-01', null, 'vigente'],
            [11, 10, 1200.00, '2026-01-01', null, 'vigente'],
            [12, 11, 250.00, '2026-01-01', null, 'vigente'],
            [13, 12, 450.00, '2026-01-01', null, 'vigente'],
            [14, 13, 120.00, '2026-01-01', null, 'vigente'],
            [15, 14, 400.00, '2026-01-01', null, 'vigente'],
            [16, 15, 1800.00, '2026-01-01', null, 'vigente'],
            [17, 1, 130.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [18, 3, 180.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [19, 5, 3200.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [20, 11, 220.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [21, 14, 350.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [22, 4, 800.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [23, 8, 620.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [24, 12, 400.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [25, 6, 550.00, '2025-01-01', '2025-12-31', 'cerrada'],
            [26, 13, 100.00, '2025-01-01', '2025-12-31', 'cerrada'],
        ];
    }
}
