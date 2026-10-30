<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class TratamientoTipoEstudioSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Estudios que pide cada tratamiento y si son obligatorios.
     *
     * 11 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: tratamiento_tipo_estudio). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('tratamientos_tipos_estudio', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla tratamientos_tipos_estudio, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'tratamiento_id', 'tipo_estudio_id', 'obligatorio', 'momento_clinico', 'observacion'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 2, 1, true, 'previo al tratamiento', 'Periapical de diagnóstico y de conductometría'],
            [2, 7, 4, true, 'previo al tratamiento', 'Tomografía para evaluar la disponibilidad ósea'],
            [3, 6, 2, true, 'previo al tratamiento', 'Panorámica para evaluar la posición del tercer molar'],
            [4, 5, 2, true, 'previo al tratamiento', 'Panorámica de diagnóstico ortodóncico'],
            [5, 4, 1, false, 'previo al tratamiento', 'Periapical recomendada antes de la preparación'],
            [6, 10, 6, false, 'previo al tratamiento', 'Fotografía clínica del registro estético inicial'],
            [7, 7, 2, false, 'control posterior', 'Panorámica de control de la osteointegración'],
            [8, 12, 1, true, 'previo al tratamiento', 'Periapical de cada conducto'],
            [9, 11, 1, false, 'previo al tratamiento', 'Periapical recomendada antes de la exodoncia'],
            [10, 15, 2, false, 'previo al tratamiento', 'Panorámica para evaluar los pilares'],
            [11, 14, 2, false, 'previo al tratamiento', 'Panorámica para valorar el nivel óseo'],
        ];
    }
}
