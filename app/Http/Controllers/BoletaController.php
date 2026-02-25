<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BoletaController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validación de los datos recibidos desde los componentes
        $request->validate([
            'cliente_id'        => 'required|exists:clientes,id',
            'categoria_id'      => 'required',
            'no_bolsa'          => 'required|integer',
            'fecha_boleta'      => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'prestamo'          => 'required|numeric|min:1',
            'partidas'          => 'required|array|min:1',
        ]);

        try {
            // 2. Iniciamos una transacción para garantizar integridad
            return DB::transaction(function () use ($request) {

                // A. Crear el encabezado de la Boleta
                $boleta = Boleta::create([
                    'cliente_id'        => $request->cliente_id,
                    'categoria_id'      => $request->categoria_id,
                    'user_id'        => Auth::id() ?? 1,
                    'no_bolsa'          => $request->no_bolsa,
                    'tipo_prestamo'     => $request->tipo_prestamo ?? 'tradicional',
                    'meses'             => $request->meses ?? 1,
                    'prestamo'          => $request->prestamo,
                    'valor_comercial'   => $request->valor_comercial,
                    'p_interes'         => $request->p_interes,
                    'comision'          => $request->comision,
                    'iva_comision'      => $request->iva_comision,
                    'total_pagar'       => $request->total_pagar,
                    'fecha_boleta'      => $request->fecha_boleta,
                    'fecha_vencimiento' => $request->fecha_vencimiento,
                    'estatus'           => 'PE',
                ]);

                // B. Guardar el detalle de las prendas (Oro/Monedas)
                foreach ($request->partidas as $item) {
                    $boleta->partidas()->create([
                        'tipo'            => $item['tipo'],
                        'subtipo'         => $item['subtipo'],
                        'gramos_cantidad' => $item['gramos_cantidad'],
                        'costo_unitario'  => $item['costo_unitario'],
                        'valor'           => $item['valor'],
                        'descripcion'     => $item['descripcion'],
                    ]);
                }

                // C. Generar el Vencimiento Inicial
                // Para boleta tradicional es solo un registro
                $boleta->vencimientos()->create([
                    'no_pago'           => 1,
                    'fecha_vencimiento' => $request->fecha_vencimiento,
                    'capital'           => $request->prestamo,
                    'comision'          => $request->comision,
                    'iva_comision'      => $request->iva_comision,
                    'total'             => $request->total_pagar,
                    'estatus'           => 'pendiente',
                    'usuario_id'        => Auth::id() ?? 1,
                ]);

                // D. Registrar el Movimiento Contable Inicial
                $boleta->movimientos()->create([
                    'tipo'              => 'empeño',
                    'capital_original'  => $request->prestamo,
                    'comision_original' => $request->comision,
                    'importe_pagado'    => 0,
                    'estatus'           => 'aplicado',
                    'usuario_id'        => Auth::id() ?? 1,
                    'fecha_movimiento'  => now(),
                ]);

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Boleta generada con éxito',
                    'boleta'  => $boleta->load('partidas','cliente', 'user')
                ], 201);
            });

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }
}
