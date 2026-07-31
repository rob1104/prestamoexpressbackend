<?php

namespace App\Http\Controllers;

use App\Models\FlujoConcepto;
use App\Models\MovimientosCaja;
use App\Models\SucursalConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class FlujoCajaController extends Controller
{
    public function getConceptos()
    {
        $entradas = FlujoConcepto::where('tipo', 'ENTRADA')->where('activo', true)->get();
        $salidas = FlujoConcepto::where('tipo', 'SALIDA')->where('activo', true)->get();

        return response()->json([
            'entradas' => $entradas,
            'salidas' => $salidas
        ]);
    }

    public function historial(Request $request)
    {
        $query = MovimientosCaja::with(['caja', 'conceptoFlujo'])->orderBy('id', 'desc');

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('created_at', [
                $request->fecha_inicio . ' 00:00:00',
                $request->fecha_fin . ' 23:59:59'
            ]);
        }

        $movimientos = $query->paginate(50);

        return response()->json([
            'status' => 'success',
            'data' => $movimientos
        ]);
    }

    public function registrarEntrada(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'denominaciones' => 'required|string',
            'concepto_id' => 'required|exists:flujo_conceptos,id',
            'observaciones' => 'nullable|string',
            'recibido_por' => 'nullable|string',
            'entregado_por' => 'nullable|string',
            'autorizado_por' => 'nullable|string',
            'adicional_1' => 'nullable|string',
            'adicional_2' => 'nullable|string',
            'es_pago_relacionado' => 'nullable|boolean'
        ]);

        $concepto = FlujoConcepto::find($request->concepto_id);

        $observacionFinal = $concepto->nombre;
        if (!empty($request->observaciones)) {
            $observacionFinal .= ' - ' . $request->observaciones;
        }

        $movimiento = MovimientosCaja::create([
            'caja_id' => 1, // Por ahora fijo a Caja 1 como en VB6
            'user_id' => Auth::id() ?? 1,
            'concepto_id' => $request->concepto_id,
            'tipo' => 'ENTRADA',
            'monto' => $request->monto,
            'denominacion' => $request->denominaciones,
            'observaciones' => $observacionFinal,
            'recibido_por' => $request->recibido_por,
            'entregado_por' => $request->entregado_por,
            'autorizado_por' => $request->autorizado_por,
            'adicional_1' => $request->adicional_1,
            'adicional_2' => $request->adicional_2,
            'es_pago_relacionado' => $request->es_pago_relacionado ?? false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Entrada registrada correctamente.',
            'data' => $movimiento
        ]);
    }

    public function registrarSalida(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'denominaciones' => 'required|string',
            'concepto_id' => 'required|exists:flujo_conceptos,id',
            'observaciones' => 'nullable|string',
            'recibido_por' => 'nullable|string',
            'entregado_por' => 'nullable|string',
            'autorizado_por' => 'nullable|string',
            'adicional_1' => 'nullable|string',
            'adicional_2' => 'nullable|string',
            'es_pago_relacionado' => 'nullable|boolean'
        ]);

        $concepto = FlujoConcepto::find($request->concepto_id);

        $observacionFinal = $concepto->nombre;
        if (!empty($request->observaciones)) {
            $observacionFinal .= ' - ' . $request->observaciones;
        }

        $movimiento = MovimientosCaja::create([
            'caja_id' => 1, // Por ahora fijo a Caja 1 como en VB6
            'user_id' => Auth::id() ?? 1,
            'concepto_id' => $request->concepto_id,
            'tipo' => 'SALIDA',
            'monto' => $request->monto,
            'denominacion' => $request->denominaciones,
            'observaciones' => $observacionFinal,
            'recibido_por' => $request->recibido_por,
            'entregado_por' => $request->entregado_por,
            'autorizado_por' => $request->autorizado_por,
            'adicional_1' => $request->adicional_1,
            'adicional_2' => $request->adicional_2,
            'es_pago_relacionado' => $request->es_pago_relacionado ?? false
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Salida registrada correctamente.',
            'data' => $movimiento
        ]);
    }

    public function ticketUrlFirmada(Request $request, $id)
    {
        $url = URL::temporarySignedRoute(
            'caja.movimiento.ticket',
            now()->addMinutes(5),
            ['id' => $id]
        );

        return response()->json(['url' => $url]);
    }

    public function imprimirTicket($id)
    {
        $movimiento = MovimientosCaja::with('conceptoFlujo')->findOrFail($id);
        $sucursal = SucursalConfig::first();

        // Extract extras observations (removing the concept name prefix if exists)
        $observacionesExtras = $movimiento->observaciones;
        if ($movimiento->conceptoFlujo && str_starts_with($observacionesExtras, $movimiento->conceptoFlujo->nombre . ' - ')) {
            $observacionesExtras = substr($observacionesExtras, strlen($movimiento->conceptoFlujo->nombre . ' - '));
        } elseif ($movimiento->conceptoFlujo && $observacionesExtras === $movimiento->conceptoFlujo->nombre) {
            $observacionesExtras = '';
        }

        $pdf = Pdf::loadView('ticket-flujo', [
            'movimiento' => $movimiento,
            'sucursal' => $sucursal,
            'cajero' => 'Cajero ' . $movimiento->user_id, // Adjust if User relationship is loaded
            'observacionesExtras' => $observacionesExtras
        ])->setPaper([0, 0, 226.77, 800], 'portrait'); // 80mm thermal paper width

        return $pdf->stream("ticket_movimiento_{$movimiento->id}.pdf");
    }
}
