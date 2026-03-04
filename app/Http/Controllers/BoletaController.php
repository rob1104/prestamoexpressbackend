<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use App\Models\BoletaPago;
use App\Models\BoletaTradicional;
use App\Models\CalendarioPago;
use App\Models\MovimientosCaja;
use App\Models\NotaCredito;
use App\Models\Pago;
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
        $boleta = Boleta::with(['cliente', 'partidas', 'user', 'tradicional'])->find($id);

        if(!$boleta) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El folio no existe, boleta no encontrada'
            ], 404);
        }

        $pagos = Pago::where('boleta_id', $boleta->id)->get();

        return response()->json([
            'boleta' => $boleta,
            'pagos' => $pagos
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
            'fecha_vencimiento' => 'required',
            'prestamo'          => 'required|numeric|min:1',
            'partidas'          => 'required|array|min:1',
            'fecha_vencimiento_raw'          => 'required',
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
                    'fecha_vencimiento' => $request->fecha_vencimiento_raw,
                    'estatus'           => 'PE',
                    'numero_pagos' => $request->numero_pagos
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

                if ($request->tipo_prestamo === 'pagos' && $request->numero_pagos > 0) {

                    $fechasPagos = [];
                    // Parseamos la fecha inicial desde donde arranca la boleta
                    $fechaCiclo = Carbon::parse($request->fecha_boleta);

                    for ($i = 1; $i <= $request->numero_pagos; $i++) {

                        // Sumar días según la frecuencia (Periodo)
                        switch ($request->periodo_id) {
                            case 1: // Semanal
                                $fechaCiclo->addDays(7);
                                break;
                            case 2: // Catorcenal
                                $fechaCiclo->addDays(14);
                                break;
                            case 3: // Quincenal
                                $fechaCiclo->addDays(15);
                                break;
                            case 4: // Mensual
                                $fechaCiclo->addMonths(1);
                                break;
                        }

                        // Determinar si es un pago normal o el último (para ajustar centavos)
                        $montoCuota = ($i == $request->numero_pagos) ? $request->ultimo_pago : $request->pago_fijo;

                        $fechasPagos[] = [
                            'boleta_id'         => $boleta->id,
                            'num_pago'          => $i,
                            'fecha_vencimiento' => $fechaCiclo->format('Y-m-d'),
                            'monto'             => $montoCuota,
                            'estatus'           => 'PE', // Pendiente
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    }

                    // Insertamos todas las letras de pago de golpe en la base de datos
                    CalendarioPago::insert($fechasPagos);

                    $pagosAPreGenerar = [];

                    foreach ($request->calendario as $fila) {
                        // Separamos el "01/04" para obtener solo el "1"
                        $numPago = explode('/', $fila['no_pago'])[0];

                        $pagosAPreGenerar[] = [
                            'boleta_id'         => $boleta->id,
                            'num_pago'          => (int) $numPago,
                            'fecha_vencimiento' => Carbon::parse($fila['fecha_vencimiento'])->format('Y-m-d'),

                            // Llenamos el desglose exacto de la tabla de amortización
                            'importe'           => $fila['capital'],
                            'comision'          => $fila['comision'],
                            'total'             => $fila['pago_requerido'],

                            // Estos campos se quedan vacíos o en 0 porque aún no viene a la ventanilla
                            'fecha_pago'        => null,
                            'importe_recibido'  => 0,
                            'cambio'            => 0,
                            'user_id'           => Auth::id(), // Se llenará con el ID del cajero que le cobre en el futuro
                            'caja_id'           => 1,

                            // 'P' significa que la semana está PENDIENTE de cobro
                            'estatus'           => 'PE',
                            'created_at'        => now(),
                            'updated_at'        => now(),
                        ];
                    }

                    // Insertamos todas las filas de golpe (Muy eficiente en rendimiento)
                    BoletaPago::insert($pagosAPreGenerar);

                }
                else {
                    BoletaTradicional::create([
                        'boleta_id'         => $boleta->id,
                        'refrendo'          => 1,
                        'fecha_vencimiento' => $request->fecha_vencimiento_raw,
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
                }



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
                    'fecha_vencimiento' => $request->fecha_vencimiento_raw,
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

                $pagos = null;
                if ($boleta->tipo_prestamo === 'tradicional') {
                    // Trae el historial de refrendos
                    $pagos = BoletaTradicional::where('boleta_id', $boleta->id)->get();
                }
                elseif ($boleta->tipo_prestamo === 'pagos') {
                    // Trae el calendario de las letras (semanas/quincenas) ordenado
                    $pagos = CalendarioPago::where('boleta_id', $boleta->id)
                        ->orderBy('num_pago', 'asc')
                        ->get();
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'Boleta generada con éxito',
                    'boleta'  => $boleta->load('partidas','cliente', 'user'),
                    'pagos' => $pagos,
                    'historial_pagos' => $pagos
                ], 201);
            });

        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al guardar: ' . $e->getMessage()
            ], 500);
        }
    }

    public function procesarLiquidacion(Request $request)
    {
        // 1. Validamos los datos (Agregamos bonificacion)
        $request->validate([
            'boleta_id'      => 'required|exists:boletas,id',
            'importe_pago'   => 'required|numeric',
            'recargos'       => 'required|numeric',
            'dias_vencidos'  => 'required|integer',
            'total_pagado'   => 'required|numeric',
            'total_recibido' => 'required|numeric',
            'bonificacion'   => 'nullable|numeric',
            'denominaciones' => 'required|json'
        ]);

        return DB::transaction(function () use ($request) {

            $boleta = Boleta::with('cliente')->findOrFail($request->boleta_id);

            if (in_array($boleta->estatus, ['Liquidada', 'Desempeñada', 'Inactiva'])) {
                throw new \Exception("Esta boleta ya fue liquidada anteriormente.");
            }

            $bonificacionNC = $request->input('bonificacion', 0);
            $hoy = now();

            MovimientosCaja::create([
                'caja_id'      => $request->caja_id ?? 1,
                'boleta_id'    => $boleta->id,
                'user_id'      => auth()->id(),
                'tipo'         => 'ENTRADA',
                'monto'        => $request->total_pagado,
                'denominacion' => $request->denominaciones,
            ]);

            // 2. Insertar el Pago de Liquidación (Desempeño)
            $pagoId = DB::table('pagos')->insertGetId([
                'boleta_id'         => $boleta->id,
                'no_pago'           => $request->no_pago ?? '---',
                'fecha'             => $hoy->format('Y-m-d'),
                'tipo_movimiento'   => 1, // <-- 1 = Liquidación/Desempeño (Ajusta según tu catálogo VB6)
                'prestamo'          => $boleta->prestamo,
                'interestotal'      => $boleta->comision, // El interés cobrado
                'recargosNormal'    => $request->recargos,
                'dias_vencidos'     => $request->dias_vencidos,
                'importe'           => $request->importe_pago,
                'user_id'           => auth()->id(),
                'totalPagado'       => $request->total_pagado,
                'totalRecibido'     => $request->total_recibido,
                'caja_id'           => $request->caja_id ?? 1,
                'estatus'           => 'A',
                'created_at'        => $hoy,
                'updated_at'        => $hoy
            ]);

            // 3. Generar la Nota de Crédito (Si hubo descuento por pronto pago)
            if ($bonificacionNC > 0) {
                NotaCredito::create([
                    'boleta_id'         => $boleta->id,
                    'tipo_prestamo'     => 'tradicional',
                    'cantidad'          => $bonificacionNC,
                    'cantidad_sugerida' => $bonificacionNC,
                    'estatus'           => 'aplicado',
                    'caja_id'           => $request->caja_id ?? 1,
                    'user_id'           => auth()->id(),
                ]);
            }

            // 4. Actualizar el estatus de la Boleta a Liquidada
            $boleta->update([
                'estatus' => 'LI',
            ]);

            $trad = BoletaTradicional::where('boleta_id', $boleta->id)->latest()->first();
            $trad->update(['estatus' => 'LI']);
            // 5. Preparar datos para el Ticket
            $ticket_data = [
                'folio_contrato'  => $boleta->id,
                'numero_refrendo' => $trad->refrendo,
                'no_bolsa'        => $boleta->no_bolsa,
                'prestamo'        => $boleta->prestamo,
                'recargos'        => $request->recargos,
                'bonificacion'    => $bonificacionNC,
                'total_pagado'    => $request->total_pagado,
                'recibido'        => $request->total_recibido,
                'cambio'          => $request->total_recibido - $request->total_pagado,
                'cliente' => [
                    'id'     => $boleta->cliente_id ?? '000',
                    'nombre' => $boleta->cliente->nombre ?? 'PÚBLICO GENERAL',
                ]
            ];

            return response()->json([
                'message'     => 'Liquidación procesada correctamente',
                'ticket_data' => $ticket_data
            ]);
        });
    }
}
