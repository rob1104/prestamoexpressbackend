<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ArqueoCaja;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ArqueoCajaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'caja_id' => 'required|integer',
            'desglose' => 'required|array',
        ]);

        $cajaId = $request->input('caja_id');
        $desglose = $request->input('desglose');

        // Calcular Importe Arqueo (la suma física capturada)
        $importeArqueo = 0;
        
        // Billetes
        $importeArqueo += ($desglose['billetes']['1000'] ?? 0) * 1000;
        $importeArqueo += ($desglose['billetes']['500'] ?? 0) * 500;
        $importeArqueo += ($desglose['billetes']['200'] ?? 0) * 200;
        $importeArqueo += ($desglose['billetes']['100'] ?? 0) * 100;
        $importeArqueo += ($desglose['billetes']['50'] ?? 0) * 50;
        $importeArqueo += ($desglose['billetes']['20'] ?? 0) * 20;

        // Monedas
        $importeArqueo += ($desglose['monedas']['10'] ?? 0) * 10;
        $importeArqueo += ($desglose['monedas']['5'] ?? 0) * 5;
        $importeArqueo += ($desglose['monedas']['2'] ?? 0) * 2;
        $importeArqueo += ($desglose['monedas']['1'] ?? 0) * 1;
        $importeArqueo += ($desglose['monedas']['0_50'] ?? 0) * 0.50;
        $importeArqueo += ($desglose['monedas']['0_20'] ?? 0) * 0.20;
        $importeArqueo += ($desglose['monedas']['0_10'] ?? 0) * 0.10;
        $importeArqueo += ($desglose['monedas']['0_01'] ?? 0) * 0.01;

        // Calcular Importe Sistema
        $entradas = DB::table('pagos')
                ->where('estatus', 'A')
                ->where('caja_id', $cajaId)
                ->sum('importe') +
            DB::table('movimientos_cajas')
                ->where('tipo', 'ENTRADA')
                ->where('caja_id', $cajaId)
                ->whereNull('boleta_id')
                ->sum('monto');

        // Para consistencia con ReporteFlujoCajaController
        $salidas = DB::table('boletas')
                ->whereNotIn('estatus', ['ANULADO', 'CA'])
                ->sum('prestamo') +
            DB::table('movimientos_cajas')
                ->where('tipo', 'SALIDA')
                ->where('caja_id', $cajaId)
                ->whereNull('boleta_id')
                ->sum('monto');

        $importeSistema = $entradas - $salidas;
        $diferencia = $importeArqueo - $importeSistema;

        // Guardar Arqueo en BD
        $arqueo = ArqueoCaja::create([
            'caja_id' => $cajaId,
            'user_id' => Auth::id() ?? 1,
            'importe_sistema' => $importeSistema,
            'importe_arqueo' => $importeArqueo,
            'diferencia' => $diferencia,
            'desglose' => $desglose,
        ]);

        return response()->json([
            'message' => 'Arqueo guardado exitosamente',
            'arqueo' => $arqueo
        ], 201);
    }
}
