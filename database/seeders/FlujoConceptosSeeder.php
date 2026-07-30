<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\FlujoConcepto;

class FlujoConceptosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $conceptos = [
            // ENTRADAS
            ['nombre' => 'Aportación Inicial / Fondo de Caja', 'tipo' => 'ENTRADA'],
            ['nombre' => 'Sobrante de Caja', 'tipo' => 'ENTRADA'],
            ['nombre' => 'Ingreso Extraordinario', 'tipo' => 'ENTRADA'],

            // SALIDAS / GASTOS
            ['nombre' => 'Retiro de Efectivo (Bóveda)', 'tipo' => 'SALIDA'],
            ['nombre' => 'Gasto: Papelería y Oficina', 'tipo' => 'SALIDA'],
            ['nombre' => 'Gasto: Alimentos / Comida', 'tipo' => 'SALIDA'],
            ['nombre' => 'Gasto: Mantenimiento y Limpieza', 'tipo' => 'SALIDA'],
            ['nombre' => 'Pago a Proveedor', 'tipo' => 'SALIDA'],
            ['nombre' => 'Faltante de Caja', 'tipo' => 'SALIDA'],
        ];

        foreach ($conceptos as $concepto) {
            FlujoConcepto::firstOrCreate(
                ['nombre' => $concepto['nombre']], // Evita duplicados por nombre
                [
                    'tipo' => $concepto['tipo'],
                    'activo' => true
                ]
            );
        }
    }
}
