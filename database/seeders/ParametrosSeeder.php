<?php

namespace Database\Seeders;

use App\Models\ComisionConfig;
use App\Models\RecargoConfig;
use App\Models\SucursalConfig;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ParametrosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpieza de tablas para evitar duplicados
        DB::table('sucursal_configs')->truncate();
        DB::table('comision_configs')->truncate();
        DB::table('recargo_configs')->truncate();

        // 1. Datos Generales y Financieros de la Sucursal
        SucursalConfig::create([
            'no_sucursal'           => 1,
            'nombre_sucursal'       => 'PRESTAMO EXPRESS MATRIZ',
            'razon_social'          => 'CORPORATIVO EXPRESS S.A. DE C.V.',
            'calle_num'             => 'AVE. SEXTA NO. 218 ENTRE TLAXCALA Y MICHOACAN.',
            'colonia'               => 'FRACC. MODERNO',
            'municipio'             => 'MATAMOROS',
            'estado'                => 'TAMAULIPAS',
            'codigo_postal'         => '87380',
            'rfc'                   => 'CEX-090824-J25',
            'telefono_1'            => '817-8717',

            'p_comision'            => 20.00,
            'p_iva'                 => 16.00,
            'p_avaluo'              => 10.00,
            'capital_trabajo'       => 3058085.31,
            'fondo_fijo_mn'         => 20000.00,
            'costo_reporte_extravio'=> 10.00,

            // Separación de Comisiones
            'p_almacenaje'          => 6.00,
            'p_administracion'      => 3.57,
            'p_custodia'            => 4.00,
            'p_interes_dividido'    => 4.50,
            'p_iva_interes'         => 1.93,

            // Datos del Ticket de Electrónicos
            'linea_01' => 'THALIA GARZA NUÑEZ',
            'linea_02' => 'REMATE DE APARATOS USADOS',
            'linea_03' => 'AVE. SEXTA NO. 218 ENTRE',
            'linea_04' => 'TLAXCALA Y MICHOACAN',
            'linea_05' => 'FRACC. MODERNO TEL: 817-9333',
        ]);

        // 2. Tabla de Comisiones para Préstamos de PAGOS
        $comisiones = [
            ['categorias' => '1/2/3/5/6/7/', 'limite_inferior' => 1.00, 'limite_superior' => 250000.00, 'mes_inferior' => 1, 'mes_superior' => 3, 'porcentaje_comision' => 20.00],
            ['categorias' => '1/2/3/5/6/7/', 'limite_inferior' => 1.00, 'limite_superior' => 250000.00, 'mes_inferior' => 4, 'mes_superior' => 6, 'porcentaje_comision' => 16.00],
            ['categorias' => '1/2/3/5/6/7/', 'limite_inferior' => 1.00, 'limite_superior' => 250000.00, 'mes_inferior' => 7, 'mes_superior' => 12, 'porcentaje_comision' => 14.00],
            ['categorias' => '4/',           'limite_inferior' => 1.00, 'limite_superior' => 800000.00, 'mes_inferior' => 1, 'mes_superior' => 3, 'porcentaje_comision' => 12.00],
        ];

        foreach ($comisiones as $com) {
            ComisionConfig::create($com);
        }

        // 3. Tabla de Recargos
        $recargos = [
            ['dia_inicio' => 1,  'dia_fin' => 14, 'valor' => 0.67,  'tipo' => 'D'], // Diario
            ['dia_inicio' => 15, 'dia_fin' => 15, 'valor' => 10.00, 'tipo' => 'T'], // Total
            ['dia_inicio' => 16, 'dia_fin' => 19, 'valor' => 0.67,  'tipo' => 'D'],
            ['dia_inicio' => 20, 'dia_fin' => 24, 'valor' => 20.00, 'tipo' => 'T'],
            ['dia_inicio' => 25, 'dia_fin' => 29, 'valor' => 30.00, 'tipo' => 'T'],
            ['dia_inicio' => 30, 'dia_fin' => 44, 'valor' => 40.00, 'tipo' => 'T'],
            ['dia_inicio' => 45, 'dia_fin' => 999,'valor' => 50.00, 'tipo' => 'T'],
        ];

        foreach ($recargos as $rec) {
            RecargoConfig::create($rec);
        }
    }
}
