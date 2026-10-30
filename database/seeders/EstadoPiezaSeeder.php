<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class EstadoPiezaSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de estados de pieza del odontograma, con su color de referencia.
     *
     * 10 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: estado_pieza). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('estados_pieza', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla estados_pieza, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'codigo', 'nombre', 'descripcion', 'color_referencia', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'SANO', 'Sano', 'Pieza sin patología visible', '#43A047', 'activo'],
            [2, 'CARIES', 'Caries', 'Lesión cariosa activa', '#E53935', 'activo'],
            [3, 'AUSENTE', 'Ausente o perdido', 'Pieza no presente en boca', '#9E9E9E', 'activo'],
            [4, 'RESINA', 'Obturado con resina', 'Restauración con resina compuesta', '#1E88E5', 'activo'],
            [5, 'CARILLA', 'Carilla', 'Carilla estética adherida', '#8E24AA', 'activo'],
            [6, 'PERNO', 'Perno', 'Perno intrarradicular', '#FB8C00', 'activo'],
            [7, 'CORONA', 'Corona', 'Corona protésica', '#FDD835', 'activo'],
            [8, 'IMPLANTE', 'Implante', 'Implante osteointegrado', '#00897B', 'activo'],
            [9, 'ENDO', 'Tratamiento de conducto', 'Pieza con endodoncia realizada', '#6D4C41', 'activo'],
            [10, 'ORTO', 'En tratamiento ortodóncico', 'Pieza con aparatología fija', '#3949AB', 'activo'],
        ];
    }
}
