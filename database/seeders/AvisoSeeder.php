<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class AvisoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Avisos generados para cada odontólogo. referencia_id no tiene llave foránea:
     * apunta a citas, controles o tratamientos aplicados según el tipo.
     *
     * 19 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: aviso). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('avisos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla avisos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'odontologo_id', 'tipo', 'referencia_id', 'mensaje', 'fecha_generacion', 'leido'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 2, 'cita próxima', 20, 'Cita del 10/09/2026 a las 09:00 con Carlos Rojas para la extracción del tercer molar 38', '2026-09-07 07:00', false],
            [2, 1, 'cita próxima', 21, 'Cita del 14/09/2026 a las 08:00 con Daniela Fernández para control de ortodoncia', '2026-09-07 07:00', false],
            [3, 2, 'control pendiente', 3, 'Control posterior a la endodoncia de Ana Vargas previsto para el 15/09/2026', '2026-09-07 07:05', false],
            [4, 1, 'control pendiente', 4, 'Ajuste de ortodoncia de Daniela Fernández previsto para el 14/09/2026', '2026-09-07 07:05', true],
            [5, 2, 'saldo pendiente', 5, 'El implante de Silvia Cuéllar mantiene un saldo pendiente de 1500.00', '2026-09-01 08:00', true],
            [6, 2, 'saldo pendiente', 3, 'La endodoncia de Ana Vargas mantiene un saldo pendiente de 200.00', '2026-09-01 08:00', false],
            [7, 2, 'estudio diagnóstico pendiente', 6, 'La corona sobre implante de Silvia Cuéllar requiere una periapical previa', '2026-09-02 08:00', false],
            [8, 2, 'sesión pendiente sin cita', 11, 'La carilla de María Ortiz tiene una sesión pendiente sin cita programada', '2026-09-03 08:00', false],
            [9, 2, 'control pendiente', 3, 'Control previsto para el 2026-09-15', '2026-09-05 07:00', true],
            [10, 1, 'control pendiente', 4, 'Control previsto para el 2026-09-14', '2026-09-05 07:00', false],
            [11, 1, 'control pendiente', 6, 'Control previsto para el 2027-01-14', '2026-09-05 07:00', false],
            [12, 2, 'control pendiente', 7, 'Control previsto para el 2026-12-03', '2026-09-05 07:00', true],
            [13, 1, 'control pendiente', 24, 'Control previsto para el 2026-09-07', '2026-09-05 07:00', false],
            [14, 2, 'control pendiente', 25, 'Control previsto para el 2026-12-07', '2026-09-05 07:00', false],
            [15, 2, 'control pendiente', 29, 'Control previsto para el 2026-10-12', '2026-09-05 07:00', true],
            [16, 1, 'control pendiente', 32, 'Control previsto para el 2026-11-02', '2026-09-05 07:00', false],
            [17, 2, 'control pendiente', 35, 'Control previsto para el 2026-12-28', '2026-09-05 07:00', false],
            [18, 1, 'control pendiente', 36, 'Control previsto para el 2026-12-14', '2026-09-05 07:00', true],
            [19, 2, 'control pendiente', 37, 'Control previsto para el 2027-01-04', '2026-09-05 07:00', false],
        ];
    }
}
