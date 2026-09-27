<?php

namespace Database\Seeders;

use App\Models\HistoriaClinica;
use App\Models\Paciente;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    /**
     * Crea 8 pacientes de prueba, cada uno con su historia clínica
     * (igual que CU08: al registrar un paciente se abre su historia).
     *
     * Hay niños, adultos y adultos mayores de ambos sexos, algunos sin
     * teléfono o correo (son opcionales) y un paciente inhabilitado, para
     * poder probar el buscador (CU09), la edad calculada y habilitar/inhabilitar.
     */
    public function run(): void
    {
        $pacientes = [
            [
                'ci' => '6123456', 'nombres' => 'María Fernanda', 'apellidos' => 'Quispe Mamani',
                'fecha_nacimiento' => '1990-03-14', 'sexo' => 'F', 'telefono' => '71234567',
                'correo' => 'maria.quispe@gmail.com', 'direccion' => 'Av. Busch 1450, Santa Cruz',
                'observaciones' => 'Alérgica a la penicilina.', 'estado' => 'activo', 'fecha_registro' => '2026-02-03',
            ],
            [
                'ci' => '5987321', 'nombres' => 'Juan Carlos', 'apellidos' => 'Rojas Vaca',
                'fecha_nacimiento' => '1978-11-02', 'sexo' => 'M', 'telefono' => '76543210',
                'correo' => 'jc.rojas@hotmail.com', 'direccion' => 'Calle Sucre 233, Santa Cruz',
                'observaciones' => 'Hipertenso controlado.', 'estado' => 'activo', 'fecha_registro' => '2026-02-10',
            ],
            [
                'ci' => '13456789', 'nombres' => 'Sofía', 'apellidos' => 'Gutiérrez Flores',
                'fecha_nacimiento' => '2016-07-21', 'sexo' => 'F', 'telefono' => '72011334',
                'correo' => null, 'direccion' => 'Barrio Equipetrol, calle 8 n.º 17',
                'observaciones' => 'Paciente pediátrica; asiste con su madre.', 'estado' => 'activo', 'fecha_registro' => '2026-03-05',
            ],
            [
                'ci' => '4561237', 'nombres' => 'Luis Alberto', 'apellidos' => 'Mendoza Suárez',
                'fecha_nacimiento' => '1955-01-30', 'sexo' => 'M', 'telefono' => '70998877',
                'correo' => null, 'direccion' => 'Av. Alemana 3er anillo, Santa Cruz',
                'observaciones' => 'Diabetes tipo 2. Usa prótesis parcial superior.', 'estado' => 'activo', 'fecha_registro' => '2026-03-18',
            ],
            [
                'ci' => '7894561', 'nombres' => 'Carla Andrea', 'apellidos' => 'Vargas Justiniano',
                'fecha_nacimiento' => '1998-09-09', 'sexo' => 'F', 'telefono' => '78123456',
                'correo' => 'carla.vargas@gmail.com', 'direccion' => 'Urb. Las Palmas, casa 42',
                'observaciones' => null, 'estado' => 'activo', 'fecha_registro' => '2026-04-07',
            ],
            [
                'ci' => '8321654', 'nombres' => 'Diego', 'apellidos' => 'Céspedes Rivero',
                'fecha_nacimiento' => '2009-12-15', 'sexo' => 'M', 'telefono' => '73345566',
                'correo' => 'diego.cespedes@gmail.com', 'direccion' => 'Calle Warnes 510, Santa Cruz',
                'observaciones' => 'Evaluación para ortodoncia.', 'estado' => 'activo', 'fecha_registro' => '2026-05-12',
            ],
            [
                'ci' => '3219876', 'nombres' => 'Rosa Elena', 'apellidos' => 'Choque Condori',
                'fecha_nacimiento' => '1968-05-27', 'sexo' => 'F', 'telefono' => null,
                'correo' => null, 'direccion' => 'Plan 3000, UV 160',
                'observaciones' => null, 'estado' => 'activo', 'fecha_registro' => '2026-06-20',
            ],
            [
                // Paciente inhabilitado: no se borra; se conserva con su historia clínica.
                'ci' => '9654123', 'nombres' => 'Marco Antonio', 'apellidos' => 'Paz Salvatierra',
                'fecha_nacimiento' => '1985-08-03', 'sexo' => 'M', 'telefono' => '77665544',
                'correo' => 'marco.paz@yahoo.com', 'direccion' => 'Av. Banzer 4to anillo',
                'observaciones' => 'Se mudó de ciudad.', 'estado' => 'inactivo', 'fecha_registro' => '2026-02-25',
            ],
        ];

        foreach ($pacientes as $datos) {
            $paciente = Paciente::create($datos);

            // La historia clínica se abre el mismo día en que se registró al paciente.
            HistoriaClinica::create([
                'paciente_id' => $paciente->id,
                'fecha_apertura' => $datos['fecha_registro'],
            ]);
        }
    }
}
