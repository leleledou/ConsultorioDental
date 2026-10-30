<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class ControlSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Controles posteriores programados al cerrar los tratamientos.
     *
     * 39 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: control). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('controles', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla controles, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'tratamiento_aplicado_id', 'fecha_prevista', 'motivo', 'estado', 'fecha_cumplimiento', 'observaciones'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, '2026-08-05', 'Control de higiene a los seis meses', 'vencido', null, 'No se logró contactar a la paciente'],
            [2, 2, '2026-08-12', 'Control de la obturación de la pieza 16', 'cumplido', '2026-08-14', 'Obturación en buen estado'],
            [3, 3, '2026-09-15', 'Control posterior a la endodoncia', 'pendiente', null, 'Se programa tras la obturación definitiva'],
            [4, 4, '2026-09-14', 'Ajuste periódico de la aparatología', 'pendiente', null, 'Coincide con la cita ya programada'],
            [5, 5, '2026-08-05', 'Control de la osteointegración del implante', 'cumplido', '2026-08-05', 'Se solicitó panorámica de control'],
            [6, 7, '2027-01-14', 'Fluorización semestral', 'pendiente', null, 'Recordar a la madre del paciente'],
            [7, 10, '2026-12-03', 'Control de la coloración alcanzada', 'pendiente', null, 'Evaluar la necesidad de un retoque'],
            [8, 13, '2026-04-20', 'Control periodontal', 'cumplido', '2026-04-22', 'Programado al cierre de la sesión'],
            [9, 14, '2025-12-05', 'Control de la obturación', 'cumplido', '2025-12-07', 'Programado al cierre de la sesión'],
            [10, 15, '2025-12-27', 'Control radiográfico de la endodoncia', 'vencido', null, 'Programado al cierre de la sesión'],
            [11, 16, '2026-02-28', 'Control de la corona', 'cumplido', '2026-03-02', 'Programado al cierre de la sesión'],
            [12, 17, '2026-05-04', 'Control del sellante', 'cumplido', '2026-05-06', 'Programado al cierre de la sesión'],
            [13, 18, '2026-05-04', 'Control de higiene a los seis meses', 'vencido', null, 'Programado al cierre de la sesión'],
            [14, 19, '2026-01-31', 'Control radiográfico de la endodoncia', 'cumplido', '2026-02-02', 'Programado al cierre de la sesión'],
            [15, 20, '2026-03-26', 'Control de la corona', 'cumplido', '2026-03-28', 'Programado al cierre de la sesión'],
            [16, 21, '2026-01-03', 'Control postextracción', 'vencido', null, 'Programado al cierre de la sesión'],
            [17, 22, '2026-02-07', 'Control de ajuste de la prótesis', 'cumplido', '2026-02-09', 'Programado al cierre de la sesión'],
            [18, 23, '2026-02-13', 'Control de la obturación', 'cumplido', '2026-02-15', 'Programado al cierre de la sesión'],
            [19, 24, '2026-03-20', 'Control de la carilla', 'vencido', null, 'Programado al cierre de la sesión'],
            [20, 25, '2026-07-27', 'Control de higiene a los seis meses', 'cumplido', '2026-07-29', 'Programado al cierre de la sesión'],
            [21, 26, '2026-04-03', 'Control de la coloración', 'cumplido', '2026-04-05', 'Programado al cierre de la sesión'],
            [22, 27, '2026-08-10', 'Fluorización semestral', 'vencido', null, 'Programado al cierre de la sesión'],
            [23, 28, '2026-08-31', 'Control del sellante', 'cumplido', '2026-09-02', 'Programado al cierre de la sesión'],
            [24, 29, '2026-09-07', 'Control periodontal', 'pendiente', null, 'Programado al cierre de la sesión'],
            [25, 30, '2026-12-07', 'Control de higiene a los seis meses', 'pendiente', null, 'Programado al cierre de la sesión'],
            [26, 31, '2026-04-10', 'Control postextracción', 'cumplido', '2026-04-12', 'Programado al cierre de la sesión'],
            [27, 32, '2026-05-29', 'Control de la osteointegración', 'cumplido', '2026-05-31', 'Programado al cierre de la sesión'],
            [28, 33, '2026-04-24', 'Ajuste periódico de la aparatología', 'vencido', null, 'Programado al cierre de la sesión'],
            [29, 34, '2026-10-12', 'Control de higiene a los seis meses', 'pendiente', null, 'Programado al cierre de la sesión'],
            [30, 35, '2026-05-29', 'Control de la obturación', 'cumplido', '2026-05-31', 'Programado al cierre de la sesión'],
            [31, 36, '2026-06-12', 'Control de la obturación', 'vencido', null, 'Programado al cierre de la sesión'],
            [32, 37, '2026-11-02', 'Control de higiene a los seis meses', 'pendiente', null, 'Programado al cierre de la sesión'],
            [33, 38, '2026-07-10', 'Control de la coloración', 'cumplido', '2026-07-12', 'Programado al cierre de la sesión'],
            [34, 39, '2026-06-26', 'Control postextracción', 'vencido', null, 'Programado al cierre de la sesión'],
            [35, 40, '2026-12-28', 'Control de higiene a los seis meses', 'pendiente', null, 'Programado al cierre de la sesión'],
            [36, 41, '2026-12-14', 'Fluorización semestral', 'pendiente', null, 'Programado al cierre de la sesión'],
            [37, 42, '2027-01-04', 'Control del sellante', 'pendiente', null, 'Programado al cierre de la sesión'],
            [38, 43, '2026-08-21', 'Control de la carilla', 'cumplido', '2026-08-23', 'Programado al cierre de la sesión'],
            [39, 44, '2026-09-04', 'Control de la carilla', 'cumplido', '2026-09-06', 'Programado al cierre de la sesión'],
        ];
    }
}
