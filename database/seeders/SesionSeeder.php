<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class SesionSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Sesiones realizadas. Si tiene cita_id, la cita es del mismo tratamiento
     * aplicado; por eso se cargan después de CitaSeeder.
     *
     * 70 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: sesion). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('sesiones', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla sesiones, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'tratamiento_aplicado_id', 'odontologo_id', 'cita_id', 'numero_sesion', 'fecha', 'avance_tecnico', 'duracion_real_minutos', 'observaciones', 'fecha_proxima_sesion', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 2, 1, 1, '2026-02-05', 'Destartraje supragingival completo y pulido coronario', 45, 'Se instruye la técnica de cepillado', null, 'realizada'],
            [2, 2, 2, 2, 1, '2026-02-12', 'Remoción de caries y obturación con resina en cara oclusal', 45, 'Ajuste oclusal verificado', null, 'realizada'],
            [3, 4, 1, 3, 1, '2026-03-10', 'Cementado de brackets en ambas arcadas y arco inicial 0.014', 50, 'Se entregan indicaciones de higiene con aparatología', '2026-04-10', 'realizada'],
            [4, 4, 1, 5, 2, '2026-04-10', 'Cambio de arco a 0.016 y ajuste de ligaduras', 45, 'Buena respuesta al tratamiento', '2026-05-12', 'realizada'],
            [5, 4, 1, 7, 3, '2026-05-12', 'Ajuste de arco y control de higiene', 40, 'Se observa alineación del sector anterosuperior', '2026-06-11', 'realizada'],
            [6, 4, 1, 10, 4, '2026-06-11', 'Cambio de arco a 0.018 y colocación de cadeneta', 45, null, '2026-07-09', 'realizada'],
            [7, 4, 1, 13, 5, '2026-07-09', 'Ajuste de cadeneta y control del torque anterior', 45, null, '2026-08-11', 'realizada'],
            [8, 4, 1, 16, 6, '2026-08-11', 'Cambio de arco y control del apiñamiento inferior', 45, 'Apiñamiento inferior corregido en un 60 por ciento', '2026-09-14', 'realizada'],
            [9, 5, 2, 6, 1, '2026-05-05', 'Colocación del implante de titanio en el sitio de la pieza 46', 95, 'Sutura y control postoperatorio a los siete días', '2026-11-10', 'realizada'],
            [10, 10, 2, 8, 1, '2026-05-20', 'Aplicación de peróxido de hidrógeno al 35 por ciento', 60, 'Sensibilidad leve posterior', '2026-06-03', 'realizada'],
            [11, 10, 2, 9, 2, '2026-06-03', 'Segunda aplicación y control del tono alcanzado', 55, 'Se alcanza el tono acordado con la paciente', null, 'realizada'],
            [12, 3, 2, 11, 1, '2026-06-15', 'Apertura camaral, conductometría y preparación biomecánica', 60, 'Se deja medicación intraconducto', '2026-07-01', 'realizada'],
            [13, 3, 2, 12, 2, '2026-07-01', 'Recambio de la medicación y ajuste de la preparación', 55, 'Paciente sin sintomatología', '2026-09-16', 'realizada'],
            [14, 7, 1, 14, 1, '2026-07-14', 'Profilaxis y aplicación tópica de flúor en cubeta', 30, 'Niño colaborador', null, 'realizada'],
            [15, 8, 1, 15, 1, '2026-07-21', 'Remoción de caries y obturación de la pieza 85', 45, null, null, 'realizada'],
            [16, 11, 2, 17, 1, '2026-08-14', 'Tallado de la pieza 11 y toma de impresión', 60, 'Se coloca provisional', null, 'realizada'],
            [17, 13, 2, 23, 1, '2025-10-08', 'Raspado y alisado radicular por cuadrante', 60, 'Paciente sin complicaciones', '2025-10-22', 'realizada'],
            [18, 13, 2, 24, 2, '2025-10-22', 'Raspado y alisado radicular por cuadrante', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [19, 14, 2, 25, 1, '2025-11-05', 'Remoción de caries y obturación con resina', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [20, 15, 1, 26, 1, '2025-10-16', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', '2025-10-30', 'realizada'],
            [21, 15, 1, 27, 2, '2025-10-30', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', '2025-11-13', 'realizada'],
            [22, 15, 1, 28, 3, '2025-11-13', 'Apertura, conductometría y obturación de conductos', 95, 'Paciente sin complicaciones', '2025-11-27', 'realizada'],
            [23, 15, 1, 29, 4, '2025-11-27', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', null, 'realizada'],
            [24, 16, 1, 30, 1, '2026-01-15', 'Tallado, impresión y cementado de la corona', 60, 'Paciente sin complicaciones', '2026-01-29', 'realizada'],
            [25, 16, 1, 31, 2, '2026-01-29', 'Tallado, impresión y cementado de la corona', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [26, 17, 1, 32, 1, '2025-11-05', 'Grabado, lavado y sellado de fosas y fisuras', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [27, 18, 1, 33, 1, '2025-11-05', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [28, 19, 2, 34, 1, '2025-11-20', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', '2025-12-04', 'realizada'],
            [29, 19, 2, 35, 2, '2025-12-04', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', '2025-12-18', 'realizada'],
            [30, 19, 2, 36, 3, '2025-12-18', 'Apertura, conductometría y obturación de conductos', 95, 'Paciente sin complicaciones', '2026-01-01', 'realizada'],
            [31, 19, 2, 37, 4, '2026-01-01', 'Apertura, conductometría y obturación de conductos', 90, 'Paciente sin complicaciones', null, 'realizada'],
            [32, 20, 2, 38, 1, '2026-02-10', 'Tallado, impresión y cementado de la corona', 60, 'Paciente sin complicaciones', '2026-02-24', 'realizada'],
            [33, 20, 2, 39, 2, '2026-02-24', 'Tallado, impresión y cementado de la corona', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [34, 21, 2, 40, 1, '2025-12-04', 'Exodoncia simple con anestesia infiltrativa', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [35, 22, 2, 41, 1, '2026-01-08', 'Impresión, prueba de dientes y entrega de la prótesis', 60, 'Paciente sin complicaciones', '2026-01-22', 'realizada'],
            [36, 22, 2, 42, 2, '2026-01-22', 'Impresión, prueba de dientes y entrega de la prótesis', 60, 'Paciente sin complicaciones', '2026-02-05', 'realizada'],
            [37, 22, 2, 43, 3, '2026-02-05', 'Impresión, prueba de dientes y entrega de la prótesis', 65, 'Paciente sin complicaciones', '2026-02-19', 'realizada'],
            [38, 23, 2, 45, 1, '2026-01-14', 'Remoción de caries y obturación con resina', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [39, 24, 2, 46, 1, '2026-02-04', 'Tallado, impresión y cementado de la carilla', 60, 'Paciente sin complicaciones', '2026-02-18', 'realizada'],
            [40, 24, 2, 47, 2, '2026-02-18', 'Tallado, impresión y cementado de la carilla', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [41, 25, 2, 48, 1, '2026-01-28', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [42, 26, 2, 49, 1, '2026-02-18', 'Aplicación del agente blanqueador', 60, 'Paciente sin complicaciones', '2026-03-04', 'realizada'],
            [43, 26, 2, 50, 2, '2026-03-04', 'Aplicación del agente blanqueador', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [44, 27, 1, 51, 1, '2026-02-11', 'Profilaxis y aplicación tópica de flúor', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [45, 28, 1, 52, 1, '2026-03-04', 'Grabado, lavado y sellado de fosas y fisuras', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [46, 29, 2, 53, 1, '2026-02-25', 'Raspado y alisado radicular por cuadrante', 60, 'Paciente sin complicaciones', '2026-03-11', 'realizada'],
            [47, 29, 2, 54, 2, '2026-03-11', 'Raspado y alisado radicular por cuadrante', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [48, 30, 2, 55, 1, '2026-06-10', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [49, 31, 2, 56, 1, '2026-03-11', 'Exodoncia simple con anestesia infiltrativa', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [50, 32, 2, 57, 1, '2026-04-15', 'Colocación del implante y control postoperatorio', 90, 'Paciente sin complicaciones', '2026-04-29', 'realizada'],
            [51, 32, 2, 58, 2, '2026-04-29', 'Colocación del implante y control postoperatorio', 90, 'Paciente sin complicaciones', null, 'realizada'],
            [52, 33, 1, 59, 1, '2026-03-25', 'Ajuste de arco y control de la aparatología', 45, 'Paciente sin complicaciones', '2026-04-15', 'realizada'],
            [53, 33, 1, 60, 2, '2026-04-15', 'Ajuste de arco y control de la aparatología', 45, 'Paciente sin complicaciones', '2026-05-06', 'realizada'],
            [54, 33, 1, 61, 3, '2026-05-06', 'Ajuste de arco y control de la aparatología', 50, 'Paciente sin complicaciones', '2026-05-27', 'realizada'],
            [55, 33, 1, 62, 4, '2026-05-27', 'Ajuste de arco y control de la aparatología', 45, 'Paciente sin complicaciones', '2026-06-17', 'realizada'],
            [56, 33, 1, 63, 5, '2026-06-17', 'Ajuste de arco y control de la aparatología', 45, 'Paciente sin complicaciones', '2026-07-08', 'realizada'],
            [57, 33, 1, 64, 6, '2026-07-08', 'Ajuste de arco y control de la aparatología', 50, 'Paciente sin complicaciones', '2026-07-29', 'realizada'],
            [58, 34, 2, 66, 1, '2026-04-15', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [59, 35, 2, 67, 1, '2026-04-29', 'Remoción de caries y obturación con resina', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [60, 36, 2, 68, 1, '2026-05-13', 'Remoción de caries y obturación con resina', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [61, 37, 2, 69, 1, '2026-05-06', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [62, 38, 2, 70, 1, '2026-05-27', 'Aplicación del agente blanqueador', 60, 'Paciente sin complicaciones', '2026-06-10', 'realizada'],
            [63, 38, 2, 71, 2, '2026-06-10', 'Aplicación del agente blanqueador', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [64, 39, 1, 72, 1, '2026-05-27', 'Exodoncia simple con anestesia infiltrativa', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [65, 40, 1, 73, 1, '2026-07-01', 'Destartraje supragingival y pulido coronario', 45, 'Paciente sin complicaciones', null, 'realizada'],
            [66, 41, 1, 74, 1, '2026-06-17', 'Profilaxis y aplicación tópica de flúor', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [67, 42, 1, 75, 1, '2026-07-08', 'Grabado, lavado y sellado de fosas y fisuras', 30, 'Paciente sin complicaciones', null, 'realizada'],
            [68, 43, 2, 76, 1, '2026-07-08', 'Tallado, impresión y cementado de la carilla', 60, 'Paciente sin complicaciones', '2026-07-22', 'realizada'],
            [69, 43, 2, 77, 2, '2026-07-22', 'Tallado, impresión y cementado de la carilla', 60, 'Paciente sin complicaciones', null, 'realizada'],
            [70, 44, 2, 78, 1, '2026-08-05', 'Tallado, impresión y cementado de la carilla', 60, 'Paciente sin complicaciones', '2026-08-19', 'realizada'],
        ];
    }
}
