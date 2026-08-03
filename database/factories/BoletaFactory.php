<?php

namespace Database\Factories;

use App\Models\Boleta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Boleta>
 */
class BoletaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cliente_id' => \App\Models\Cliente::factory(),
            'categoria_id' => \App\Models\Categoria::factory(),
            'promocion_id' => null,
            'user_id' => \App\Models\User::factory(),
            'no_bolsa' => $this->faker->numberBetween(1000, 99999),
            'tipo_prestamo' => $this->faker->randomElement(['tradicional', 'pagos']),
            'meses' => 1,
            'periodo' => 0,
            'pagos' => 1,
            'prestamo' => $this->faker->randomFloat(2, 500, 10000),
            'valor_comercial' => $this->faker->randomFloat(2, 600, 15000),
            'p_interes' => 5.0,
            'comision' => $this->faker->randomFloat(2, 50, 500),
            'iva_comision' => 0,
            'pago_fijo' => 0,
            'ultimo_pago' => 0,
            'total_pagar' => $this->faker->randomFloat(2, 550, 10500),
            'fecha_boleta' => now(),
            'fecha_vencimiento' => now()->addMonth(),
            'estatus' => 'AC',
            'cotizacion_valor' => 0,
        ];
    }
}
