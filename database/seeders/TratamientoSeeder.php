<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\Tratamiento;
use Illuminate\Database\Seeder;

class TratamientoSeeder extends Seeder
{
    /**
     * Carga el catálogo inicial de tratamientos (CU15, RF-25).
     * La duración estimada es la que se propone al programar una cita (RN-08).
     * Hay un tratamiento dado de baja para probar que no se ofrece en CU10.
     */
    public function run(): void
    {
        $tratamientos = [
            ['Endodoncia unirradicular', 'Endodoncia', 90, 2, true,
                'Tratamiento de conducto de una pieza con una sola raíz.', 'disponible'],
            ['Extracción simple', 'Cirugía', 45, 1, false,
                'Extracción de una pieza erupcionada sin colgajo.', 'disponible'],
            ['Implante dental', 'Implantología', 120, 3, true,
                'Colocación del implante, segunda fase y corona sobre implante.', 'disponible'],
            ['Profilaxis y limpieza', 'Periodoncia', 40, 1, false,
                'Remoción de placa y sarro con pulido dental.', 'disponible'],
            ['Control odontopediátrico', 'Odontopediatría', 30, 1, false,
                'Revisión periódica, aplicación de flúor y educación en higiene.', 'disponible'],
            ['Restauración con resina', 'Estética dental', 60, 1, false,
                'Obturación estética con resina compuesta.', 'disponible'],
            ['Ortodoncia con brackets metálicos', 'Ortodoncia', 60, 24, true,
                'Instalación de brackets y controles mensuales.', 'disponible'],
            ['Blanqueamiento con lámpara LED', 'Estética dental', 75, 2, true,
                'Blanqueamiento en consultorio. Dado de baja hasta reponer el equipo.', 'no disponible'],
        ];

        foreach ($tratamientos as [$nombre, $especialidad, $duracion, $sesiones, $requiereSesiones, $descripcion, $estado]) {
            Tratamiento::create([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                // La especialidad se busca por nombre, nunca con un id escrito a mano.
                'especialidad_id' => Especialidad::where('nombre', $especialidad)->firstOrFail()->id,
                'duracion_estimada_minutos' => $duracion,
                'numero_sesiones_previstas' => $sesiones,
                'requiere_sesiones' => $requiereSesiones,
                'estado' => $estado,
            ]);
        }
    }
}
