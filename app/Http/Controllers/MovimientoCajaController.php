<?php

namespace App\Http\Controllers;

use App\Models\MovimientosCaja;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MovimientoCajaController extends Controller
{

    // Verifica si la caja ya fue abierta en la fecha actual
    public function checkApertura()
    {
        $hoy = Carbon::today();

        $aperturaExistente = MovimientosCaja::where('observaciones', 'Fondo de caja inicial (Apertura de turno)')
            ->whereDate('created_at', $hoy)
            ->exists();

        return response()->json([
            'apertura_realizada' => $aperturaExistente
        ]);
    }

    public function registrarEfectivo(Request $request, $boletaId)
    {
        $request->validate([
            'desglose' => 'required|array',
            'total_efectivo' => 'required|numeric'
        ]);

        try {
            $boleta = \App\Models\Boleta::find($boletaId);
            $observaciones = 'Préstamo';
            if ($boleta) {
                if ($boleta->tipo_prestamo === 'pagos') {
                    $observaciones = 'Préstamo en Pagos - Boleta #' . $boletaId;
                } else {
                    $observaciones = 'Préstamo Tradicional - Boleta #' . $boletaId;
                }
            } else {
                $observaciones = 'Préstamo - Boleta #' . $boletaId;
            }

            $movimiento = MovimientosCaja::create([
                'caja_id'      => 1, // Caja Número: 1
                'boleta_id'    => $boletaId,
                'user_id'      => Auth::id(), // ID del cajero
                'tipo'         => 'SALIDA',   // Salida por préstamo
                'monto'        => $request->total_efectivo,
                'denominacion' => $request->desglose, // JSON de billetes y monedas
                'observaciones'=> $observaciones
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

    // Guarda el fondo de caja inicial del turno
    public function registrarApertura(Request $request)
    {
        // 1. Validamos que nos envíen la información correcta
        $request->validate([
            'monto' => 'required|numeric|min:0',
            'denominaciones' => 'required|string'
        ]);

        // 2. Registramos el movimiento especial de APERTURA
        MovimientosCaja::create([
            'caja_id'      => 1,
            'user_id'      => Auth::id() ?? 1,
            'referencia_id'=> null,
            'tipo'         => 'ENTRADA',
            'monto'        => $request->monto,
            'denominacion' => $request->denominaciones,
            'observaciones'=> 'Fondo de caja inicial (Apertura de turno)'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Apertura de caja registrada correctamente.'
        ]);
    }
}
