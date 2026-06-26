<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\MovimientosCaja; // Asegúrate de tener importado tu modelo de Caja
use Exception;
use Carbon\Carbon;

class VentaJoyeriaController extends Controller
{
    public function buscarNota($folio)
    {
        // 1. Buscamos el encabezado de la venta
        $nota = DB::table('ventas_joyeria_general')->where('id', $folio)->first();

        if (!$nota) {
            return response()->json(['message' => 'El Número de la Nota de Mostrador NO Existe.'], 404);
        }

        // Validación extraída de VB6: Si el estatus es 'C' (Cancelada) o ya fue pagada/convertida
        if ($nota->estatus === 'C') {
            return response()->json(['message' => 'Esta Nota de Mostrador está Cancelada.'], 400);
        }

        // 2. Buscamos el detalle de los artículos
        $detalles = DB::table('ventas_joyeria_detalle')
            ->where('venta_id', $folio)
            ->orderBy('consecutivo', 'asc')
            ->get();

        return response()->json([
            'nota'     => $nota,
            'detalles' => $detalles
        ]);
    }

    public function entradaManual(Request $request)
    {
        $request->validate([
            'monto' => 'required|numeric|min:0.01',
            'denominaciones' => 'required'
        ]);

        MovimientosCaja::create([
            'caja_id'      => 1, // Tu ID de caja actual
            'user_id'      => Auth::id() ?? 1,
            'referencia_id'=> null,
            'tipo'         => 'ENTRADA',
            'monto'        => $request->monto,
            'denominacion' => $request->denominaciones,
            'observaciones'=> $request->observaciones
        ]);

        return response()->json(['message' => 'Entrada registrada con éxito']);
    }

    public function siguienteFolio()
    {
        // Busca el ID más alto en la tabla y le suma 1. Si no hay registros, devuelve 1.
        $ultimoId = DB::table('ventas_joyeria_general')->max('id');
        $siguiente = $ultimoId ? $ultimoId + 1 : 1;

        // Formateamos para que luzca como un folio (ej. 00015)
        $folioFormateado = str_pad($siguiente, 5, "0", STR_PAD_LEFT);

        return response()->json([
            'siguiente_folio' => $folioFormateado
        ]);
    }
    public function procesarVenta(Request $request)
    {
        // 1. Validaciones básicas de seguridad
        $request->validate([
            'tipo_venta' => 'required|string',
            'modo'       => 'required|string',
            'cliente'    => 'required|string',
            'conceptos'  => 'required|array|min:1',
            'pago'       => 'required|array',
            'denominaciones' => 'nullable|array'
        ]);

        return DB::transaction(function () use ($request) {
            $hoy = now();
            $user_id = Auth::id() ?? 1;
            $caja_id = $request->caja_id ?? 1;
            $sucursal_id = 1;

            // Extraemos los datos enviados desde Vue
            $subtotal = collect($request->conceptos)->sum('importe');
            $descuento = $request->descuento ?? 0;
            $total_pagar = $subtotal - $descuento;

            $pago = $request->pago;
            $importe_separado = $pago['importe_separado'] ?? 0;
            $efectivo = $pago['efectivo'] ?? 0;
            $credito = $pago['credito'] ?? 0;
            $debito = $pago['debito'] ?? 0;
            $total_recibido = $pago['recibido'] ?? 0;

            // Lógica de Negocio (VB6): Determinar cuánto dinero real se está cobrando hoy
            $importe_cobrado = ($request->tipo_venta === 'SEPARADO') ? $importe_separado : $total_pagar;
            $saldo_pendiente = ($request->tipo_venta === 'SEPARADO') ? ($total_pagar - $importe_cobrado) : 0;

            // S = Pagada al 100%, N = Tiene saldo pendiente
            $estatus_pago = ($saldo_pendiente <= 0) ? 'S' : 'N';
            $fecha_limite = ($request->tipo_venta === 'SEPARADO') ? $hoy->copy()->addDays(30)->format('Y-m-d') : null;

            // 2. Guardar Datos Generales de la Venta (Encabezado)
            $ventaId = DB::table('ventas_joyeria_general')->insertGetId([
                'sucursal_id'      => $sucursal_id,
                'tipo_venta'       => substr($request->tipo_venta, 0, 1), // T, S, P
                'modo'             => substr($request->modo, 0, 1),       // C, V, D, M
                'fecha_movimiento' => $hoy->format('Y-m-d'),
                'nota_mostrador'   => $request->nota_mostrador,
                'vendedor_id'      => $request->vendedor_id,
                'cliente'          => strtoupper($request->cliente),
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

            // 3. Guardar el Detalle de la Venta (Conceptos / Partidas)
            $detalles = [];
            foreach ($request->conceptos as $index => $item) {
                $detalles[] = [
                    'venta_id'      => $ventaId,
                    'consecutivo'   => $index + 1,
                    'cantidad'      => $item['cantidad'],
                    'categoria'     => $item['categoria_nombre'],
                    'clasificacion' => $item['clasificacion_nombre'],
                    'importe'       => $item['importe'],
                    'concepto'      => strtoupper($item['concepto']),
                    'created_at'    => $hoy,
                    'updated_at'    => $hoy
                ];

                // Opcional: Aquí puedes agregar tu lógica de descontar inventario
                // DB::table('inventario_joyeria')->where('...')->decrement('cantidad', $item['cantidad']);
            }
            DB::table('ventas_joyeria_detalle')->insert($detalles);

            // 4. Guardar Historial del Pago (Para abonos futuros si fue separado)
            DB::table('ventas_joyeria_pagos')->insert([
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

            // 5. Afectar el Arqueo de la Caja (Entrada de Dinero real)
            $esCompra = ($request->tipo_operacion === 'COMPRA');
            $tipoMovimiento = $esCompra ? 'SALIDA' : 'ENTRADA';

            if ($importe_cobrado > 0 && $efectivo > 0) {
                MovimientosCaja::create([
                    'caja_id'      => $caja_id,
                    'user_id'      => $user_id,
                    'referencia_id'=> $ventaId,
                    'tipo'         => $tipoMovimiento,
                    'monto'        => $efectivo, // Solo registramos el efectivo en la caja física
                    'observaciones'=> ($esCompra ? "Compra de Joyería " : "Venta de Joyería ") . "Folio: $ventaId",
                    'denominacion' => $request->denominaciones,
                ]);
            }

            // 6. Retornamos la info lista para la Impresora Térmica
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
}
