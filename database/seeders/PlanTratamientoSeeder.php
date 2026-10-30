<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class PlanTratamientoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Planes de tratamiento. costo_total es la suma de los costo_acordado de sus
     * tratamientos aplicados (lo comprueba tests/Feature/Base/PoblacionTest.php).
     *
     * 28 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: plan_tratamiento). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('planes_tratamiento', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla planes_tratamiento, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'paciente_id', 'odontologo_id', 'fecha_creacion', 'descripcion', 'costo_total', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 2, '2026-02-03', 'Profilaxis y obturación del sector posterior (precio cerrado con 10 % de descuento por paquete)', 315.00, 'concluido'],
            [2, 1, 2, '2026-06-10', 'Tratamiento endodóntico de la pieza 36', 300.00, 'activo'],
            [3, 5, 1, '2026-02-26', 'Tratamiento ortodóncico correctivo con brackets', 3500.00, 'activo'],
            [4, 7, 2, '2026-04-15', 'Rehabilitación con implante en la pieza 46 (precio cerrado con 10 % de descuento por paquete)', 4860.00, 'activo'],
            [5, 6, 1, '2026-07-10', 'Prevención y restauración en dentición mixta', 300.00, 'concluido'],
            [6, 2, 2, '2026-07-28', 'Exodoncia quirúrgica del tercer molar 38', 600.00, 'activo'],
            [7, 3, 2, '2026-05-12', 'Rehabilitación estética del sector anterior', 1900.00, 'activo'],
            [8, 4, 1, '2026-03-28', 'Profilaxis y control preventivo', 150.00, 'cancelado'],
            [9, 9, 2, '2025-10-08', 'Tratamiento periodontal y restauración', 530.00, 'concluido'],
            [10, 10, 1, '2025-10-16', 'Rehabilitación del sector posterior', 1300.00, 'concluido'],
            [11, 11, 1, '2025-11-05', 'Prevención y control de erupción', 230.00, 'concluido'],
            [12, 12, 2, '2025-11-20', 'Endodoncia y corona del molar inferior', 1300.00, 'concluido'],
            [13, 13, 2, '2025-12-04', 'Prótesis parcial removible superior', 2020.00, 'activo'],
            [14, 14, 2, '2026-01-14', 'Restauración por trauma dental', 1400.00, 'concluido'],
            [15, 15, 2, '2026-01-28', 'Tratamiento estético del sector anterior', 850.00, 'concluido'],
            [16, 16, 1, '2026-02-11', 'Prevención en dentición temporal', 220.00, 'concluido'],
            [17, 17, 2, '2026-02-25', 'Control periodontal de mantenimiento', 550.00, 'concluido'],
            [18, 18, 2, '2026-03-11', 'Rehabilitación con implante', 4750.00, 'concluido'],
            [19, 19, 1, '2026-03-25', 'Tratamiento ortodóncico correctivo', 3500.00, 'activo'],
            [20, 20, 2, '2026-04-15', 'Restauración de piezas posteriores', 550.00, 'concluido'],
            [21, 21, 2, '2026-05-06', 'Blanqueamiento y profilaxis', 850.00, 'concluido'],
            [22, 22, 1, '2026-05-27', 'Exodoncia y control', 400.00, 'concluido'],
            [23, 23, 1, '2026-06-17', 'Prevención en dentición mixta', 220.00, 'concluido'],
            [24, 24, 2, '2026-07-08', 'Carillas del sector anterior', 2400.00, 'activo'],
            [25, 3, 2, '2026-09-01', 'Endodoncia del primer molar inferior', 300.00, 'activo'],
            [26, 7, 2, '2026-09-01', 'Segundo implante en el maxilar inferior', 4500.00, 'activo'],
            [27, 19, 1, '2026-09-01', 'Retiro de aparatología y retención', 900.00, 'activo'],
            [28, 13, 2, '2026-09-01', 'Recambio de la prótesis superior', 1800.00, 'activo'],
        ];
    }
}
