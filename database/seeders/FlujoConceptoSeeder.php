<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FlujoConceptoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conceptos = [
            // Salidas (Gastos)
            ['nombre' => 'Gastos de Oficina', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Luz', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Agua', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Internet / Teléfono', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Gastos de Sra. Marta', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Gastos de Limpieza', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Préstamo a otra sucursal', 'tipo' => 'SALIDA', 'activo' => true],
            ['nombre' => 'Pago a Proveedores', 'tipo' => 'SALIDA', 'activo' => true],
            
            // Entradas
            ['nombre' => 'Préstamo de otra sucursal', 'tipo' => 'ENTRADA', 'activo' => true],
            ['nombre' => 'Devolución de gastos', 'tipo' => 'ENTRADA', 'activo' => true],
            ['nombre' => 'Aportación de Capital', 'tipo' => 'ENTRADA', 'activo' => true],
            ['nombre' => 'Entradas diversas', 'tipo' => 'ENTRADA', 'activo' => true],
        ];

        foreach ($conceptos as $concepto) {
            \App\Models\FlujoConcepto::updateOrCreate(
                ['nombre' => $concepto['nombre'], 'tipo' => $concepto['tipo']],
                $concepto
            );
        }
    }
}
