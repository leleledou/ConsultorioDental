<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class TratamientoAplicadoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Tratamientos aplicados de cada plan, con su diagnóstico, pieza y costo acordado.
     * Va antes que citas, sesiones, pagos, controles, estudios y detalles de
     * odontograma, que lo referencian.
     *
     * 48 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: tratamiento_aplicado). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('tratamientos_aplicados', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla tratamientos_aplicados, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'plan_tratamiento_id', 'tratamiento_id', 'odontologo_id', 'pieza_dental_id', 'diagnostico_id', 'observacion_diagnostica', 'costo_acordado', 'sesiones_planificadas', 'fecha_inicio', 'fecha_fin', 'estado', 'observaciones'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 1, 2, null, 4, 'Gingivitis generalizada por placa bacteriana', 135.00, 1, '2026-02-05', '2026-02-05', 'concluido', 'Se indica control de higiene a los seis meses Precio neto: descuento de paquete del 10 % distribuido en este tratamiento.'],
            [2, 1, 3, 2, 16, 1, 'Caries oclusal en la pieza 16', 180.00, 1, '2026-02-12', '2026-02-12', 'concluido', 'Obturación con resina fotocurable Precio neto: descuento de paquete del 10 % distribuido en este tratamiento.'],
            [3, 2, 2, 2, 36, 2, 'Pulpitis irreversible en la pieza 36', 300.00, 3, '2026-06-15', null, 'en proceso', 'Tarifa vigente al momento del acuerdo: 300.00'],
            [4, 3, 5, 1, null, 6, 'Maloclusión clase I con apiñamiento', 3500.00, 24, '2026-03-10', null, 'en proceso', 'Control mensual de arcos'],
            [5, 4, 7, 2, 46, 7, 'Edentulismo parcial en la pieza 46', 4050.00, 2, '2026-05-05', null, 'en proceso', 'Valoración previa con resultado apto Precio neto: descuento de paquete del 10 % distribuido en este tratamiento.'],
            [6, 4, 4, 2, 46, 7, 'Rehabilitación protésica sobre el implante', 810.00, 2, '2026-11-10', null, 'planificado', 'Se ejecuta tras la osteointegración Precio neto: descuento de paquete del 10 % distribuido en este tratamiento.'],
            [7, 5, 9, 1, null, 10, 'Control preventivo en dentición mixta', 100.00, 1, '2026-07-14', '2026-07-14', 'concluido', 'Se indica fluorización semestral'],
            [8, 5, 3, 1, 85, 1, 'Caries oclusal en la pieza 85', 200.00, 1, '2026-07-21', '2026-07-21', 'concluido', 'Obturación en molar temporal'],
            [9, 6, 6, 2, 38, 8, 'Tercer molar retenido con pericoronaritis', 600.00, 1, '2026-09-10', null, 'planificado', 'Panorámica previa ya cargada'],
            [10, 7, 8, 2, null, 10, 'Pigmentación extrínseca del sector anterior', 700.00, 2, '2026-05-20', '2026-06-03', 'concluido', 'Dos sesiones en consultorio'],
            [11, 7, 10, 2, 11, 9, 'Fractura del ángulo incisal de la pieza 11', 1200.00, 2, '2026-08-14', null, 'en proceso', 'Carilla en proceso de elaboración'],
            [12, 8, 1, 1, null, 4, 'Gingivitis leve', 150.00, 1, '2026-04-02', null, 'cancelado', 'El paciente no asistió y no reprogramó'],
            [13, 9, 14, 2, null, 1, 'Diagnóstico confirmado en la valoración clínica', 350.00, 2, '2025-10-08', '2025-10-22', 'concluido', 'Tratamiento concluido'],
            [14, 9, 3, 2, 26, 1, 'Diagnóstico confirmado en la valoración clínica', 180.00, 1, '2025-11-05', '2025-11-05', 'concluido', 'Tratamiento concluido'],
            [15, 10, 12, 1, 46, 9, 'Diagnóstico confirmado en la valoración clínica', 400.00, 4, '2025-10-16', '2025-11-27', 'concluido', 'Tratamiento concluido'],
            [16, 10, 4, 1, 46, 9, 'Diagnóstico confirmado en la valoración clínica', 900.00, 2, '2026-01-15', '2026-01-29', 'concluido', 'Tratamiento concluido'],
            [17, 11, 13, 1, 16, 11, 'Diagnóstico confirmado en la valoración clínica', 100.00, 1, '2025-11-05', '2025-11-05', 'concluido', 'Tratamiento concluido'],
            [18, 11, 1, 1, null, 11, 'Diagnóstico confirmado en la valoración clínica', 130.00, 1, '2025-11-05', '2025-11-05', 'concluido', 'Tratamiento concluido'],
            [19, 12, 12, 2, 36, 9, 'Diagnóstico confirmado en la valoración clínica', 400.00, 4, '2025-11-20', '2026-01-01', 'concluido', 'Tratamiento concluido'],
            [20, 12, 4, 2, 36, 9, 'Diagnóstico confirmado en la valoración clínica', 900.00, 2, '2026-02-10', '2026-02-24', 'concluido', 'Tratamiento concluido'],
            [21, 13, 11, 2, 24, 7, 'Diagnóstico confirmado en la valoración clínica', 220.00, 1, '2025-12-04', '2025-12-04', 'concluido', 'Tratamiento concluido'],
            [22, 13, 15, 2, null, 7, 'Diagnóstico confirmado en la valoración clínica', 1800.00, 4, '2026-01-08', null, 'en proceso', 'Avance de 3 de 4 sesiones'],
            [23, 14, 3, 2, 21, 9, 'Diagnóstico confirmado en la valoración clínica', 200.00, 1, '2026-01-14', '2026-01-14', 'concluido', 'Tratamiento concluido'],
            [24, 14, 10, 2, 21, 9, 'Diagnóstico confirmado en la valoración clínica', 1200.00, 2, '2026-02-04', '2026-02-18', 'concluido', 'Tratamiento concluido'],
            [25, 15, 1, 2, null, 10, 'Diagnóstico confirmado en la valoración clínica', 150.00, 1, '2026-01-28', '2026-01-28', 'concluido', 'Tratamiento concluido'],
            [26, 15, 8, 2, null, 10, 'Diagnóstico confirmado en la valoración clínica', 700.00, 2, '2026-02-18', '2026-03-04', 'concluido', 'Tratamiento concluido'],
            [27, 16, 9, 1, null, 12, 'Diagnóstico confirmado en la valoración clínica', 100.00, 1, '2026-02-11', '2026-02-11', 'concluido', 'Tratamiento concluido'],
            [28, 16, 13, 1, 55, 12, 'Diagnóstico confirmado en la valoración clínica', 120.00, 1, '2026-03-04', '2026-03-04', 'concluido', 'Tratamiento concluido'],
            [29, 17, 14, 2, null, 11, 'Diagnóstico confirmado en la valoración clínica', 400.00, 2, '2026-02-25', '2026-03-11', 'concluido', 'Tratamiento concluido'],
            [30, 17, 1, 2, null, 11, 'Diagnóstico confirmado en la valoración clínica', 150.00, 1, '2026-06-10', '2026-06-10', 'concluido', 'Tratamiento concluido'],
            [31, 18, 11, 2, 37, 7, 'Diagnóstico confirmado en la valoración clínica', 250.00, 1, '2026-03-11', '2026-03-11', 'concluido', 'Tratamiento concluido'],
            [32, 18, 7, 2, 37, 7, 'Diagnóstico confirmado en la valoración clínica', 4500.00, 2, '2026-04-15', '2026-04-29', 'concluido', 'Tratamiento concluido'],
            [33, 19, 5, 1, null, 6, 'Diagnóstico confirmado en la valoración clínica', 3500.00, 24, '2026-03-25', null, 'en proceso', 'Avance de 6 de 24 sesiones'],
            [34, 20, 1, 2, null, 1, 'Diagnóstico confirmado en la valoración clínica', 150.00, 1, '2026-04-15', '2026-04-15', 'concluido', 'Tratamiento concluido'],
            [35, 20, 3, 2, 47, 1, 'Diagnóstico confirmado en la valoración clínica', 200.00, 1, '2026-04-29', '2026-04-29', 'concluido', 'Tratamiento concluido'],
            [36, 20, 3, 2, 37, 1, 'Diagnóstico confirmado en la valoración clínica', 200.00, 1, '2026-05-13', '2026-05-13', 'concluido', 'Tratamiento concluido'],
            [37, 21, 1, 2, null, 10, 'Diagnóstico confirmado en la valoración clínica', 150.00, 1, '2026-05-06', '2026-05-06', 'concluido', 'Tratamiento concluido'],
            [38, 21, 8, 2, null, 10, 'Diagnóstico confirmado en la valoración clínica', 700.00, 2, '2026-05-27', '2026-06-10', 'concluido', 'Tratamiento concluido'],
            [39, 22, 11, 1, 18, 4, 'Diagnóstico confirmado en la valoración clínica', 250.00, 1, '2026-05-27', '2026-05-27', 'concluido', 'Tratamiento concluido'],
            [40, 22, 1, 1, null, 4, 'Diagnóstico confirmado en la valoración clínica', 150.00, 1, '2026-07-01', '2026-07-01', 'concluido', 'Tratamiento concluido'],
            [41, 23, 9, 1, null, 12, 'Diagnóstico confirmado en la valoración clínica', 100.00, 1, '2026-06-17', '2026-06-17', 'concluido', 'Tratamiento concluido'],
            [42, 23, 13, 1, 36, 12, 'Diagnóstico confirmado en la valoración clínica', 120.00, 1, '2026-07-08', '2026-07-08', 'concluido', 'Tratamiento concluido'],
            [43, 24, 10, 2, 21, 9, 'Diagnóstico confirmado en la valoración clínica', 1200.00, 2, '2026-07-08', '2026-07-22', 'concluido', 'Tratamiento concluido'],
            [44, 24, 10, 2, 12, 9, 'Diagnóstico confirmado en la valoración clínica', 1200.00, 2, '2026-08-05', null, 'en proceso', 'Avance de 1 de 2 sesiones'],
            [45, 25, 2, 2, 36, 2, 'Tratamiento acordado con el paciente, pendiente de iniciar', 300.00, 3, '2026-09-14', null, 'planificado', 'Pendiente de cargar el estudio previo requerido'],
            [46, 26, 7, 2, 36, 7, 'Tratamiento acordado con el paciente, pendiente de iniciar', 4500.00, 2, '2026-09-21', null, 'planificado', 'Pendiente de cargar el estudio previo requerido'],
            [47, 27, 4, 1, 21, 9, 'Tratamiento acordado con el paciente, pendiente de iniciar', 900.00, 2, '2026-09-28', null, 'planificado', 'Pendiente de cargar el estudio previo requerido'],
            [48, 28, 15, 2, null, 7, 'Tratamiento acordado con el paciente, pendiente de iniciar', 1800.00, 4, '2026-10-05', null, 'planificado', 'Pendiente de cargar el estudio previo requerido'],
        ];
    }
}
