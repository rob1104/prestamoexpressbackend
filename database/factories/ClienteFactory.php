<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => strtoupper($this->faker->firstName()),
            'apellido_paterno' => strtoupper($this->faker->lastName()),
            'apellido_materno' => strtoupper($this->faker->lastName()),
            'identificacion' => $this->faker->unique()->numerify('INE###########'),
            'clasificacion' => $this->faker->randomElement(['NUEVO', 'EXCELENTE', 'BUENO', 'REGULAR', 'MALO']),
            'telefono1' => $this->faker->numerify('##########'),
            'telefono2' => $this->faker->optional()->numerify('##########'),
            'ineFrente' => null,
            'ineReverso' => null,
            'callenum' => $this->faker->streetAddress(),
            'colonia' => $this->faker->citySuffix(),
            'municipio' => $this->faker->city(),
            'estado' => $this->faker->state(),
            'codPostal' => $this->faker->numerify('#####'),
            'ocupacion' => $this->faker->jobTitle(),
            'observacion' => $this->faker->optional()->sentence(),
        ];
    }
}
