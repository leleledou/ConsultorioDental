<?php

namespace Database\Factories;

use App\Models\Especialidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Especialidad>
 */
class EspecialidadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Máximo 40 caracteres (columna string(40)).
            'nombre' => substr(fake()->unique()->words(2, true), 0, 40),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}
