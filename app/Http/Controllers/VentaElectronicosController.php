<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientosCaja;
use Exception;

class VentaElectronicosController extends Controller
{
    // --- 1. PROCESAR VENTA NUEVA ---
    public function procesarVenta(Request $request)
    {
        $request->validate([
            'tipo_venta' => 'required|string',
            'modo'       => 'required|string',
            'cliente'    => 'required|string',
            'conceptos'  => 'required|array|min:1',
            'pago'       => 'required|array',
            'denominaciones' => 'nullable'
        ]);

        return DB::transaction(function () use ($request) {
            $hoy = now();
            $user_id = Auth::id() ?? 1;
            $caja_id = 1;

            $subtotal = collect($request->conceptos)->sum(fn($c) => $c['precio'] * $c['cantidad']);
            $descuento = $request->descuento ?? 0;
            $total_pagar = $subtotal - $descuento;

            $pago = $request->pago;
            $importe_separado = $pago['importe_separado'] ?? 0;
            $efectivo = $pago['efectivo'] ?? 0;
            $credito = $pago['credito'] ?? 0;
            $debito = $pago['debito'] ?? 0;
            $total_recibido = $pago['recibido'] ?? 0;

            $importe_cobrado = ($request->tipo_venta === 'SEPARADO') ? $importe_separado : $total_pagar;
            $saldo_pendiente = ($request->tipo_venta === 'SEPARADO') ? ($total_pagar - $importe_cobrado) : 0;
            $estatus_pago = ($saldo_pendiente <= 0) ? 'S' : 'N';
            $fecha_limite = ($request->tipo_venta === 'SEPARADO') ? $hoy->copy()->addDays(30)->format('Y-m-d') : null;

            // 1. Guardar Encabezado
            $ventaId = DB::table('ventas_electronicos_general')->insertGetId([
                'sucursal_id'      => $request->sucursal_id ?? 1,
                'tipo_venta'       => substr($request->tipo_venta, 0, 1),
                'modo'             => substr($request->modo, 0, 1),
                'fecha_movimiento' => $hoy->format('Y-m-d'),
                'nota_mostrador'   => $request->nota_mostrador,
                'vendedor_id'      => $request->vendedor_id,
                'empresa_id'       => $request->empresa_id,
                'cliente'          => strtoupper($request->cliente),
                'no_bolsa'         => $request->no_bolsa,
                'subtotal'         => $subtotal,
                'descuento'        => $descuento,
                'total_pagar'      => $total_pagar,
                'pago_recibido'    => $total_recibido,
                'estatus'          => 'A',
                'estatus_pago'     => $estatus_pago,
                'fecha_limite'     => $fecha_limite,
                'usuario_id'       => $user_id,
                'caja_id'          => $caja_id,
                'created_at'       => $hoy,
                'updated_at'       => $hoy
            ]);

            // 2. Guardar Detalle (Artículos)
            $detalles = [];
            foreach ($request->conceptos as $index => $item) {
                $detalles[] = [
                    'venta_id'         => $ventaId,
                    'consecutivo'      => $index + 1,
                    'codigo'           => $item['codigo'],
                    'clasificacion'    => $item['clasificacion_nombre'] ?? null,
                    'subclasificacion' => $item['subclasificacion_nombre'] ?? null,
                    'descripcion'      => strtoupper($item['descripcion']),
                    'cantidad'         => $item['cantidad'],
                    'precio'           => $item['precio'],
                    'importe'          => $item['precio'] * $item['cantidad'],
                    'created_at'       => $hoy,
                    'updated_at'       => $hoy
                ];

                // Lógica para descontar inventario en el futuro
                // DB::table('inventario_electronicos')->where('codigo', $item['codigo'])->decrement('existencia', $item['cantidad']);
            }
            DB::table('ventas_electronicos_detalle')->insert($detalles);

            // 3. Guardar Pago Inicial
            DB::table('ventas_electronicos_pagos')->insert([
                'venta_id'         => $ventaId,
                'no_pago'          => 1,
                'fecha_pago'       => $hoy->format('Y-m-d'),
                'importe'          => $importe_cobrado,
                'saldo_pagar'      => $total_pagar,
                'resto_pagar'      => $saldo_pendiente,
                'estatus'          => 'A',
                'tipo_venta'       => ($request->tipo_venta === 'SEPARADO') ? 'S' : 'T',
                'modo'             => substr($request->modo, 0, 1),
                'importe_recibido' => $total_recibido,
                'importe_efectivo' => $efectivo,
                'importe_credito'  => $credito,
                'importe_debito'   => $debito,
                'usuario_id'       => $user_id,
                'caja_id'          => $caja_id,
                'created_at'       => $hoy,
                'updated_at'       => $hoy
            ]);

            // 4. Afectar Caja
            if ($importe_cobrado > 0 && $efectivo > 0) {
                MovimientosCaja::create([
                    'caja_id'      => $caja_id,
                    'user_id'      => $user_id,
                    'referencia_id'=> $ventaId,
                    'tipo'         => 'ENTRADA',
                    'monto'        => $efectivo,
                    'denominacion' => $request->denominaciones,
                    'observaciones'=> "Venta Electrónicos Folio: $ventaId"
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Venta registrada exitosamente',
                'ticket_data' => [
                    'folio'        => $ventaId,
                    'cliente'      => strtoupper($request->cliente),
                    'tipo_venta'   => $request->tipo_venta,
                    'modo'         => $request->modo,
                    'conceptos'    => $request->conceptos,
                    'subtotal'     => $subtotal,
                    'descuento'    => $descuento,
                    'total_pagar'  => $total_pagar,
                    'saldo'        => $saldo_pendiente,
                    'fecha_limite' => $fecha_limite,
                    'pago'         => $request->pago,
                    'vendedor_id'  => $request->vendedor_id
                ]
            ]);
        });
    }

    // --- 2. OBTENER FOLIO SIGUIENTE ---
    public function siguienteFolio()
    {
        $ultimoId = DB::table('ventas_electronicos_general')->max('id');
        $siguiente = $ultimoId ? $ultimoId + 1 : 1;
        return response()->json(['siguiente_folio' => str_pad($siguiente, 5, "0", STR_PAD_LEFT)]);
    }

    // --- 3. CARGAR NOTA DE MOSTRADOR (F5) ---
    public function buscarNota($folio)
    {
        $nota = DB::table('ventas_electronicos_general')->where('id', $folio)->first();

        if (!$nota) return response()->json(['message' => 'El Número de la Nota de Mostrador NO Existe.'], 404);
        if ($nota->estatus === 'C') return response()->json(['message' => 'Esta Nota de Mostrador está Cancelada.'], 400);

        $detalles = DB::table('ventas_electronicos_detalle')
            ->where('venta_id', $folio)
            ->orderBy('consecutivo', 'asc')
            ->get();

        $pagos = DB::table('ventas_electronicos_pagos')
            ->where('venta_id', $folio)
            ->orderBy('no_pago', 'asc')
            ->get();

        return response()->json([
            'nota'     => $nota,
            'detalles' => $detalles,
            'pagos'    => $pagos
        ]);
    }
}
