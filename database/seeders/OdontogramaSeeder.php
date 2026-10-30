<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class OdontogramaSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Odontogramas iniciales y de evolución (uno por historia clínica y fecha).
     *
     * 22 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: odontograma). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('odontogramas', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla odontogramas, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'historia_clinica_id', 'odontologo_id', 'fecha_registro', 'tipo', 'tipo_denticion', 'observaciones', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 2, '2026-02-03', 'inicial', 'permanente', 'Toma inicial al abrir la historia clínica', 'activo'],
            [2, 1, 2, '2026-07-05', 'evolución', 'permanente', 'Actualización tras la obturación y la endodoncia', 'activo'],
            [3, 5, 1, '2026-02-26', 'inicial', 'permanente', 'Toma inicial previa al tratamiento ortodóncico', 'activo'],
            [4, 6, 1, '2026-07-10', 'inicial', 'mixta', 'Se registran las piezas temporales y los primeros molares permanentes', 'activo'],
            [5, 7, 2, '2026-04-15', 'inicial', 'permanente', 'Toma inicial para la valoración del implante', 'activo'],
            [6, 9, 2, '2025-10-06', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [7, 10, 1, '2025-10-14', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [8, 11, 1, '2025-11-03', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [9, 12, 2, '2025-11-18', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [10, 13, 2, '2025-12-02', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [11, 14, 2, '2026-01-12', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [12, 17, 2, '2026-02-23', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [13, 18, 2, '2026-03-09', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [14, 19, 1, '2026-03-23', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [15, 20, 2, '2026-04-13', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [16, 22, 1, '2026-05-25', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [17, 24, 2, '2026-07-06', 'inicial', 'permanente', 'Levantamiento inicial del odontograma', 'activo'],
            [18, 16, 1, '2026-02-09', 'inicial', 'temporal', 'Levantamiento inicial del odontograma', 'activo'],
            [19, 23, 1, '2026-06-15', 'inicial', 'mixta', 'Levantamiento inicial del odontograma', 'activo'],
            [20, 9, 2, '2026-04-24', 'evolución', 'permanente', 'Actualización tras los tratamientos realizados', 'activo'],
            [21, 12, 2, '2026-06-06', 'evolución', 'permanente', 'Actualización tras los tratamientos realizados', 'activo'],
            [22, 19, 1, '2026-10-09', 'evolución', 'permanente', 'Actualización tras los tratamientos realizados', 'activo'],
        ];
    }
}
