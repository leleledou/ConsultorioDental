<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class EstudioDiagnosticoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Estudios diagnósticos cargados por paciente (previos, de control e iniciales).
     *
     * 31 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: estudio_diagnostico). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('estudios_diagnosticos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla estudios_diagnosticos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'paciente_id', 'tipo_estudio_id', 'odontologo_id', 'pieza_dental_id', 'tratamiento_aplicado_id', 'fecha_estudio', 'centro_radiologico', 'ruta_archivo', 'hallazgos', 'fecha_registro'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 5, 2, 1, null, 4, '2026-03-05', 'Centro Radiológico Dental Imagen', '/estudios/2026/daniela_panoramica.jpg', 'Terceros molares en formación, sin patología asociada', '2026-03-06'],
            [2, 7, 4, 2, 46, 5, '2026-04-22', 'Centro Radiológico Dental Imagen', '/estudios/2026/silvia_tomografia.dcm', 'Altura ósea de 12 mm y ancho de 6 mm en el sitio de la pieza 46', '2026-04-23'],
            [3, 1, 1, 2, 36, 3, '2026-06-12', null, '/estudios/2026/ana_periapical_36.jpg', 'Lesión periapical incipiente en la raíz distal de la pieza 36', '2026-06-12'],
            [4, 1, 1, 2, 36, 3, '2026-07-01', null, '/estudios/2026/ana_periapical_36_control.jpg', 'Conductometría conforme, sin ensanchamiento periapical', '2026-07-01'],
            [5, 2, 2, 2, 38, 9, '2026-08-05', 'Radiología Oral Santa Cruz', '/estudios/2026/carlos_panoramica.jpg', 'Tercer molar 38 en posición mesioangular con retención parcial', '2026-08-06'],
            [6, 3, 6, 2, 11, 11, '2026-08-10', null, '/estudios/2026/maria_foto_inicial.jpg', 'Registro fotográfico del sector anterior previo a la carilla', '2026-08-10'],
            [7, 7, 2, 2, 46, 5, '2026-08-05', 'Centro Radiológico Dental Imagen', '/estudios/2026/silvia_panoramica_control.jpg', 'Implante en posición, sin signos de pérdida ósea periimplantaria', '2026-08-06'],
            [8, 9, 2, 2, null, 13, '2025-10-05', 'Centro Radiológico Dental Imagen', '/estudios/2025/p9_panoramica_8.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2025-10-06'],
            [9, 10, 1, 1, 46, 15, '2025-10-13', 'Centro de Imagenología Oriental', '/estudios/2025/p10_periapical_9.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2025-10-14'],
            [10, 12, 1, 2, 36, 19, '2025-11-17', 'Radiología Oral Santa Cruz', '/estudios/2025/p12_periapical_10.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2025-11-18'],
            [11, 13, 1, 2, 24, 21, '2025-12-01', null, '/estudios/2025/p13_periapical_11.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2025-12-02'],
            [12, 13, 2, 2, null, 22, '2026-01-05', 'Centro Radiológico Dental Imagen', '/estudios/2026/p13_panoramica_12.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2026-01-06'],
            [13, 17, 2, 2, null, 29, '2026-02-22', 'Radiología Oral Santa Cruz', '/estudios/2026/p17_panoramica_13.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2026-02-23'],
            [14, 18, 4, 2, 37, 32, '2026-04-12', null, '/estudios/2026/p18_tomografia_14.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2026-04-13'],
            [15, 19, 2, 1, null, 33, '2026-03-22', 'Radiología Oral Santa Cruz', '/estudios/2026/p19_panoramica_15.jpg', 'Estudio previo conforme, sin hallazgos que contraindiquen el tratamiento', '2026-03-23'],
            [16, 9, 2, 2, null, 13, '2025-10-29', 'Radiología Oral Santa Cruz', '/estudios/2025/p9_control_16.jpg', 'Control posterior sin signos de complicación', '2025-10-30'],
            [17, 10, 1, 1, 46, 15, '2025-12-04', 'Centro de Imagenología Oriental', '/estudios/2025/p10_control_17.jpg', 'Control posterior sin signos de complicación', '2025-12-05'],
            [18, 10, 1, 1, 46, 16, '2026-02-05', null, '/estudios/2026/p10_control_18.jpg', 'Control posterior sin signos de complicación', '2026-02-06'],
            [19, 12, 1, 2, 36, 19, '2026-01-08', 'Centro Radiológico Dental Imagen', '/estudios/2026/p12_control_19.jpg', 'Control posterior sin signos de complicación', '2026-01-09'],
            [20, 12, 1, 2, 36, 20, '2026-03-03', 'Radiología Oral Santa Cruz', '/estudios/2026/p12_control_20.jpg', 'Control posterior sin signos de complicación', '2026-03-04'],
            [21, 13, 1, 2, 24, 21, '2025-12-11', 'Centro de Imagenología Oriental', '/estudios/2025/p13_control_21.jpg', 'Control posterior sin signos de complicación', '2025-12-12'],
            [22, 17, 2, 2, null, 29, '2026-03-18', null, '/estudios/2026/p17_control_22.jpg', 'Control posterior sin signos de complicación', '2026-03-19'],
            [23, 18, 1, 2, 37, 31, '2026-03-18', 'Centro Radiológico Dental Imagen', '/estudios/2026/p18_control_23.jpg', 'Control posterior sin signos de complicación', '2026-03-19'],
            [24, 18, 2, 2, 37, 32, '2026-05-06', 'Radiología Oral Santa Cruz', '/estudios/2026/p18_control_24.jpg', 'Control posterior sin signos de complicación', '2026-05-07'],
            [25, 22, 1, 1, 18, 39, '2026-06-03', 'Centro de Imagenología Oriental', '/estudios/2026/p22_control_25.jpg', 'Control posterior sin signos de complicación', '2026-06-04'],
            [26, 21, 2, 2, null, null, '2026-05-08', 'Centro Radiológico Dental Imagen', '/estudios/2026/p21_inicial_26.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2026-05-09'],
            [27, 23, 2, 1, null, null, '2026-06-19', 'Radiología Oral Santa Cruz', '/estudios/2026/p23_inicial_27.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2026-06-20'],
            [28, 8, 1, 2, null, null, '2026-08-26', 'Centro de Imagenología Oriental', '/estudios/2026/p8_inicial_28.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2026-08-27'],
            [29, 16, 3, 1, null, null, '2026-02-12', null, '/estudios/2026/p16_inicial_29.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2026-02-13'],
            [30, 11, 2, 1, null, null, '2025-11-06', 'Centro Radiológico Dental Imagen', '/estudios/2025/p11_inicial_30.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2025-11-07'],
            [31, 14, 6, 2, null, null, '2026-01-15', 'Radiología Oral Santa Cruz', '/estudios/2026/p14_inicial_31.jpg', 'Estudio de diagnóstico inicial solicitado en la primera consulta', '2026-01-16'],
        ];
    }
}
