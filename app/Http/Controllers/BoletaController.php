<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use App\Models\BoletaTradicional;
use App\Models\SucursalConfig;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BoletaController extends Controller
{

    public function show($id)
    {
        $boleta = Boleta::with(['cliente', 'partidas', 'user'])->find($id);

        if(!$boleta) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El folio no existe, boleta no encontrada'
            ], 404);
        }

        return response()->json([
            'boleta' => $boleta
        ]);
    }

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
                $mesesEspanol = [
                    'ene' => '01', 'feb' => '02', 'mar' => '03', 'abr' => '04',
                    'may' => '05', 'jun' => '06', 'jul' => '07', 'ago' => '08',
                    'sep' => '09', 'oct' => '10', 'nov' => '11', 'dic' => '12'
                ];

                try {
                    $fechaInput = strtolower($request->fecha_vencimiento); // Aseguramos minúsculas
                    $partes = explode('-', $fechaInput); // Divide en [27, mar, 2026]

                    if (count($partes) === 3) {
                        $dia = str_pad($partes[0], 2, '0', STR_PAD_LEFT);
                        $mes = $mesesEspanol[$partes[1]] ?? '01';
                        $anio = $partes[2];
                        $fechaFormateada = "$anio-$mes-$dia"; // Resultado: 2026-03-27
                    } else {
                        // Fallback de seguridad si el formato falla
                        $fechaFormateada = Carbon::now()->addDays(30)->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $fechaFormateada = Carbon::now()->addDays(30)->format('Y-m-d');
                }

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
                    'fecha_vencimiento' => $fechaFormateada,
                    'estatus'           => 'PE',
                ]);

                $config = SucursalConfig::first();
                if (!$config) {
                    throw new \Exception("No se encontró la configuración de la sucursal.");
                }

                $pAlmacenaje = (float)($config->p_almacenaje ?? 0);
                $pAdmin      = (float)($config->p_administracion ?? 0);
                $pCustodia   = (float)($config->p_custodia ?? 0);
                $pIntDiv     = (float)($config->p_interes_dividido ?? 0);
                $pIva        = (float)($config->p_iva_interes ?? 0);

                $prestamo = (float)$request->prestamo;
                $interesTotalCobrado = (float)$request->comision; // El interés calculado en frontend

                $mAlmacenaje = round($prestamo * ($pAlmacenaje / 100), 2);
                $mAdmin      = round($prestamo * ($pAdmin / 100), 2);
                $mCustodia   = round($prestamo * ($pCustodia / 100), 2);
                $mIntDiv     = round($prestamo * ($pIntDiv / 100), 2);
                $mIva        = round($prestamo * ($pIva / 100), 2);

                $sumaPartes = $mAlmacenaje + $mAdmin + $mCustodia + $mIntDiv + $mIva;
                $diferencia = round($interesTotalCobrado - $sumaPartes, 2);

                $mAlmacenaje += $diferencia;

                BoletaTradicional::create([
                    'boleta_id'         => $boleta->id,
                    'refrendo'          => 1,
                    'fecha_vencimiento' => $fechaFormateada,
                    'dias_reales'       => $request->plazo_dias,
                    'capital'           => $prestamo,
                    'interes'           => $interesTotalCobrado,
                    'almacenaje'        => $mAlmacenaje,
                    'administracion'    => $mAdmin,
                    'custodia'          => $mCustodia,
                    'interesdividido'   => $mIntDiv,
                    'iva_interes'       => $mIva,
                    'estatus'           => 'PE',
                    'user_id'           => auth()->id(),
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
