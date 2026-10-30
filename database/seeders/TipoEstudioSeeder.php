<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class TipoEstudioSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de tipos de estudio diagnóstico (radiografías, tomografía, fotografía).
     *
     * 6 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: tipo_estudio). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('tipos_estudio', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla tipos_estudio, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'nombre', 'descripcion', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'Radiografía periapical', 'Imagen de una o dos piezas y su periápice', 'activo'],
            [2, 'Radiografía panorámica', 'Vista general de ambos maxilares', 'activo'],
            [3, 'Radiografía bitewing', 'Imagen de aleta de mordida para caries interproximal', 'activo'],
            [4, 'Tomografía computarizada', 'Estudio volumétrico para planificación quirúrgica', 'activo'],
            [5, 'Imagen 3D', 'Reconstrucción tridimensional del maxilar', 'activo'],
            [6, 'Fotografía clínica', 'Registro fotográfico intraoral', 'activo'],
        ];
    }
}
