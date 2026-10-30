<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class HistoriaClinicaSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Una historia clínica por paciente, abierta el día en que se lo registró
     * (igual que CU08).
     *
     * 24 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: historia_clinica). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('historias_clinicas', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla historias_clinicas, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'paciente_id', 'fecha_apertura', 'habitos', 'observaciones_generales'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, '2026-02-03', "Cepillado dos veces al día;\nconsumo frecuente de café", 'Paciente colaboradora, sin antecedentes quirúrgicos'],
            [2, 2, '2026-07-28', "Fumador ocasional;\ncepillado una vez al día", 'Refiere molestia en la zona del tercer molar inferior izquierdo'],
            [3, 3, '2026-05-12', "Cepillado tres veces al día;\nuso de hilo dental", 'Interesada en mejorar la estética del sector anterior'],
            [4, 4, '2026-03-28', 'Cepillado irregular', 'No asistió a la primera cita programada'],
            [5, 5, '2026-02-26', "Onicofagia;\ncepillado dos veces al día", 'Paciente adolescente en tratamiento ortodóncico'],
            [6, 6, '2026-07-10', "Consumo alto de golosinas;\ncepillado asistido por la madre", 'Dentición mixta, primeros molares permanentes ya erupcionados'],
            [7, 7, '2026-04-15', "Bruxismo nocturno;\ncepillado dos veces al día", 'Diabetes tipo 2 controlada, se vigila la cicatrización'],
            [8, 8, '2026-08-24', 'Cepillado dos veces al día', 'Consulta inicial de valoración'],
            [9, 9, '2025-10-06', "Cepillado dos veces al día;\nconsumo de gaseosas", 'Paciente colaboradora'],
            [10, 10, '2025-10-14', "Bruxismo nocturno;\ncepillado dos veces al día", 'Se sugiere placa de descarga'],
            [11, 11, '2025-11-03', "Onicofagia;\ncepillado irregular", 'Control de erupción de premolares'],
            [12, 12, '2025-11-18', 'Cepillado una vez al día', 'Refiere dolor espontáneo nocturno'],
            [13, 13, '2025-12-02', "Cepillado dos veces al día;\nusa prótesis removible", 'Higiene protésica deficiente'],
            [14, 14, '2026-01-12', "Cepillado tres veces al día;\npractica fútbol", 'Se recomienda protector bucal'],
            [15, 15, '2026-01-26', 'Consumo frecuente de café y té', 'Interesada en tratamiento estético'],
            [16, 16, '2026-02-09', "Cepillado asistido por la madre;\nconsumo alto de golosinas", 'Primera consulta pediátrica'],
            [17, 17, '2026-02-23', "Cepillado dos veces al día;\nuso de enjuague", 'Antecedente periodontal en control'],
            [18, 18, '2026-03-09', "Cepillado irregular;\nfumador", 'Pérdida de piezas posteriores'],
            [19, 19, '2026-03-23', 'Cepillado dos veces al día', 'Evaluación de apiñamiento'],
            [20, 20, '2026-04-13', 'Cepillado una vez al día', 'Se refuerza instrucción de higiene'],
            [21, 21, '2026-05-04', "Cepillado dos veces al día;\nuso de hilo dental", 'Sin hallazgos relevantes'],
            [22, 22, '2026-05-25', 'Cepillado dos veces al día', 'Se controla la presión antes de la anestesia'],
            [23, 23, '2026-06-15', "Cepillado asistido;\ndentición mixta", 'Aplicación preventiva programada'],
            [24, 24, '2026-07-06', "Cepillado tres veces al día;\nblanqueadores de venta libre", 'Sensibilidad previa'],
        ];
    }
}
