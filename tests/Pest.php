<?php

use App\Models\AntecedenteMedico;
use App\Models\Cita;
use App\Models\Diagnostico;
use App\Models\Especialidad;
use App\Models\EstadoCita;
use App\Models\EstadoPieza;
use App\Models\Material;
use App\Models\MetodoPago;
use App\Models\Paciente;
use App\Models\PiezaDental;
use App\Models\TipoEstudio;
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

// Cita programada para mañana de 09:00 a 10:00. Con 'estado' se elige otro estado
// (requiere EstadoCitaSeeder). Se recarga para leer hora_fin, calculada por la base.
function crearCita(array $datos = []): Cita
{
    $estado = $datos['estado'] ?? EstadoCita::PROGRAMADA;
    unset($datos['estado']);

    return Cita::create($datos + [
        'paciente_id' => crearPaciente()->id,
        'odontologo_id' => crearUsuario()->id,
        'fecha' => today()->addDay()->toDateString(),
        'hora_inicio' => '09:00',
        'duracion_minutos' => 60,
        'motivo' => 'Control',
        'estado_cita_id' => EstadoCita::idDe($estado),
        'fecha_registro' => today(),
    ])->refresh();
}

// Diagnóstico activo del catálogo; $datos reemplaza los valores por defecto.
function crearDiagnostico(array $datos = []): Diagnostico
{
    static $numero = 0;
    $numero++;

    return Diagnostico::create($datos + [
        'codigo' => "D{$numero}",
        'nombre' => "Diagnóstico {$numero}",
        'estado' => 'activo',
    ]);
}

// Tipo de estudio activo del catálogo; $datos reemplaza los valores por defecto.
function crearTipoEstudio(array $datos = []): TipoEstudio
{
    static $numero = 0;
    $numero++;

    return TipoEstudio::create($datos + [
        'nombre' => "Tipo de estudio {$numero}",
        'estado' => 'activo',
    ]);
}

// Material activo del catálogo; $datos reemplaza los valores por defecto.
function crearMaterial(array $datos = []): Material
{
    static $numero = 0;
    $numero++;

    return Material::create($datos + [
        'nombre' => "Material {$numero}",
        'categoria' => 'restaurador',
        'unidad_medida' => 'unidad',
        'estado' => 'activo',
    ]);
}

// Antecedente médico del catálogo; $datos reemplaza los valores por defecto.
function crearAntecedente(array $datos = []): AntecedenteMedico
{
    static $numero = 0;
    $numero++;

    return AntecedenteMedico::create($datos + ['nombre' => "Antecedente {$numero}"]);
}

// Estado de pieza activo del catálogo; $datos reemplaza los valores por defecto.
function crearEstadoPieza(array $datos = []): EstadoPieza
{
    static $numero = 0;
    $numero++;

    return EstadoPieza::create($datos + [
        'codigo' => "E{$numero}",
        'nombre' => "Estado {$numero}",
        'estado' => 'activo',
    ]);
}

// Pieza dental con ese código FDI (el id de la tabla es el mismo código).
function crearPiezaDental(int $fdi): PiezaDental
{
    return PiezaDental::create([
        'id' => $fdi,
        'codigo_fdi' => $fdi,
        'nombre' => "Pieza {$fdi}",
        'cuadrante' => intdiv($fdi, 10),
        'tipo_pieza' => 'molar',
        'tipo_denticion' => 'permanente',
    ]);
}

// Método de pago activo del catálogo; $datos reemplaza los valores por defecto.
function crearMetodoPago(array $datos = []): MetodoPago
{
    static $numero = 0;
    $numero++;

    return MetodoPago::create($datos + [
        'nombre' => "Método {$numero}",
        'estado' => 'activo',
    ]);
}
