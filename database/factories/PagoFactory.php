<?php

namespace Database\Factories;

use App\Models\Pago;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
class PagoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'no_pago' => 1,
            'fecha' => now()->format('Y-m-d'),
            'tipo_movimiento' => 1,
            'prestamo' => 1000,
            'interestotal' => 200,
            'importe' => 200,
            'totalPagado' => 200,
            'totalRecibido' => 200,
            'estatus' => 'A'
        ];
    }
}
