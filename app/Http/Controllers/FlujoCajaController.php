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
            'flujo_concepto_id' => $request->concepto_id,
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
            'flujo_concepto_id' => $request->concepto_id,
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
    public function inventarioCaja(Request $request)
    {
        $hoy = \Carbon\Carbon::today();

        // Obtener movimientos de hoy
        $movimientos = MovimientosCaja::whereDate('created_at', $hoy)->get();

        $inventario = [
            'billetes' => [
                '1000' => 0, '500' => 0, '200' => 0, '100' => 0, '50' => 0, '20' => 0
            ],
            'monedas' => [
                '10' => 0, '5' => 0, '2' => 0, '1' => 0, '0.5' => 0, '0.2' => 0, '0.1' => 0, '0.01' => 0
            ],
            'total' => 0
        ];

        foreach ($movimientos as $mov) {
            $denom = $mov->denominacion;
            if (empty($denom)) continue;
            
            // Handle if denominacion is a json string
            if (is_string($denom)) {
                $denom = json_decode($denom, true);
            }

            if (!is_array($denom)) continue;

            $factor = ($mov->tipo === 'ENTRADA') ? 1 : -1;

            // Formato 1: Arreglo de objetos (Compras de joyería)
            if (isset($denom[0]) && is_array($denom[0]) && isset($denom[0]['valor'])) {
                foreach ($denom as $item) {
                    $val = floatval($item['valor']);
                    $cant = intval($item['cantidad'] ?? 0);
                    if ($val >= 20 && isset($inventario['billetes'][strval($val)])) {
                        $inventario['billetes'][strval($val)] += ($cant * $factor);
                    } else {
                        $key = $val == 0.5 ? '0.5' : ($val == 0.2 ? '0.2' : ($val == 0.1 ? '0.1' : ($val == 0.01 ? '0.01' : strval($val))));
                        if (isset($inventario['monedas'][$key])) {
                            $inventario['monedas'][$key] += ($cant * $factor);
                        }
                    }
                }
            }
            // Formato 2: Objeto estructurado con 'billetes' y 'monedas' (Retiro tradicional)
            else if (isset($denom['billetes']) || isset($denom['monedas'])) {
                if (isset($denom['billetes']) && is_array($denom['billetes'])) {
                    foreach ($denom['billetes'] as $val => $cant) {
                        if (isset($inventario['billetes'][$val])) {
                            $inventario['billetes'][$val] += (intval($cant) * $factor);
                        }
                    }
                }
                if (isset($denom['monedas']) && is_array($denom['monedas'])) {
                    foreach ($denom['monedas'] as $val => $cant) {
                        $key = floatval($val) == 0.5 ? '0.5' : (floatval($val) == 0.2 ? '0.2' : (floatval($val) == 0.1 ? '0.1' : (floatval($val) == 0.01 ? '0.01' : strval($val))));
                        if (isset($inventario['monedas'][$key])) {
                            $inventario['monedas'][$key] += (intval($cant) * $factor);
                        }
                    }
                }
            }
            // Formato 3: Objeto plano con claves mixtas (1000, 500, m10, m050) (Entradas manuales)
            else {
                foreach ($denom as $k => $v) {
                    $cant = intval($v ?? 0);
                    if ($cant === 0) continue;

                    if (strpos($k, 'm') === 0) {
                        $val = str_replace('m', '', $k);
                        if ($val === '050') $val = '0.5';
                        if ($val === '001') $val = '0.01';
                        if (isset($inventario['monedas'][$val])) {
                            $inventario['monedas'][$val] += ($cant * $factor);
                        }
                    } else {
                        if (isset($inventario['billetes'][$k])) {
                            $inventario['billetes'][$k] += ($cant * $factor);
                        }
                    }
                }
            }
        }

        // Calcular total basado en el conteo final
        $total = 0;
        foreach ($inventario['billetes'] as $val => $cant) {
            $total += (floatval($val) * $cant);
        }
        foreach ($inventario['monedas'] as $val => $cant) {
            $total += (floatval($val) * $cant);
        }

        $inventario['total'] = $total;

        return response()->json($inventario);
    }
}

