<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Boleta;
use App\Models\MovimientosCaja;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function resumenDiario(Request $request)
    {
        $rango = $request->get('rango', 'hoy'); // 'hoy', 'semana', 'mes'
        
        $hoy = now();
        if ($rango === 'semana') {
            $fechaInicio = now()->startOfWeek();
        } elseif ($rango === 'mes') {
            $fechaInicio = now()->startOfMonth();
        } else {
            $fechaInicio = now()->startOfDay();
        }
        $fechaFin = now()->endOfDay();

        // 1. Ingresos a Caja (Efectivo físico)
        $ingresosCaja = MovimientosCaja::where('tipo', 'ENTRADA')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('monto');

        // 2. Empeños Nuevos
        $empenosHoyCount = Boleta::whereBetween('fecha_boleta', [$fechaInicio, $fechaFin])->count();
        $empenosHoyMonto = Boleta::whereBetween('fecha_boleta', [$fechaInicio, $fechaFin])->sum('prestamo');

        // 3. Ventas (Joyería + Electrónicos)
        $ventasJoyeria = DB::table('ventas_joyeria_general')
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->where('estatus', '!=', 'C')
            ->sum('total_pagar');
            
        $ventasElectronicos = DB::table('ventas_electronicos_general')
            ->whereBetween('fecha_movimiento', [$fechaInicio, $fechaFin])
            ->where('estatus', '!=', 'C')
            ->sum('total_pagar');
            
        $ventasHoyTotal = $ventasJoyeria + $ventasElectronicos;

        // 4. Boletas Vencidas
        $boletasVencidasCount = Boleta::where('estatus', 'PE')
            ->whereDate('fecha_vencimiento', '<', $hoy->toDateString()) // always calculate as of today
            ->count();

        // 5. Salidas de Caja
        $egresosCaja = MovimientosCaja::where('tipo', 'SALIDA')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->sum('monto');

        // 6. Cartera Activa
        $carteraActiva = Boleta::where('estatus', 'PE')->sum('prestamo');

        // 7. Clientes Nuevos
        $clientesNuevos = \App\Models\Cliente::whereBetween('created_at', [$fechaInicio, $fechaFin])->count();

        // 8. Intereses Cobrados
        $interesesCobrados = \App\Models\Pago::whereBetween('fecha', [$fechaInicio, $fechaFin])->sum('interestotal');

        // --- CHART DATA ---
        
        // Flujo Trend Chart (Last 7 days regardless of filter)
        $fechasFlujo = [];
        $ingresosFlujo = [];
        $egresosFlujo = [];
        for ($i = 6; $i >= 0; $i--) {
            $f = now()->subDays($i)->toDateString();
            $fechasFlujo[] = Carbon::parse($f)->format('d M');
            $ingresosFlujo[] = MovimientosCaja::where('tipo', 'ENTRADA')->whereDate('created_at', $f)->sum('monto');
            $egresosFlujo[] = MovimientosCaja::where('tipo', 'SALIDA')->whereDate('created_at', $f)->sum('monto');
        }
        
        // Cartera Status Donut Chart (Based on boleta count)
        $carteraNuevos = Boleta::whereDate('fecha_boleta', $hoy->toDateString())->where('estatus', '!=', 'ANULADO')->count();
        $carteraVigentes = Boleta::where('estatus', 'PE')->whereDate('fecha_vencimiento', '>=', $hoy->toDateString())->count();
        $carteraVencidos = Boleta::where('estatus', 'PE')->whereDate('fecha_vencimiento', '<', $hoy->toDateString())->count();

        return response()->json([
            'ingresos_caja' => $ingresosCaja,
            'empenos_count' => $empenosHoyCount,
            'empenos_monto' => $empenosHoyMonto,
            'ventas_total' => $ventasHoyTotal,
            'boletas_vencidas' => $boletasVencidasCount,
            'egresos_caja' => $egresosCaja,
            'cartera_activa' => $carteraActiva,
            'clientes_nuevos' => $clientesNuevos,
            'intereses_cobrados' => $interesesCobrados,
            'charts' => [
                'flujo' => [
                    'categories' => $fechasFlujo,
                    'ingresos' => $ingresosFlujo,
                    'egresos' => $egresosFlujo
                ],
                'cartera' => [
                    'series' => [$carteraNuevos, $carteraVigentes, $carteraVencidos],
                    'labels' => ['Nuevos Hoy', 'Vigentes', 'Vencidos']
                ]
            ]
        ]);
    }
}
