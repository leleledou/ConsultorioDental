<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de especialidades definido en el documento de diseño.
     * El id se escribe (no se deja al autoincremental) porque users y tratamientos
     * de la referencia apuntan a esos id.
     *
     * 7 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: especialidad). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('especialidades', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla especialidades, en el orden de cada fila.
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
            [1, 'Ortodoncia', 'Corrección de la posición de los dientes y los maxilares'],
            [2, 'Endodoncia', 'Tratamiento de los conductos radiculares'],
            [3, 'Estética dental', 'Restauraciones estéticas, carillas y blanqueamiento'],
            [4, 'Odontopediatría', 'Atención odontológica de niños y adolescentes'],
            [5, 'Cirugía', 'Extracciones y procedimientos quirúrgicos menores'],
            [6, 'Periodoncia', 'Tratamiento de encías y tejidos de soporte'],
            [7, 'Implantología', 'Colocación y rehabilitación sobre implantes'],
        ];
    }
}
