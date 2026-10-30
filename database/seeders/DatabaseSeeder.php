<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga la población completa del sistema (las 29 tablas), a partir de
     * database/seeders/referencia/poblacion_referencia.sql.
     *
     * El orden importa por las llaves foráneas: cada seeder va después de los de
     * las tablas a las que apunta. No hay llaves circulares: citas apunta a
     * tratamientos_aplicados y sesiones apunta a citas, así que el orden es
     * tratamientos aplicados -> citas -> sesiones -> materiales de cada sesión.
     *
     * Ejecutar siempre con: php artisan migrate:fresh --seed
     * (los seeders escriben los id de la referencia; ejecutarlos dos veces sobre
     * la misma base falla por llaves primarias y columnas unique duplicadas).
     */
    public function run(): void
    {
        $this->call([
            // Catálogos sin llaves foráneas.
            EspecialidadSeeder::class,
            AntecedenteMedicoSeeder::class,
            PiezaDentalSeeder::class,
            EstadoPiezaSeeder::class,
            DiagnosticoSeeder::class,
            TipoEstudioSeeder::class,
            MaterialSeeder::class,
            EstadoCitaSeeder::class,
            MetodoPagoSeeder::class,
            PacienteSeeder::class,

            // Necesitan especialidades.
            UserSeeder::class,
            TratamientoSeeder::class,

            // Necesitan pacientes, tratamientos y catálogos.
            HistoriaClinicaSeeder::class,
            TarifaSeeder::class,
            TratamientoTipoEstudioSeeder::class,
            PlanTratamientoSeeder::class,
            HistoriaAntecedenteSeeder::class,
            OdontogramaSeeder::class,

            // Necesitan planes de tratamiento.
            TratamientoAplicadoSeeder::class,

            // Necesitan tratamientos aplicados.
            DetalleOdontogramaSeeder::class,
            CitaSeeder::class,
            EstudioDiagnosticoSeeder::class,
            PagoSeeder::class,
            ControlSeeder::class,

            // Necesitan citas o estudios.
            SesionSeeder::class,
            ValoracionImplanteSeeder::class,

            // Necesitan sesiones.
            SesionMaterialSeeder::class,

            // Solo apuntan a users (referencia_id y registro_id no tienen llave foránea).
            AvisoSeeder::class,
            BitacoraSeeder::class,

            // Al final: citas con fechas relativas a hoy, que no se cruzan con las anteriores.
            AgendaActualSeeder::class,
        ]);
    }
}
