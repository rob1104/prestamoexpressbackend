<?php

namespace App\Http\Controllers;

use App\Models\MovimientosCaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimientoCajaController extends Controller
{
    public function registrarEfectivo(Request $request, $boletaId)
    {
        $request->validate([
            'desglose' => 'required|array',
            'total_efectivo' => 'required|numeric'
        ]);

        try {
            $movimiento = MovimientosCaja::create([
                'caja_id'      => 1, // Caja Número: 1
                'boleta_id'    => $boletaId,
                'user_id'      => Auth::id(), // ID del cajero
                'tipo'         => 'SALIDA',   // Salida por préstamo
                'monto'        => $request->total_efectivo,
                'denominacion' => $request->desglose, // JSON de billetes y monedas
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Movimiento de caja registrado correctamente',
                'data' => $movimiento
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al registrar en caja: ' . $e->getMessage()
            ], 500);
        }
    }
}
