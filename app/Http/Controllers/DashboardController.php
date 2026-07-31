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

        // 5. Salidas de Caja Hoy
        $egresosCaja = MovimientosCaja::where('tipo', 'SALIDA')
            ->whereDate('created_at', $hoy)
            ->sum('monto');

        // 6. Cartera Activa (Total prestado en la calle)
        $carteraActiva = Boleta::where('estatus', 'PE')->sum('prestamo');

        // 7. Clientes Nuevos Hoy
        $clientesNuevos = \App\Models\Cliente::whereDate('created_at', $hoy)->count();

        // 8. Intereses Cobrados Hoy
        $interesesCobrados = \App\Models\Pago::whereDate('fecha', $hoy)->sum('interestotal');

        return response()->json([
            'ingresos_caja' => $ingresosCaja,
            'empenos_count' => $empenosHoyCount,
            'empenos_monto' => $empenosHoyMonto,
            'ventas_total' => $ventasHoyTotal,
            'boletas_vencidas' => $boletasVencidasCount,
            'egresos_caja' => $egresosCaja,
            'cartera_activa' => $carteraActiva,
            'clientes_nuevos' => $clientesNuevos,
            'intereses_cobrados' => $interesesCobrados
        ]);
    }
}
