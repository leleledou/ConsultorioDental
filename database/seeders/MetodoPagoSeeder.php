<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class MetodoPagoSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Catálogo de métodos de pago aceptados en caja.
     *
     * 4 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: metodo_pago). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('metodos_pago', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla metodos_pago, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'nombre', 'descripcion', 'estado'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, 'Efectivo', 'Cobro en efectivo en el consultorio', 'activo'],
            [2, 'QR', 'Pago mediante código QR bancario', 'activo'],
            [3, 'Transferencia bancaria', 'Depósito o transferencia a la cuenta del consultorio', 'activo'],
            [4, 'Tarjeta de débito', 'Cobro con tarjeta mediante datáfono', 'activo'],
        ];
    }
}
