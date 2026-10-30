<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class ValoracionImplanteSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Valoraciones previas a la colocación de implantes.
     *
     * 5 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: valoracion_implante). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('valoraciones_implante', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla valoraciones_implante, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'paciente_id', 'pieza_dental_id', 'odontologo_id', 'estudio_diagnostico_id', 'tratamiento_aplicado_id', 'fecha_valoracion', 'estado_hueso', 'requiere_injerto', 'observaciones_clinicas', 'resultado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 7, 46, 2, 2, 5, '2026-04-25', 'suficiente', false, 'Reborde alveolar conservado y encía queratinizada suficiente', 'apto'],
            [2, 7, 36, 2, 2, null, '2026-04-25', 'regular', true, 'Requiere injerto óseo previo por reabsorción vestibular', 'apto con condiciones'],
            [3, 18, 37, 2, null, null, '2026-04-10', 'suficiente', false, 'Reborde conservado tras la cicatrización de la exodoncia', 'apto'],
            [4, 22, 18, 1, null, null, '2026-05-20', 'insuficiente', true, 'Seno maxilar descendido, requiere elevación', 'no apto'],
            [5, 13, 24, 2, null, null, '2026-01-05', 'regular', true, 'Se propone injerto previo o prótesis removible', 'apto con condiciones'],
        ];
    }
}
