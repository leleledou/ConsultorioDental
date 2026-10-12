<?php

use App\Models\Especialidad;
use App\Models\Paciente;
use App\Models\Tratamiento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Las pruebas de tests/Feature (Base y Transaccionales) usan la base MySQL de
| pruebas (dentalrox_test, ver phpunit.xml) y la reinician con RefreshDatabase.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Funciones de apoyo
|--------------------------------------------------------------------------
|
| Crean los datos mínimos de las pruebas, sin depender de los datos de
| desarrollo.
|
*/

// Usuario activo con el rol indicado ('administrador' u 'odontologo').
function crearUsuario(string $rol = 'odontologo', array $datos = []): User
{
    return User::factory()->create(['rol' => $rol] + $datos);
}

// Paciente activo con datos válidos; $datos reemplaza los valores por defecto.
function crearPaciente(array $datos = []): Paciente
{
    static $numero = 0;
    $numero++;

    return Paciente::create($datos + [
        'ci' => (string) (7000000 + $numero),
        'nombres' => "Paciente{$numero}",
        'apellidos' => 'Prueba',
        'fecha_nacimiento' => '1990-01-01',
        'sexo' => 'F',
        'estado' => 'activo',
        'fecha_registro' => today(),
    ]);
}

// Tratamiento disponible del catálogo; crea la especialidad si no se indica.
function crearTratamiento(array $datos = []): Tratamiento
{
    static $numero = 0;
    $numero++;

    return Tratamiento::create($datos + [
        'nombre' => "Tratamiento {$numero}",
        'especialidad_id' => Especialidad::factory()->create()->id,
        'duracion_estimada_minutos' => 60,
        'numero_sesiones_previstas' => 1,
        'requiere_sesiones' => false,
        'estado' => 'disponible',
    ]);
}
