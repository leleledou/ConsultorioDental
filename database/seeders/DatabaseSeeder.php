<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Carga los datos mínimos del sistema.
     *
     * El orden importa por las llaves foráneas: users y tratamientos necesitan
     * las especialidades.
     *
     * Ejecutar siempre con: php artisan migrate:fresh --seed
     * (los seeders usan create(); ejecutarlos dos veces sobre la misma base
     * falla por datos duplicados en columnas unique).
     */
    public function run(): void
    {
        $this->call([
            EspecialidadSeeder::class,
            UserSeeder::class,
            PacienteSeeder::class,
            TratamientoSeeder::class,
        ]);
    }
}
