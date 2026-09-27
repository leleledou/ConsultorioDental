<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Crea las cuentas de los dos odontólogos del consultorio
     * (poblado del documento de diseño, Capítulo 4).
     *
     * No se escriben intentos_fallidos, bloqueado_hasta ni debe_cambiar_contrasena:
     * toman sus valores por defecto de la migración (0, null y false).
     */
    public function run(): void
    {
        // Las especialidades se buscan por nombre, nunca con un id escrito a mano.
        // firstOrFail() lanza un error claro si la especialidad no existe
        // (por ejemplo, si no se ejecutó antes EspecialidadSeeder).
        $ortodoncia = Especialidad::where('nombre', 'Ortodoncia')->firstOrFail();
        $esteticaDental = Especialidad::where('nombre', 'Estética dental')->firstOrFail();

        // Administradora del sistema.
        User::create([
            'nombres' => 'Roxana',
            'apellidos' => 'Calizaya',
            'ci' => '3456789',
            'matricula_profesional' => 'MP-2451',
            'especialidad_id' => $ortodoncia->id,
            'correo' => 'roxana.calizaya@dentalrox.com',
            'usuario' => 'rcalizaya',
            // RN-01: la contraseña se guarda solo cifrada (bcrypt) con Hash::make.
            // El cast 'hashed' del modelo User detecta que ya está cifrada y no la vuelve a cifrar.
            'password' => Hash::make('Ortodoncia#2026'),
            'telefono' => '70011223',
            'rol' => 'administrador',
            'estado' => 'activo',
        ]);

        // Odontólogo sin rol de administrador.
        User::create([
            'nombres' => 'Gerson',
            'apellidos' => 'Callejas',
            'ci' => '3987654',
            'matricula_profesional' => 'MP-2678',
            'especialidad_id' => $esteticaDental->id,
            'correo' => 'gerson.callejas@dentalrox.com',
            'usuario' => 'gcallejas',
            'password' => Hash::make('Estetica#2026'),
            'telefono' => '70044556',
            'rol' => 'odontologo',
            'estado' => 'activo',
        ]);
    }
}
