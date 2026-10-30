<?php

namespace Database\Seeders;

use App\Models\EstadoCita;
use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Citas históricas del documento (octubre de 2025 a octubre de 2026).
     * Las citas de la semana actual y las dos siguientes las crea AgendaActualSeeder.
     *
     * tratamiento_aplicado_id apunta a tratamientos_aplicados: por eso este seeder
     * se ejecuta después de TratamientoAplicadoSeeder. Las sesiones, que apuntan a
     * las citas, se cargan después (SesionSeeder).
     *
     * El estado se busca por nombre (constantes de EstadoCita), nunca por id.
     * hora_fin no se escribe: la calcula la base de datos.
     *
     * 103 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: cita). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        // estado_cita_id llega como nombre de estado y se cambia por su id.
        $registros = array_map(function (array $fila) {
            $fila[8] = EstadoCita::idDe($fila[8]);

            return $fila;
        }, $this->filas());

        $this->insertarFilas('citas', $this->columnas(), $registros);
    }

    /**
     * Columnas de la tabla citas, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'paciente_id', 'odontologo_id', 'tratamiento_aplicado_id', 'fecha', 'hora_inicio', 'duracion_minutos', 'motivo', 'estado_cita_id', 'observaciones', 'fecha_registro'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 2, 1, '2026-02-05', '08:30', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-02-03'],
            [2, 1, 2, 2, '2026-02-12', '09:00', 45, 'Obturación de la pieza 16', EstadoCita::ATENDIDA, null, '2026-02-05'],
            [3, 5, 1, 4, '2026-03-10', '08:00', 45, 'Colocación de brackets', EstadoCita::ATENDIDA, null, '2026-02-26'],
            [4, 4, 1, 12, '2026-04-02', '10:00', 45, 'Limpieza dental', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-03-28'],
            [5, 5, 1, 4, '2026-04-10', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::ATENDIDA, null, '2026-03-10'],
            [6, 7, 2, 5, '2026-05-05', '09:00', 90, 'Colocación del implante en la pieza 46', EstadoCita::ATENDIDA, null, '2026-04-25'],
            [7, 5, 1, 4, '2026-05-12', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::ATENDIDA, null, '2026-04-10'],
            [8, 3, 2, 10, '2026-05-20', '10:00', 60, 'Blanqueamiento, primera sesión', EstadoCita::ATENDIDA, null, '2026-05-12'],
            [9, 3, 2, 10, '2026-06-03', '10:00', 60, 'Blanqueamiento, segunda sesión', EstadoCita::ATENDIDA, null, '2026-05-20'],
            [10, 5, 1, 4, '2026-06-11', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::ATENDIDA, null, '2026-05-12'],
            [11, 1, 2, 3, '2026-06-15', '09:00', 60, 'Endodoncia, primera sesión', EstadoCita::ATENDIDA, null, '2026-06-10'],
            [12, 1, 2, 3, '2026-07-01', '09:00', 60, 'Endodoncia, segunda sesión', EstadoCita::ATENDIDA, null, '2026-06-15'],
            [13, 5, 1, 4, '2026-07-09', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::ATENDIDA, null, '2026-06-11'],
            [14, 6, 1, 7, '2026-07-14', '15:00', 30, 'Aplicación de flúor', EstadoCita::ATENDIDA, null, '2026-07-10'],
            [15, 6, 1, 8, '2026-07-21', '15:00', 45, 'Obturación de la pieza 85', EstadoCita::ATENDIDA, null, '2026-07-14'],
            [16, 5, 1, 4, '2026-08-11', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::ATENDIDA, null, '2026-07-09'],
            [17, 3, 2, 11, '2026-08-14', '10:00', 60, 'Preparación para la carilla de la pieza 11', EstadoCita::ATENDIDA, null, '2026-08-03'],
            [18, 3, 2, 11, '2026-08-19', '16:00', 60, 'Prueba de la carilla', EstadoCita::CANCELADA, 'Cancelada a pedido de la paciente', '2026-08-14'],
            [19, 8, 2, null, '2026-08-25', '11:00', 45, 'Consulta inicial de valoración', EstadoCita::ATENDIDA, null, '2026-08-24'],
            [20, 2, 2, 9, '2026-09-10', '09:00', 60, 'Extracción del tercer molar 38', EstadoCita::PROGRAMADA, null, '2026-08-28'],
            [21, 5, 1, 4, '2026-09-14', '08:00', 45, 'Control mensual de ortodoncia', EstadoCita::PROGRAMADA, null, '2026-08-11'],
            [22, 1, 2, 3, '2026-09-16', '09:00', 60, 'Endodoncia, tercera sesión', EstadoCita::PROGRAMADA, null, '2026-07-01'],
            [23, 9, 2, 13, '2025-10-08', '08:00', 60, 'Destartraje periodontal', EstadoCita::ATENDIDA, null, '2025-10-04'],
            [24, 9, 2, 13, '2025-10-22', '09:00', 60, 'Destartraje periodontal', EstadoCita::ATENDIDA, null, '2025-10-18'],
            [25, 9, 2, 14, '2025-11-05', '10:00', 45, 'Obturación con resina', EstadoCita::ATENDIDA, null, '2025-11-01'],
            [26, 10, 1, 15, '2025-10-16', '14:30', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-10-12'],
            [27, 10, 1, 15, '2025-10-30', '14:30', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-10-26'],
            [28, 10, 1, 15, '2025-11-13', '15:30', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-11-09'],
            [29, 10, 1, 15, '2025-11-27', '16:30', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-11-23'],
            [30, 10, 1, 16, '2026-01-15', '17:30', 60, 'Corona de porcelana', EstadoCita::ATENDIDA, null, '2026-01-11'],
            [31, 10, 1, 16, '2026-01-29', '08:30', 60, 'Corona de porcelana', EstadoCita::ATENDIDA, null, '2026-01-25'],
            [32, 11, 1, 17, '2025-11-05', '09:30', 30, 'Sellante preventivo', EstadoCita::ATENDIDA, null, '2025-11-01'],
            [33, 11, 1, 18, '2025-11-05', '15:00', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2025-11-01'],
            [34, 12, 2, 19, '2025-11-20', '15:00', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-11-16'],
            [35, 12, 2, 19, '2025-12-04', '16:00', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-11-30'],
            [36, 12, 2, 19, '2025-12-18', '17:00', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-12-14'],
            [37, 12, 2, 19, '2026-01-01', '08:00', 90, 'Endodoncia', EstadoCita::ATENDIDA, null, '2025-12-28'],
            [38, 12, 2, 20, '2026-02-10', '08:00', 60, 'Corona de porcelana', EstadoCita::ATENDIDA, null, '2026-02-06'],
            [39, 12, 2, 20, '2026-02-24', '09:00', 60, 'Corona de porcelana', EstadoCita::ATENDIDA, null, '2026-02-20'],
            [40, 13, 2, 21, '2025-12-04', '10:00', 30, 'Extracción simple', EstadoCita::ATENDIDA, null, '2025-11-30'],
            [41, 13, 2, 22, '2026-01-08', '11:00', 60, 'Prótesis parcial removible', EstadoCita::ATENDIDA, null, '2026-01-04'],
            [42, 13, 2, 22, '2026-01-22', '14:30', 60, 'Prótesis parcial removible', EstadoCita::ATENDIDA, null, '2026-01-18'],
            [43, 13, 2, 22, '2026-02-05', '15:30', 60, 'Prótesis parcial removible', EstadoCita::ATENDIDA, null, '2026-02-01'],
            [44, 13, 2, 22, '2026-02-19', '16:30', 60, 'Prótesis parcial removible', EstadoCita::PROGRAMADA, null, '2026-02-15'],
            [45, 14, 2, 23, '2026-01-14', '17:30', 45, 'Obturación con resina', EstadoCita::ATENDIDA, null, '2026-01-10'],
            [46, 14, 2, 24, '2026-02-04', '08:30', 60, 'Carilla de porcelana', EstadoCita::ATENDIDA, null, '2026-01-31'],
            [47, 14, 2, 24, '2026-02-18', '09:30', 60, 'Carilla de porcelana', EstadoCita::ATENDIDA, null, '2026-02-14'],
            [48, 15, 2, 25, '2026-01-28', '10:30', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-01-24'],
            [49, 15, 2, 26, '2026-02-18', '15:00', 60, 'Blanqueamiento dental', EstadoCita::ATENDIDA, null, '2026-02-14'],
            [50, 15, 2, 26, '2026-03-04', '16:00', 60, 'Blanqueamiento dental', EstadoCita::ATENDIDA, null, '2026-02-28'],
            [51, 16, 1, 27, '2026-02-11', '17:00', 30, 'Aplicación de flúor', EstadoCita::ATENDIDA, null, '2026-02-07'],
            [52, 16, 1, 28, '2026-03-04', '18:00', 30, 'Sellante preventivo', EstadoCita::ATENDIDA, null, '2026-02-28'],
            [53, 17, 2, 29, '2026-02-25', '08:00', 60, 'Destartraje periodontal', EstadoCita::ATENDIDA, null, '2026-02-21'],
            [54, 17, 2, 29, '2026-03-11', '09:00', 60, 'Destartraje periodontal', EstadoCita::ATENDIDA, null, '2026-03-07'],
            [55, 17, 2, 30, '2026-06-10', '10:00', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-06-06'],
            [56, 18, 2, 31, '2026-03-11', '11:00', 30, 'Extracción simple', EstadoCita::ATENDIDA, null, '2026-03-07'],
            [57, 18, 2, 32, '2026-04-15', '14:30', 90, 'Colocación de implante', EstadoCita::ATENDIDA, null, '2026-04-11'],
            [58, 18, 2, 32, '2026-04-29', '15:30', 90, 'Colocación de implante', EstadoCita::ATENDIDA, null, '2026-04-25'],
            [59, 19, 1, 33, '2026-03-25', '16:30', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-03-21'],
            [60, 19, 1, 33, '2026-04-15', '17:30', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-04-11'],
            [61, 19, 1, 33, '2026-05-06', '08:30', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-05-02'],
            [62, 19, 1, 33, '2026-05-27', '09:30', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-05-23'],
            [63, 19, 1, 33, '2026-06-17', '10:30', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-06-13'],
            [64, 19, 1, 33, '2026-07-08', '15:00', 45, 'Control de ortodoncia', EstadoCita::ATENDIDA, null, '2026-07-04'],
            [65, 19, 1, 33, '2026-07-29', '16:00', 45, 'Control de ortodoncia', EstadoCita::PROGRAMADA, null, '2026-07-25'],
            [66, 20, 2, 34, '2026-04-15', '08:00', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-04-11'],
            [67, 20, 2, 35, '2026-04-29', '18:00', 45, 'Obturación con resina', EstadoCita::ATENDIDA, null, '2026-04-25'],
            [68, 20, 2, 36, '2026-05-13', '08:00', 45, 'Obturación con resina', EstadoCita::ATENDIDA, null, '2026-05-09'],
            [69, 21, 2, 37, '2026-05-06', '10:00', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-05-02'],
            [70, 21, 2, 38, '2026-05-27', '11:00', 60, 'Blanqueamiento dental', EstadoCita::ATENDIDA, null, '2026-05-23'],
            [71, 21, 2, 38, '2026-06-10', '11:00', 60, 'Blanqueamiento dental', EstadoCita::ATENDIDA, null, '2026-06-06'],
            [72, 22, 1, 39, '2026-05-27', '14:30', 30, 'Extracción simple', EstadoCita::ATENDIDA, null, '2026-05-23'],
            [73, 22, 1, 40, '2026-07-01', '15:30', 45, 'Limpieza dental', EstadoCita::ATENDIDA, null, '2026-06-27'],
            [74, 23, 1, 41, '2026-06-17', '16:30', 30, 'Aplicación de flúor', EstadoCita::ATENDIDA, null, '2026-06-13'],
            [75, 23, 1, 42, '2026-07-08', '17:30', 30, 'Sellante preventivo', EstadoCita::ATENDIDA, null, '2026-07-04'],
            [76, 24, 2, 43, '2026-07-08', '08:30', 60, 'Carilla de porcelana', EstadoCita::ATENDIDA, null, '2026-07-04'],
            [77, 24, 2, 43, '2026-07-22', '09:30', 60, 'Carilla de porcelana', EstadoCita::ATENDIDA, null, '2026-07-18'],
            [78, 24, 2, 44, '2026-08-05', '10:30', 60, 'Carilla de porcelana', EstadoCita::ATENDIDA, null, '2026-08-01'],
            [79, 24, 2, 44, '2026-08-19', '15:00', 60, 'Carilla de porcelana', EstadoCita::PROGRAMADA, null, '2026-08-15'],
            [80, 9, 2, null, '2025-10-22', '16:00', 45, 'Limpieza dental', EstadoCita::CANCELADA, 'Cancelada por el paciente con aviso previo', '2025-10-17'],
            [81, 11, 1, null, '2025-12-10', '17:00', 45, 'Limpieza dental', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2025-12-05'],
            [82, 13, 2, null, '2026-01-22', '18:00', 60, 'Prótesis parcial removible', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-01-17'],
            [83, 15, 2, null, '2026-03-04', '08:00', 60, 'Blanqueamiento dental', EstadoCita::CANCELADA, 'Cancelada por el paciente con aviso previo', '2026-02-27'],
            [84, 17, 2, null, '2026-03-18', '09:00', 60, 'Destartraje periodontal', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-03-13'],
            [85, 19, 1, null, '2026-04-22', '10:00', 45, 'Control de ortodoncia', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-04-17'],
            [86, 20, 2, null, '2026-05-20', '11:00', 45, 'Obturación con resina', EstadoCita::CANCELADA, 'Cancelada por el paciente con aviso previo', '2026-05-15'],
            [87, 21, 2, null, '2026-06-10', '14:30', 60, 'Blanqueamiento dental', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-06-05'],
            [88, 22, 1, null, '2026-07-15', '15:30', 45, 'Limpieza dental', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-07-10'],
            [89, 24, 2, null, '2026-08-19', '17:30', 60, 'Carilla de porcelana', EstadoCita::CANCELADA, 'Cancelada por el paciente con aviso previo', '2026-08-14'],
            [90, 10, 1, null, '2026-02-05', '17:30', 60, 'Corona de porcelana', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-01-31'],
            [91, 12, 2, null, '2026-03-11', '15:00', 60, 'Corona de porcelana', EstadoCita::NO_ASISTIDA, 'El paciente no se presentó', '2026-03-06'],
            [92, 9, 2, null, '2026-09-14', '09:30', 45, 'Limpieza dental', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [93, 14, 2, null, '2026-09-15', '10:30', 60, 'Carilla de porcelana', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [94, 17, 2, null, '2026-09-16', '15:00', 45, 'Limpieza dental', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [95, 20, 2, null, '2026-09-17', '16:00', 45, 'Limpieza dental', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [96, 23, 1, null, '2026-09-18', '17:00', 30, 'Aplicación de flúor', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [97, 11, 1, null, '2026-09-21', '18:00', 45, 'Limpieza dental', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [98, 16, 1, null, '2026-09-22', '08:00', 30, 'Aplicación de flúor', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [99, 24, 2, null, '2026-09-23', '09:00', 60, 'Carilla de porcelana', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [100, 3, 2, 45, '2026-09-14', '11:00', 60, 'Endodoncia unirradicular', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [101, 7, 2, 46, '2026-09-21', '14:30', 90, 'Colocación de implante', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [102, 19, 1, 47, '2026-09-28', '14:30', 60, 'Corona de porcelana', EstadoCita::PROGRAMADA, null, '2026-09-01'],
            [103, 13, 2, 48, '2026-10-05', '15:30', 60, 'Prótesis parcial removible', EstadoCita::PROGRAMADA, null, '2026-09-01'],
        ];
    }
}
