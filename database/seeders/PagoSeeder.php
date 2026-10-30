<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Abonos de cada tratamiento aplicado. Todos quedan 'vigente' (valor por defecto
     * de la columna); ninguno supera el costo acordado de su tratamiento.
     *
     * 62 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: pago). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('pagos', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla pagos, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'tratamiento_aplicado_id', 'metodo_pago_id', 'odontologo_id', 'monto', 'fecha_pago', 'numero_comprobante', 'observaciones'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 1, 1, 2, 135.00, '2026-02-05', 'REC-0001', 'Cancelación total de la limpieza Dato ficticio ajustado al importe neto del paquete.'],
            [2, 2, 2, 2, 180.00, '2026-02-12', 'REC-0002', 'Cancelación total de la obturación Dato ficticio ajustado al importe neto del paquete.'],
            [3, 3, 1, 2, 100.00, '2026-06-15', 'REC-0003', 'Abono inicial de la endodoncia'],
            [4, 4, 1, 1, 500.00, '2026-03-10', 'REC-0004', 'Primera cuota de ortodoncia'],
            [5, 4, 2, 1, 500.00, '2026-04-10', 'REC-0005', 'Segunda cuota de ortodoncia'],
            [6, 4, 2, 1, 500.00, '2026-05-12', 'REC-0006', 'Tercera cuota de ortodoncia'],
            [7, 4, 1, 1, 500.00, '2026-06-11', 'REC-0007', 'Cuarta cuota de ortodoncia'],
            [8, 4, 3, 1, 500.00, '2026-07-09', 'REC-0008', 'Quinta cuota de ortodoncia'],
            [9, 4, 2, 1, 500.00, '2026-08-11', 'REC-0009', 'Sexta cuota de ortodoncia'],
            [10, 5, 3, 2, 2000.00, '2026-05-05', 'REC-0010', 'Abono inicial del implante'],
            [11, 5, 2, 2, 1000.00, '2026-06-05', 'REC-0011', 'Segundo abono del implante'],
            [12, 7, 1, 1, 100.00, '2026-07-14', 'REC-0012', 'Cancelación de la aplicación de flúor'],
            [13, 8, 1, 1, 200.00, '2026-07-21', 'REC-0013', 'Cancelación de la obturación'],
            [14, 10, 2, 2, 350.00, '2026-05-20', 'REC-0014', 'Primer abono del blanqueamiento'],
            [15, 10, 1, 2, 350.00, '2026-06-03', 'REC-0015', 'Cancelación del blanqueamiento'],
            [16, 11, 2, 2, 600.00, '2026-08-14', 'REC-0016', 'Abono inicial de la carilla'],
            [17, 13, 2, 2, 350.00, '2025-10-08', 'REC-0017', 'Abono registrado en caja'],
            [18, 14, 3, 2, 180.00, '2025-11-05', 'REC-0018', 'Abono registrado en caja'],
            [19, 15, 4, 1, 400.00, '2025-10-16', 'REC-0019', 'Abono registrado en caja'],
            [20, 16, 1, 1, 450.00, '2026-01-15', 'REC-0020', 'Abono registrado en caja'],
            [21, 16, 2, 1, 450.00, '2026-02-05', 'REC-0021', 'Abono registrado en caja'],
            [22, 17, 3, 1, 100.00, '2025-11-05', 'REC-0022', 'Abono registrado en caja'],
            [23, 18, 4, 1, 130.00, '2025-11-05', 'REC-0023', 'Abono registrado en caja'],
            [24, 19, 1, 2, 400.00, '2025-11-20', 'REC-0024', 'Abono registrado en caja'],
            [25, 20, 2, 2, 450.00, '2026-02-10', 'REC-0025', 'Abono registrado en caja'],
            [26, 20, 3, 2, 450.00, '2026-03-03', 'REC-0026', 'Abono registrado en caja'],
            [27, 21, 4, 2, 220.00, '2025-12-04', 'REC-0027', 'Abono registrado en caja'],
            [28, 22, 1, 2, 450.00, '2026-01-08', 'REC-0028', 'Abono registrado en caja'],
            [29, 22, 2, 2, 450.00, '2026-01-29', 'REC-0029', 'Abono registrado en caja'],
            [30, 22, 3, 2, 450.00, '2026-02-19', 'REC-0030', 'Abono registrado en caja'],
            [31, 23, 4, 2, 200.00, '2026-01-14', 'REC-0031', 'Abono registrado en caja'],
            [32, 24, 1, 2, 600.00, '2026-02-04', 'REC-0032', 'Abono registrado en caja'],
            [33, 24, 2, 2, 600.00, '2026-02-25', 'REC-0033', 'Abono registrado en caja'],
            [34, 25, 3, 2, 150.00, '2026-01-28', 'REC-0034', 'Abono registrado en caja'],
            [35, 26, 4, 2, 350.00, '2026-02-18', 'REC-0035', 'Abono registrado en caja'],
            [36, 26, 1, 2, 350.00, '2026-03-11', 'REC-0036', 'Abono registrado en caja'],
            [37, 27, 2, 1, 100.00, '2026-02-11', 'REC-0037', 'Abono registrado en caja'],
            [38, 28, 3, 1, 120.00, '2026-03-04', 'REC-0038', 'Abono registrado en caja'],
            [39, 29, 4, 2, 400.00, '2026-02-25', 'REC-0039', 'Abono registrado en caja'],
            [40, 30, 1, 2, 150.00, '2026-06-10', 'REC-0040', 'Abono registrado en caja'],
            [41, 31, 2, 2, 250.00, '2026-03-11', 'REC-0041', 'Abono registrado en caja'],
            [42, 32, 3, 2, 2250.00, '2026-04-15', 'REC-0042', 'Abono registrado en caja'],
            [43, 32, 4, 2, 2250.00, '2026-05-06', 'REC-0043', 'Abono registrado en caja'],
            [44, 33, 1, 1, 140.00, '2026-03-25', 'REC-0044', 'Abono registrado en caja'],
            [45, 33, 2, 1, 140.00, '2026-04-15', 'REC-0045', 'Abono registrado en caja'],
            [46, 33, 3, 1, 140.00, '2026-05-06', 'REC-0046', 'Abono registrado en caja'],
            [47, 33, 4, 1, 140.00, '2026-05-27', 'REC-0047', 'Abono registrado en caja'],
            [48, 33, 1, 1, 140.00, '2026-06-17', 'REC-0048', 'Abono registrado en caja'],
            [49, 33, 2, 1, 140.00, '2026-07-08', 'REC-0049', 'Abono registrado en caja'],
            [50, 34, 3, 2, 150.00, '2026-04-15', 'REC-0050', 'Abono registrado en caja'],
            [51, 35, 4, 2, 200.00, '2026-04-29', 'REC-0051', 'Abono registrado en caja'],
            [52, 36, 1, 2, 200.00, '2026-05-13', 'REC-0052', 'Abono registrado en caja'],
            [53, 37, 2, 2, 150.00, '2026-05-06', 'REC-0053', 'Abono registrado en caja'],
            [54, 38, 3, 2, 350.00, '2026-05-27', 'REC-0054', 'Abono registrado en caja'],
            [55, 38, 4, 2, 350.00, '2026-06-17', 'REC-0055', 'Abono registrado en caja'],
            [56, 39, 1, 1, 250.00, '2026-05-27', 'REC-0056', 'Abono registrado en caja'],
            [57, 40, 2, 1, 150.00, '2026-07-01', 'REC-0057', 'Abono registrado en caja'],
            [58, 41, 3, 1, 100.00, '2026-06-17', 'REC-0058', 'Abono registrado en caja'],
            [59, 42, 4, 1, 120.00, '2026-07-08', 'REC-0059', 'Abono registrado en caja'],
            [60, 43, 1, 2, 600.00, '2026-07-08', 'REC-0060', 'Abono registrado en caja'],
            [61, 43, 2, 2, 600.00, '2026-07-29', 'REC-0061', 'Abono registrado en caja'],
            [62, 44, 3, 2, 600.00, '2026-08-05', 'REC-0062', 'Abono registrado en caja'],
        ];
    }
}
