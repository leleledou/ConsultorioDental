<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class MaterialSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de materiales que se registran como consumo de cada sesión.
     *
     * 10 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: material). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('materiales', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla materiales, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'nombre', 'descripcion', 'categoria', 'unidad_medida', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'Resina fotocurable', 'Resina compuesta de fotopolimerización', 'restaurador', 'unidad', 'activo'],
            [2, 'Conos de gutapercha', 'Material de obturación de conductos', 'endodóntico', 'cono', 'activo'],
            [3, 'Implante de titanio', 'Implante endoóseo de titanio grado 4', 'quirúrgico', 'unidad', 'activo'],
            [4, 'Arco de ortodoncia', 'Arco de níquel titanio', 'ortodóncico', 'unidad', 'activo'],
            [5, 'Anestésico lidocaína 2%', 'Solución anestésica con vasoconstrictor', 'descartable', 'mililitro', 'activo'],
            [6, 'Cemento de ionómero', 'Cemento de ionómero de vidrio', 'restaurador', 'gramo', 'activo'],
            [7, 'Sellante fotopolimerizable', 'Resina fluida para sellado de fosas', 'preventivo', 'unidad', 'activo'],
            [8, 'Bracket metálico', 'Bracket de acero inoxidable', 'ortodóncico', 'unidad', 'activo'],
            [9, 'Hilo de sutura', 'Sutura de seda 3-0', 'quirúrgico', 'unidad', 'activo'],
            [10, 'Acrílico termocurable', 'Acrílico para base de prótesis', 'protésico', 'gramo', 'activo'],
        ];
    }
}
