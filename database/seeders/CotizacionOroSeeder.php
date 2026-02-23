<?php

namespace Database\Seeders;

use App\Models\CotizacionOro;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CotizacionOroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiamos la tabla para evitar duplicados si se vuelve a correr
        DB::table('cotizacion_oros')->truncate();

        $cotizaciones = [
            // --- CATEGORÍA: ORO POR GRAMOS ---
            ['categoria' => 'GRAMOS', 'descripcion' => '8 K', 'precio_nuevo' => 130.00, 'precio_bueno' => 150.00, 'precio_excelente' => 160.00, 'precio_compra' => 145.00],
            ['categoria' => 'GRAMOS', 'descripcion' => '10 K', 'precio_nuevo' => 150.00, 'precio_bueno' => 165.00, 'precio_excelente' => 200.00, 'precio_compra' => 170.00],
            ['categoria' => 'GRAMOS', 'descripcion' => '14 K', 'precio_nuevo' => 250.00, 'precio_bueno' => 250.00, 'precio_excelente' => 250.00, 'precio_compra' => 250.00],
            ['categoria' => 'GRAMOS', 'descripcion' => '18 K', 'precio_nuevo' => 360.00, 'precio_bueno' => 380.00, 'precio_excelente' => 450.00, 'precio_compra' => 400.00],
            ['categoria' => 'GRAMOS', 'descripcion' => '21 K', 'precio_nuevo' => 430.00, 'precio_bueno' => 440.00, 'precio_excelente' => 500.00, 'precio_compra' => 500.00],
            ['categoria' => 'GRAMOS', 'descripcion' => 'ORO FINO', 'precio_nuevo' => 0.00, 'precio_bueno' => 0.00, 'precio_excelente' => 0.00, 'precio_compra' => 0.00],
            ['categoria' => 'GRAMOS', 'descripcion' => 'MEDALLA', 'precio_nuevo' => 0.00, 'precio_bueno' => 0.00, 'precio_excelente' => 0.00, 'precio_compra' => 0.00],

            // --- CATEGORÍA: MONEDAS DE ORO ---
            ['categoria' => 'MONEDAS', 'descripcion' => '2 PESOS', 'precio_nuevo' => 768.00, 'precio_bueno' => 768.00, 'precio_excelente' => 768.00, 'precio_compra' => 0.00],
            ['categoria' => 'MONEDAS', 'descripcion' => '2½ PESOS', 'precio_nuevo' => 912.00, 'precio_bueno' => 912.00, 'precio_excelente' => 912.00, 'precio_compra' => 0.00],
            ['categoria' => 'MONEDAS', 'descripcion' => '5 PESOS', 'precio_nuevo' => 1776.00, 'precio_bueno' => 1776.00, 'precio_excelente' => 1776.00, 'precio_compra' => 0.00],
            ['categoria' => 'MONEDAS', 'descripcion' => '10 PESOS', 'precio_nuevo' => 3600.00, 'precio_bueno' => 3600.00, 'precio_excelente' => 3600.00, 'precio_compra' => 0.00],
            ['categoria' => 'MONEDAS', 'descripcion' => '20 PESOS', 'precio_nuevo' => 7200.00, 'precio_bueno' => 7200.00, 'precio_excelente' => 7200.00, 'precio_compra' => 0.00],
            ['categoria' => 'MONEDAS', 'descripcion' => '50 PESOS', 'precio_nuevo' => 18000.00, 'precio_bueno' => 18000.00, 'precio_excelente' => 18000.00, 'precio_compra' => 0.00],

            // Caso especial: Centenario a la compra
            ['categoria' => 'MONEDAS', 'descripcion' => 'CENTENARIO', 'precio_nuevo' => 0.00, 'precio_bueno' => 0.00, 'precio_excelente' => 0.00, 'precio_compra' => 0.00],
        ];

        foreach ($cotizaciones as $data) {
            CotizacionOro::create($data);
        }
    }
}
