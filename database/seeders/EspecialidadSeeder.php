<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    /**
     * Carga el catálogo de especialidades definido en el documento de diseño.
     * El id no se escribe: lo asigna la base de datos (autoincremental).
     */
    public function run(): void
    {
        Especialidad::create([
            'nombre' => 'Ortodoncia',
            'descripcion' => 'Corrección de la posición de los dientes y los maxilares',
        ]);

        Especialidad::create([
            'nombre' => 'Endodoncia',
            'descripcion' => 'Tratamiento de los conductos radiculares',
        ]);

        Especialidad::create([
            'nombre' => 'Estética dental',
            'descripcion' => 'Restauraciones estéticas, carillas y blanqueamiento',
        ]);

        Especialidad::create([
            'nombre' => 'Odontopediatría',
            'descripcion' => 'Atención odontológica de niños y adolescentes',
        ]);

        Especialidad::create([
            'nombre' => 'Cirugía',
            'descripcion' => 'Extracciones y procedimientos quirúrgicos menores',
        ]);

        Especialidad::create([
            'nombre' => 'Periodoncia',
            'descripcion' => 'Tratamiento de encías y tejidos de soporte',
        ]);

        Especialidad::create([
            'nombre' => 'Implantología',
            'descripcion' => 'Colocación y rehabilitación sobre implantes',
        ]);
    }
}
