<?php

namespace Database\Factories;

use App\Models\Especialidad;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName(),
            'ci' => (string) fake()->unique()->numberBetween(1000000, 99999999),
            'matricula_profesional' => 'MP-'.fake()->numerify('######'),
            'especialidad_id' => Especialidad::factory(),
            'correo' => fake()->unique()->safeEmail(),
            // Entre 4 y 30 caracteres, sin espacios: "user" + 6 dígitos únicos = 10 caracteres.
            'usuario' => 'user'.fake()->unique()->numerify('######'),
            'password' => static::$password ??= Hash::make('password'),
            'telefono' => fake()->numerify('7#######'),
            'rol' => 'odontologo',
            'estado' => 'activo',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
            'debe_cambiar_contrasena' => false,
            'remember_token' => Str::random(10),
        ];
    }
}
