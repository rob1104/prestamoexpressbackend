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
        $hoy = now()->toDateString();

        // 1. Ingresos a Caja (Efectivo físico que entró hoy)
        $ingresosCaja = MovimientosCaja::where('tipo', 'ENTRADA')
            ->whereDate('created_at', $hoy)
            ->sum('monto');

        // 2. Empeños Nuevos Hoy
        $empenosHoyCount = Boleta::whereDate('fecha_boleta', $hoy)->count();
        $empenosHoyMonto = Boleta::whereDate('fecha_boleta', $hoy)->sum('prestamo');

        // 3. Ventas de Hoy (Joyería + Electrónicos)
        $ventasJoyeria = DB::table('ventas_joyeria_general')
            ->whereDate('fecha_movimiento', $hoy)
            ->where('estatus', '!=', 'C')
            ->sum('total_pagar');
            
        $ventasElectronicos = DB::table('ventas_electronicos_general')
            ->whereDate('fecha_movimiento', $hoy)
            ->where('estatus', '!=', 'C')
            ->sum('total_pagar');
            
        $ventasHoyTotal = $ventasJoyeria + $ventasElectronicos;

        // 4. Boletas Vencidas
        $boletasVencidasCount = Boleta::where('estatus', 'PE')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->count();

        return response()->json([
            'ingresos_caja' => $ingresosCaja,
            'empenos_count' => $empenosHoyCount,
            'empenos_monto' => $empenosHoyMonto,
            'ventas_total' => $ventasHoyTotal,
            'boletas_vencidas' => $boletasVencidasCount
        ]);
    }
}
