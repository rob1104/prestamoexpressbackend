<?php

namespace App\Http\Controllers;

use App\Models\SucursalConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ReporteFlujoCajaController extends Controller
{
    public function generarFlujoCaja(Request $request)
    {
        $f1 = $request->input('fecha_inicio');
        $f2 = $request->input('fecha_fin');
        $cajaId = $request->input('caja_id', 1); // Por defecto Caja 1

        // --- 1. CÁLCULO DEL SALDO INICIAL (Histórico antes de f1) ---
        // Sumamos entradas históricas (Pagos + Movimientos Entrada)
        $entradasH = DB::table('pagos')
                ->where('fecha', '<', $f1)
                ->where('estatus', 'A')
                ->where('caja_id', $cajaId)
                ->sum('importe') +
            DB::table('movimientos_cajas')
                ->whereDate('created_at', '<', $f1)
                ->where('tipo', 'ENTRADA')
                ->where('caja_id', $cajaId)
                ->sum('monto');

        // Restamos salidas históricas (Préstamos + Movimientos Salida)
        $salidasH = DB::table('boletas')
                ->whereDate('fecha_boleta', '<', $f1)
                ->where('estatus', '!=', 'ANULADO')
                ->sum('prestamo') +
            DB::table('movimientos_cajas')
                ->whereDate('created_at', '<', $f1)
                ->where('tipo', 'SALIDA')
                ->where('caja_id', $cajaId)
                ->sum('monto');

        $saldoInicial = $entradasH - $salidasH;

        // --- 2. MOVIMIENTOS DEL PERIODO (f1 a f2)  ---

        // Desglose de Pagos (ENTRADAS)
        $cobranza = DB::table('pagos')
            ->whereBetween('fecha', [$f1, $f2])
            ->where('estatus', 'A')
            ->where('caja_id', $cajaId)
            ->selectRaw("SUM(capital) as capital, SUM(interestotal + ivaIC) as interes, SUM(recargosNormal) as recargos")
            ->first();

        // Movimientos Diversos (ENTRADAS/SALIDAS)
        $otrosMov = DB::table('movimientos_cajas')
            ->whereBetween(DB::raw('DATE(created_at)'), [$f1, $f2])
            ->where('caja_id', $cajaId)
            ->selectRaw("
            SUM(CASE WHEN tipo = 'ENTRADA' THEN monto ELSE 0 END) as entradas,
            SUM(CASE WHEN tipo = 'SALIDA' THEN monto ELSE 0 END) as salidas
        ")->first();

        // Préstamos Nuevos (SALIDAS)
        $prestamosNuevos = DB::table('boletas')
            ->whereBetween('fecha_boleta', [$f1, $f2])
            ->where('estatus', '!=', 'ANULADO')
            ->sum('prestamo');

        return response()->json([
            'config' => [
                'fecha_rango' => "DEL $f1 AL $f2",
                'saldo_inicial' => $saldoInicial,
                'caja' => "CAJA $cajaId"
            ],
            'entradas' => [
                'pagos_capital' => $cobranza->capital ?? 0,
                'pagos_interes' => $cobranza->interes ?? 0,
                'pagos_recargos' => $cobranza->recargos ?? 0,
                'otros' => $otrosMov->entradas ?? 0,
            ],
            'salidas' => [
                'prestamos' => $prestamosNuevos ?? 0,
                'otros' => $otrosMov->salidas ?? 0,
            ]
        ]);
    }

    public function generarUrlFirmada(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reporte.flujocaja.pdf',
            now()->addMinutes(1),
            [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'caja_id' => $request->caja_id ?? 1
            ]
        );

        return response()->json(['url' => $url]);
    }

    public function generarPDF(Request $request)
    {
        $data = $this->generarFlujoCaja($request)->getData(true);
        $sucu = SucursalConfig::first();
        $pdf = Pdf::loadView('reportes.flujocaja.pdf', [
            'reporte' => $data,
            'sucursal' => 'PRESTAMO EXPRESS',
            'empresa' => $sucu->nombre_sucursal
        ])->setPaper('letter', 'portrait');
        return $pdf->stream("Flujo_Caja_{$request->fecha_inicio}.pdf");
    }
}
