<?php

namespace Database\Factories;

use App\Models\Caja;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Caja>
 */
class CajaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'numero' => $this->faker->unique()->numberBetween(1, 100),
            'nombre' => 'Caja ' . $this->faker->unique()->numberBetween(1, 100),
            'saldo_actual' => $this->faker->randomFloat(2, 0, 50000),
        ];
    }
}
