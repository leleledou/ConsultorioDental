<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class AntecedenteMedicoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de antecedentes médicos que se registran en la historia clínica.
     *
     * 7 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: antecedente_medico). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('antecedentes_medicos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla antecedentes_medicos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'nombre', 'descripcion'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'Alergia a la penicilina', 'Reacción alérgica a antibióticos betalactámicos'],
            [2, 'Diabetes tipo 2', 'Altera la cicatrización y el riesgo periodontal'],
            [3, 'Hipertensión arterial', 'Requiere control de la anestesia con vasoconstrictor'],
            [4, 'Cardiopatía', 'Puede requerir profilaxis antibiótica previa'],
            [5, 'Asma bronquial', 'Considerar en sesiones prolongadas'],
            [6, 'Alergia a la lidocaína', 'Obliga a emplear un anestésico alternativo'],
            [7, 'Trastorno de coagulación', 'Riesgo de sangrado en procedimientos quirúrgicos'],
        ];
    }
}
