<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class HistoriaAntecedenteSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Antecedentes médicos registrados en cada historia clínica.
     *
     * 17 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: historia_antecedente). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('historias_antecedentes', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla historias_antecedentes, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'historia_clinica_id', 'antecedente_medico_id', 'observacion', 'fecha_registro'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 1, 'Reacción cutánea reportada en 2015', '2026-02-03'],
            [2, 2, 5, 'Usa inhalador de rescate', '2026-07-28'],
            [3, 3, 3, 'Controlada con medicación diaria', '2026-05-12'],
            [4, 4, 6, 'Se emplea anestésico alternativo', '2026-03-28'],
            [5, 7, 2, 'Hemoglobina glicosilada de 6.5 en el control de marzo', '2026-04-15'],
            [6, 7, 3, 'Tratamiento antihipertensivo permanente', '2026-04-15'],
            [7, 10, 3, 'Registrado en la apertura de la historia clínica', '2025-10-14'],
            [8, 10, 2, 'Registrado en la apertura de la historia clínica', '2025-10-14'],
            [9, 13, 4, 'Registrado en la apertura de la historia clínica', '2025-12-02'],
            [10, 14, 5, 'Registrado en la apertura de la historia clínica', '2026-01-12'],
            [11, 17, 1, 'Registrado en la apertura de la historia clínica', '2026-02-23'],
            [12, 18, 3, 'Registrado en la apertura de la historia clínica', '2026-03-09'],
            [13, 19, 7, 'Registrado en la apertura de la historia clínica', '2026-03-23'],
            [14, 22, 3, 'Registrado en la apertura de la historia clínica', '2026-05-25'],
            [15, 22, 2, 'Registrado en la apertura de la historia clínica', '2026-05-25'],
            [16, 24, 6, 'Registrado en la apertura de la historia clínica', '2026-07-06'],
            [17, 12, 5, 'Registrado en la apertura de la historia clínica', '2025-11-18'],
        ];
    }
}
