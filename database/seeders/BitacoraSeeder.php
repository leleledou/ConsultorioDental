<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class BitacoraSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Registros de la bitácora. registro_id no tiene llave foránea (apunta a
     * distintas tablas). usuario_digitado queda en null: solo se usa en los
     * intentos de login fallidos.
     *
     * 26 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: bitacora). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('bitacoras', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla bitacoras, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'usuario_id', 'accion', 'tabla_afectada', 'registro_id', 'fecha_hora', 'ip'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 'iniciar_sesion', 'usuario', 1, '2026-02-03 07:55', '192.168.1.10'],
            [2, 2, 'crear_paciente', 'paciente', 1, '2026-02-03 08:12', '192.168.1.11'],
            [3, 2, 'crear_odontograma', 'odontograma', 1, '2026-02-03 08:40', '192.168.1.11'],
            [4, 2, 'registrar_cita', 'cita', 1, '2026-02-03 08:55', '192.168.1.11'],
            [5, 2, 'registrar_pago', 'pago', 1, '2026-02-05 09:20', '192.168.1.11'],
            [6, 1, 'crear_paciente', 'paciente', 5, '2026-02-26 09:05', '192.168.1.10'],
            [7, 2, 'registrar_sesion', 'sesion', 16, '2026-08-14 11:05', '192.168.1.11'],
            [8, 1, 'anular_cita', 'cita', 18, '2026-08-14 17:30', '192.168.1.10'],
            [9, 2, 'actualizar_paciente', 'paciente', 3, '2026-08-20 10:20', '192.168.1.11'],
            [10, 2, 'crear_paciente', 'paciente', 8, '2026-08-24 11:40', '192.168.1.12'],
            [11, 2, 'crear_paciente', 'paciente', 9, '2025-10-06 08:07', '192.168.1.11'],
            [12, 1, 'crear_paciente', 'paciente', 10, '2025-10-14 08:14', '192.168.1.12'],
            [13, 2, 'crear_paciente', 'paciente', 11, '2025-11-03 08:21', '192.168.1.13'],
            [14, 1, 'crear_paciente', 'paciente', 12, '2025-11-18 08:28', '192.168.1.14'],
            [15, 2, 'crear_paciente', 'paciente', 13, '2025-12-02 08:35', '192.168.1.10'],
            [16, 1, 'crear_paciente', 'paciente', 14, '2026-01-12 08:42', '192.168.1.11'],
            [17, 2, 'crear_paciente', 'paciente', 15, '2026-01-26 08:49', '192.168.1.12'],
            [18, 1, 'crear_paciente', 'paciente', 16, '2026-02-09 08:56', '192.168.1.13'],
            [19, 2, 'crear_paciente', 'paciente', 17, '2026-02-23 08:03', '192.168.1.14'],
            [20, 1, 'crear_paciente', 'paciente', 18, '2026-03-09 08:10', '192.168.1.10'],
            [21, 2, 'crear_paciente', 'paciente', 19, '2026-03-23 08:17', '192.168.1.11'],
            [22, 1, 'crear_paciente', 'paciente', 20, '2026-04-13 08:24', '192.168.1.12'],
            [23, 2, 'crear_paciente', 'paciente', 21, '2026-05-04 08:31', '192.168.1.13'],
            [24, 1, 'crear_paciente', 'paciente', 22, '2026-05-25 08:38', '192.168.1.14'],
            [25, 2, 'crear_paciente', 'paciente', 23, '2026-06-15 08:45', '192.168.1.10'],
            [26, 1, 'crear_paciente', 'paciente', 24, '2026-07-06 08:52', '192.168.1.11'],
        ];
    }
}
