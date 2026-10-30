<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class TratamientoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de tratamientos (CU15, RF-25). La duración estimada es la que se
     * propone al programar una cita (RN-08). Hay uno dado de baja para probar que
     * CU10 no lo ofrece.
     *
     * 16 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: tratamiento). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('tratamientos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla tratamientos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'nombre', 'descripcion', 'especialidad_id', 'duracion_estimada_minutos', 'numero_sesiones_previstas', 'requiere_sesiones', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'Limpieza dental', 'Profilaxis y destartraje supragingival', 6, 45, 1, false, 'disponible'],
            [2, 'Endodoncia unirradicular', 'Tratamiento de conducto en pieza de una raíz', 2, 60, 3, true, 'disponible'],
            [3, 'Resina compuesta', 'Obturación estética con resina fotocurable', 3, 45, 1, false, 'disponible'],
            [4, 'Corona de porcelana', 'Corona protésica sobre pieza preparada', 3, 60, 2, true, 'disponible'],
            [5, 'Ortodoncia con brackets', 'Tratamiento ortodóncico fijo con brackets metálicos', 1, 45, 24, true, 'disponible'],
            [6, 'Extracción de tercer molar', 'Exodoncia quirúrgica de tercer molar', 5, 60, 1, false, 'disponible'],
            [7, 'Implante dental unitario', 'Colocación de implante endoóseo unitario', 7, 90, 2, true, 'disponible'],
            [8, 'Blanqueamiento dental', 'Blanqueamiento en consultorio con peróxido', 3, 60, 2, true, 'disponible'],
            [9, 'Aplicación de flúor', 'Fluorización preventiva en dentición temporal', 4, 30, 1, false, 'disponible'],
            [10, 'Carilla de porcelana', 'Carilla estética adherida en sector anterior', 3, 60, 2, true, 'disponible'],
            [11, 'Extracción simple', 'Exodoncia de pieza sin complicación quirúrgica', 5, 30, 1, false, 'disponible'],
            [12, 'Endodoncia multirradicular', 'Tratamiento de conducto en pieza de varias raíces', 2, 90, 4, true, 'disponible'],
            [13, 'Sellante de fosas y fisuras', 'Sellado preventivo en molares', 4, 30, 1, false, 'disponible'],
            [14, 'Destartraje periodontal', 'Raspado y alisado radicular por cuadrantes', 6, 60, 2, true, 'disponible'],
            [15, 'Prótesis parcial removible', 'Prótesis removible de acrílico', 3, 60, 4, true, 'disponible'],
            // Agregado a la referencia: los 15 tratamientos del documento se usan en
            // tratamientos_aplicados. Este no se usa en ninguno y está dado de baja.
            [16, 'Blanqueamiento con lámpara LED', 'Blanqueamiento en consultorio con lámpara LED. Dado de baja hasta reponer el equipo.', 3, 75, 2, true, 'no disponible'],
        ];
    }
}
