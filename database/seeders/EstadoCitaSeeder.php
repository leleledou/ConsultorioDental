<?php

namespace Database\Seeders;

use App\Models\EstadoCita;
use Illuminate\Database\Seeder;

class EstadoCitaSeeder extends Seeder
{
    /**
     * Carga el catálogo de estados de cita (CU10 a CU13).
     * Los nombres son fijos: el código los busca por nombre (constantes de EstadoCita).
     */
    public function run(): void
    {
        EstadoCita::create([
            'nombre' => EstadoCita::PROGRAMADA,
            'descripcion' => 'Cita reservada; ocupa el sillón en su horario.',
        ]);

        EstadoCita::create([
            'nombre' => EstadoCita::ATENDIDA,
            'descripcion' => 'El paciente asistió y fue atendido.',
        ]);

        EstadoCita::create([
            'nombre' => EstadoCita::CANCELADA,
            'descripcion' => 'Cita anulada o reprogramada; su horario queda libre.',
        ]);

        EstadoCita::create([
            'nombre' => EstadoCita::NO_ASISTIDA,
            'descripcion' => 'El paciente no se presentó a la cita.',
        ]);
    }
}
