<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\InsertaFilas;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    use InsertaFilas;

    /**
     * Pacientes del consultorio: niños, adolescentes, adultos y adultos mayores de
     * ambos sexos, algunos sin correo (es opcional). Uno está inhabilitado para
     * probar habilitar/inhabilitar (CU08). La historia clínica de cada uno la crea
     * HistoriaClinicaSeeder.
     *
     * 24 registros de database/seeders/referencia/poblacion_referencia.sql
     * (tabla de referencia: paciente). Se conservan los id de la referencia.
     */
    public function run(): void
    {
        $this->insertarFilas('pacientes', $this->columnas(), $this->filas());
    }

    /**
     * Columnas de la tabla pacientes, en el orden de cada fila.
     *
     * @return list<string>
     */
    private function columnas(): array
    {
        return ['id', 'ci', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo', 'telefono', 'correo', 'direccion', 'observaciones', 'estado', 'fecha_registro'];
    }

    /**
     * Filas generadas a partir del archivo de referencia (no editar a mano).
     *
     * @return list<list<mixed>>
     */
    private function filas(): array
    {
        return [
            [1, '7845213', 'Ana Lucía', 'Vargas Suárez', '1990-03-14', 'F', '70123456', 'ana.vargas@correo.com', 'Barrio Equipetrol, calle Los Cusis N.º 145', 'Refiere sensibilidad al frío', 'activo', '2026-02-03'],
            [2, '6512478', 'Carlos Andrés', 'Rojas Mendoza', '1985-11-02', 'M', '71234567', 'carlos.rojas@correo.com', 'Av. Banzer, 4.º anillo N.º 820', 'Fumador ocasional', 'activo', '2026-07-28'],
            [3, '5987412', 'María Elena', 'Ortiz Gutiérrez', '1978-06-25', 'F', '72345678', 'melena.ortiz@correo.com', 'Barrio Urbarí, calle Charcas N.º 67', 'Solicita tratamiento estético del sector anterior', 'activo', '2026-05-12'],
            [4, '8123457', 'José Antonio', 'Peña Salvatierra', '1996-01-30', 'M', '73456789', 'jose.pena@correo.com', 'Plan 3000, UV 180, manzana 22', 'Sin observaciones', 'activo', '2026-03-28'],
            [5, '9874561', 'Daniela', 'Fernández Áñez', '2009-09-12', 'F', '74567890', 'daniela.fernandez@correo.com', 'Av. Alemana, 3.er anillo N.º 512', 'Acude acompañada por su madre', 'activo', '2026-02-26'],
            [6, '10254789', 'Mateo', 'Ribera Justiniano', '2018-04-05', 'M', '75678901', null, 'Barrio Las Palmas, calle Pitajaya N.º 33', 'Paciente pediátrico, dentición mixta', 'activo', '2026-07-10'],
            [7, '4123698', 'Silvia', 'Cuéllar Montaño', '1965-02-18', 'F', '76789012', 'silvia.cuellar@correo.com', 'Av. Irala N.º 229', 'Diabética controlada, requiere control de cicatrización', 'activo', '2026-04-15'],
            // Inhabilitado (ajuste a la referencia): no tiene planes de tratamiento ni citas futuras.
            [8, '8965321', 'Rodrigo', 'Terceros Egüez', '2001-07-21', 'M', '77890123', 'rodrigo.terceros@correo.com', 'Barrio Hamacas, calle 3 N.º 18', 'Primera consulta', 'inactivo', '2026-08-24'],
            [9, '7712340', 'Lucía Fernanda', 'Melgar Paz', '1992-05-08', 'F', '70211334', 'lucia.melgar@correo.com', 'Barrio Sirari, calle Los Tajibos N.º 45', 'Refiere sangrado de encías', 'activo', '2025-10-06'],
            [10, '5423891', 'Óscar Javier', 'Padilla Roca', '1974-09-19', 'M', '70322445', 'oscar.padilla@correo.com', 'Av. Cristo Redentor, 5.º anillo N.º 90', 'Bruxismo marcado', 'activo', '2025-10-14'],
            [11, '9013452', 'Camila', 'Sandoval Vaca', '2011-03-27', 'F', '70433556', 'camila.sandoval@correo.com', 'Barrio Polanco, calle 5 N.º 12', 'Paciente adolescente, control de erupción', 'activo', '2025-11-03'],
            [12, '6634512', 'Marcelo', 'Arteaga Chávez', '1988-12-11', 'M', '70544667', 'marcelo.arteaga@correo.com', 'Av. Paraguá, 4.º anillo N.º 233', 'Consulta por dolor en molar inferior', 'activo', '2025-11-18'],
            [13, '4498123', 'Gladys', 'Roca Zambrana', '1959-07-04', 'F', '70655778', 'gladys.roca@correo.com', 'Barrio Guaracal, calle 8 N.º 71', 'Portadora de prótesis parcial', 'activo', '2025-12-02'],
            [14, '8845201', 'Iván', 'Suárez Peredo', '1999-02-23', 'M', '70766889', 'ivan.suarez@correo.com', 'Villa 1.º de Mayo, UV 90 manzana 5', 'Deportista, refiere trauma dental', 'activo', '2026-01-12'],
            [15, '7156430', 'Rosario', 'Guzmán Ledezma', '1983-10-30', 'F', '70877990', 'rosario.guzman@correo.com', 'Barrio Petrolero, calle Los Sauces N.º 19', 'Solicita blanqueamiento', 'activo', '2026-01-26'],
            [16, '10334567', 'Emiliano', 'Vaca Áñez', '2019-06-15', 'M', '70988101', null, 'Barrio Hamacas, calle 12 N.º 8', 'Paciente pediátrico, primera consulta', 'activo', '2026-02-09'],
            [17, '6721098', 'Patricia', 'Nogales Ibáñez', '1980-04-17', 'F', '71099212', 'patricia.nogales@correo.com', 'Av. Santos Dumont, 6.º anillo N.º 340', 'Antecedente de periodontitis', 'activo', '2026-02-23'],
            [18, '5310987', 'Hernán', 'Céspedes Rivero', '1968-08-09', 'M', '71100323', 'hernan.cespedes@correo.com', 'Barrio Los Choferes, calle 3 N.º 55', 'Pérdida de piezas posteriores', 'activo', '2026-03-09'],
            [19, '9922341', 'Valeria', 'Ortuño Salazar', '2007-11-21', 'F', '71211434', 'valeria.ortuno@correo.com', 'Av. Beni, 4.º anillo N.º 128', 'Interesada en ortodoncia', 'activo', '2026-03-23'],
            [20, '8123098', 'Diego', 'Mendoza Flores', '1995-01-05', 'M', '71322545', 'diego.mendoza@correo.com', 'Barrio Fátima, calle 2 N.º 63', 'Higiene deficiente', 'activo', '2026-04-13'],
            [21, '7009812', 'Andrea', 'Justiniano Roda', '1990-09-28', 'F', '71433656', 'andrea.justiniano@correo.com', 'Av. Piraí, 3.er anillo N.º 205', 'Paciente derivada por su hermana', 'activo', '2026-05-04'],
            [22, '4876520', 'Ramiro', 'Salvatierra Cuéllar', '1962-03-12', 'M', '71544767', 'ramiro.salvatierra@correo.com', 'Barrio Cañoto, calle Vallegrande N.º 30', 'Hipertenso controlado', 'activo', '2026-05-25'],
            [23, '10112233', 'Sofía', 'Balcázar Mérida', '2017-10-02', 'F', '71655878', null, 'Barrio Las Palmas, calle Toborochi N.º 17', 'Paciente pediátrica, dentición mixta', 'activo', '2026-06-15'],
            [24, '6598741', 'Gonzalo', 'Terrazas Vargas', '1986-06-24', 'M', '71766989', 'gonzalo.terrazas@correo.com', 'Av. Grigotá, 5.º anillo N.º 412', 'Consulta por estética del sector anterior', 'activo', '2026-07-06'],
        ];
    }
}
