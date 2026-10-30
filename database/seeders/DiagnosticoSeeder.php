<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class DiagnosticoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de diagnósticos con su código CIE-10.
     *
     * 12 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: diagnostico). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('diagnosticos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla diagnosticos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'codigo', 'nombre', 'descripcion', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'K02.1', 'Caries de la dentina', 'Lesión cariosa que compromete dentina', 'activo'],
            [2, 'K04.0', 'Pulpitis', 'Inflamación del tejido pulpar', 'activo'],
            [3, 'K04.1', 'Necrosis pulpar', 'Muerte del tejido pulpar', 'activo'],
            [4, 'K05.1', 'Gingivitis crónica', 'Inflamación gingival por placa bacteriana', 'activo'],
            [5, 'K05.3', 'Periodontitis', 'Pérdida de inserción y de soporte óseo', 'activo'],
            [6, 'K07.4', 'Maloclusión', 'Alteración de la relación entre arcadas', 'activo'],
            [7, 'K08.1', 'Edentulismo parcial', 'Pérdida de una o varias piezas dentales', 'activo'],
            [8, 'K01.1', 'Diente retenido', 'Pieza incluida que no completa su erupción', 'activo'],
            [9, 'S02.5', 'Fractura dental', 'Fractura coronaria o radicular', 'activo'],
            [10, 'Z01.2', 'Examen odontológico preventivo', 'Control sin patología diagnosticada', 'activo'],
            [11, 'K03.6', 'Cálculo dental', 'Depósitos de sarro supragingival y subgingival', 'activo'],
            [12, 'K00.6', 'Alteración de la erupción', 'Retraso o desviación en la erupción dentaria', 'activo'],
        ];
    }
}
