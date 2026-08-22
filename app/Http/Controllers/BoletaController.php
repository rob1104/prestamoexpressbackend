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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BoletaController extends Controller
{
    public function index(Request $request)
    {
        $query = Boleta::with(['cliente', 'user']);

        if($request->filled('search'))
        {
            $searchTerm = $request->search;
            $query->whereHas('cliente', function ($q) use ($searchTerm) {
                $q->where('nombre', 'LIKE', "%$searchTerm%")
                    ->orWhere('id', 'LIKE', "$searchTerm%");
            });
        }
        if ($request->filled('tipos')) {
            $query->whereIn('tipo_prestamo', $request->tipos);
        }
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $query->whereBetween('fecha_boleta', [$request->fecha_inicio, $request->fecha_fin]);
        }

        if ($request->filled('folio_exacto')) {
            $query->where('id', $request->folio_exacto);
        }

        $sortBy = $request->input('sortBy', 'id');
        $descending = filter_var($request->input('descending', true), FILTER_VALIDATE_BOOLEAN);
        $direccion = $descending ? 'desc' : 'asc';
        if ($sortBy) {
            $query->orderBy($sortBy, $direccion);
        }

        $rowsPerPage = $request->input('rowsPerPage', 50);
        $rowsPerPage = $rowsPerPage > 0 ? $rowsPerPage : 15;
        $boletas = $query->paginate($rowsPerPage);
        return response()->json($boletas);
    }

    public function downloadPdf($id)
    {
        $boleta = Boleta::with(['cliente', 'partidas', 'tradicional'])->findOrFail($id);
        $sucursal = SucursalConfig::first();
        $pdf = Pdf::loadView('contratoBoleta', compact('boleta', 'sucursal'));
        $pdf->setPaper('legal', 'portrait');

        return $pdf->stream("Contrato_Boleta_Folio_$boleta->id.pdf");
    }

    public function show($id)
    {
        $boleta = Boleta::with(['cliente', 'partidas', 'tradicional'])
            ->where('id', $id)
            ->where('tipo_prestamo', 'tradicional')
            ->first();

        if (!$boleta) {
            return response()->json(['message' => 'Folio no encontrado en el sistema tradicional'], 404);
        }

        if ($boleta->estatus !== 'PE') {
            return response()->json(['message' => 'Folio no encontrado o ya liquidado'], 404);
        }

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

    public function detalles($id)
    {
        // Traemos la boleta sin importar si es tradicional, pagos, o si ya está liquidada
        $boleta = Boleta::with(['cliente', 'partidas', 'user'])->findOrFail($id);

        // Cargamos el historial dependiendo de qué tipo sea
        if ($boleta->tipo_prestamo === 'tradicional') {
            // Carga los pagos (refrendos/liquidaciones)
            $boleta->load(['pagos', 'tradicional' => function($query) {
              $query->orderBy('id', 'desc');
            }]);
        }
        elseif ($boleta->tipo_prestamo === 'pagos') {
            // Carga el calendario de pagos ordenado
            $boleta->load(['calendarioPagos' => function($query) {
                $query->orderBy('num_pago', 'asc');
            }]);
        }

        return response()->json($boleta);
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
                    'iva_comision'      => $request->iva_comision ?? 0,
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
                $interesTotalCobrado = (float)$request->comision;

                // --- NUEVA LÓGICA DE DISTRIBUCIÓN MATEMÁTICA ---
                $pComision = (float)($config->p_comision ?? 20.00);

                if ($pComision > 0) {
                    $mAlmacenaje = round($interesTotalCobrado * ($pAlmacenaje / $pComision), 2);
                    $mAdmin      = round($interesTotalCobrado * ($pAdmin / $pComision), 2);
                    $mIntDiv     = round($interesTotalCobrado * ($pIntDiv / $pComision), 2);
                    $mCustodia   = round($interesTotalCobrado * ($pCustodia / $pComision), 2);
                    $mIva        = round($interesTotalCobrado * ($pIva / $pComision), 2);
                } else {
                    $mAlmacenaje = round($interesTotalCobrado * (6 / 20), 2);
                    $mIntDiv     = round($interesTotalCobrado * (4.5 / 20), 2);
                    $mAdmin      = round($interesTotalCobrado * (3.57 / 20), 2);
                    $mCustodia   = round($interesTotalCobrado * (4 / 20), 2);
                    $mIva        = round($interesTotalCobrado * (1.93 / 20), 2);
                }

                // Ajuste de centavos (el Almacenaje absorbe la diferencia)
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
                            'fecha_vencimiento' => $fila['fecha_vencimiento_raw'],

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
                    'iva_comision'      => $request->iva_comision ?? 0,
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
                throw new Exception("Esta boleta ya fue liquidada anteriormente.");
            }

            $bonificacionNC = $request->input('bonificacion', 0);
            $hoy = now();

            $observacionesLiq = ($boleta->tipo_prestamo === 'pagos' ? 'Liquidación Préstamo en Pagos' : 'Liquidación Préstamo Tradicional') . ' - Boleta #' . $boleta->id;

            MovimientosCaja::create([
                'caja_id'      => $request->caja_id ?? 1,
                'boleta_id'    => $boleta->id,
                'user_id'      => auth()->id(),
                'tipo'         => 'ENTRADA',
                'monto'        => $request->total_pagado,
                'denominacion' => $request->denominaciones,
                'observaciones'=> $observacionesLiq,
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


            $nombreCompleto = trim(
                ($boleta->cliente->nombre ?? 'PÚBLICO GENERAL') . ' ' .
                ($boleta->cliente->apellido_paterno ?? '') . ' ' .
                ($boleta->cliente->apellido_materno ?? '')
            );

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
                    'nombre' => strtoupper($nombreCompleto) ?? 'PÚBLICO GENERAL',
                ]
            ];

            return response()->json([
                'message'     => 'Liquidación procesada correctamente',
                'ticket_data' => $ticket_data
            ]);
        });
    }

    public function procesarAbono(Request $request)
    {
        $request->validate([
            'boleta_id'      => 'required|exists:boletas,id',
            'importe_pago'   => 'required|numeric',
            'abono_capital'  => 'required|numeric|min:1',
            'recargos'       => 'required|numeric',
            'dias_vencidos'  => 'required|integer',
            'total_pagado'   => 'required|numeric',
            'total_recibido' => 'required|numeric',
            'denominaciones' => 'required|json'
        ]);

        return DB::transaction(function () use ($request) {
            $boleta = Boleta::with('cliente')->findOrFail($request->boleta_id);

            if (in_array($boleta->estatus, ['Liquidada', 'Desempeñada', 'Inactiva', 'LI'])) {
                throw new Exception("Esta boleta ya no admite abonos.");
            }

            if ($request->abono_capital >= $boleta->prestamo) {
                throw new Exception("El abono debe ser menor al préstamo. Utilice Liquidación.");
            }

            $hoy = now();

            $observacionesAbono = ($boleta->tipo_prestamo === 'pagos' ? 'Abono Préstamo en Pagos' : 'Abono Préstamo Tradicional') . ' - Boleta #' . $boleta->id;

            // 1. Registro en Caja de la entrada de dinero
            MovimientosCaja::create([
                'caja_id'      => $request->caja_id ?? 1,
                'boleta_id'    => $boleta->id,
                'user_id'      => auth()->id(),
                'tipo'         => 'ENTRADA',
                'monto'        => $request->total_pagado,
                'denominacion' => $request->denominaciones,
                'observaciones'=> $observacionesAbono,
            ]);

            // 2. Insertar el Pago (Historial)
            $pagoId = DB::table('pagos')->insertGetId([
                'boleta_id'         => $boleta->id,
                'no_pago'           => $request->no_pago ?? 1,
                'fecha'             => $hoy->format('Y-m-d'),
                'tipo_movimiento'   => 2, // 2 = Abono a Capital
                'prestamo'          => $boleta->prestamo,
                'interestotal'      => $boleta->comision,
                'recargosNormal'    => $request->recargos,
                'dias_vencidos'     => $request->dias_vencidos,
                'importe'           => $request->total_pagado, // Refrendo + Abono
                'user_id'           => auth()->id(),
                'totalPagado'       => $request->total_pagado,
                'totalRecibido'     => $request->total_recibido,
                'caja_id'           => $request->caja_id ?? 1,
                'estatus'           => 'A',
                'created_at'        => $hoy,
                'updated_at'        => $hoy
            ]);

            // 3. RECALCULAR LOS NUEVOS VALORES DE LA BOLETA (Lógica VB6)
            $nuevoPrestamo = $boleta->prestamo - $request->abono_capital;

            // Calculamos la nueva comisión sobre el saldo restante (ej. 800 * 0.20 = 160)
            $porcentajeInteres = $boleta->p_interes / 100;
            $nuevaComisionTotal = round($nuevoPrestamo * $porcentajeInteres, 2);

            // Extraemos los porcentajes de la sucursal para dividir la nueva comisión
            $config = SucursalConfig::first();
            $pAlmacenaje = (float)($config->p_almacenaje ?? 0);
            $pAdmin      = (float)($config->p_administracion ?? 0);
            $pCustodia   = (float)($config->p_custodia ?? 0);
            $pIntDiv     = (float)($config->p_interes_dividido ?? 0);

            $pIva        = (float)($config->p_iva_interes ?? 1.93);
            $pComision   = (float)($config->p_comision ?? 20.00);

            if ($pComision > 0) {
                $mAlmacenaje = round($nuevaComisionTotal * ($pAlmacenaje / $pComision), 2);
                $mAdmin      = round($nuevaComisionTotal * ($pAdmin / $pComision), 2);
                $mIntDiv     = round($nuevaComisionTotal * ($pIntDiv / $pComision), 2);
                $mCustodia   = round($nuevaComisionTotal * ($pCustodia / $pComision), 2);
                $mIva        = round($nuevaComisionTotal * ($pIva / $pComision), 2);
            } else {
                $mAlmacenaje = round($nuevaComisionTotal * (6 / 20), 2);
                $mIntDiv     = round($nuevaComisionTotal * (4.5 / 20), 2);
                $mAdmin      = round($nuevaComisionTotal * (3.57 / 20), 2);
                $mCustodia   = round($nuevaComisionTotal * (4 / 20), 2);
                $mIva        = round($nuevaComisionTotal * (1.93 / 20), 2);
            }

            $sumaPartes = $mAlmacenaje + $mAdmin + $mCustodia + $mIntDiv + $mIva;
            $mAlmacenaje += round($nuevaComisionTotal - $sumaPartes, 2); // Ajuste de centavos

            // 4. Actualizar la Boleta Maestra con los nuevos saldos
            $nuevaFechaVencimiento = $hoy->addDays(30)->format('Y-m-d'); // Extender el plazo

            $boleta->update([
                'prestamo'          => $nuevoPrestamo,
                'comision'          => $nuevaComisionTotal,
                'total_pagar'       => $nuevoPrestamo + $nuevaComisionTotal,
                'fecha_vencimiento' => $nuevaFechaVencimiento
            ]);

            // 5. Crear el nuevo periodo (BoletaTradicional)
            $ultimoRefrendo = BoletaTradicional::where('boleta_id', $boleta->id)->latest('id')->first();
            $nuevoNumRefrendo = ($ultimoRefrendo->refrendo ?? 1) + 1;

            if ($ultimoRefrendo) {
                $ultimoRefrendo->update(['estatus' => 'PA']); // Marcar el anterior como pagado
            }

            BoletaTradicional::create([
                'boleta_id'         => $boleta->id,
                'refrendo'          => $nuevoNumRefrendo,
                'fecha_vencimiento' => $nuevaFechaVencimiento,
                'dias_reales'       => 30,
                'capital'           => $nuevoPrestamo,
                'interes'           => $nuevaComisionTotal,
                'almacenaje'        => $mAlmacenaje,
                'administracion'    => $mAdmin,
                'custodia'          => $mCustodia,
                'interesdividido'   => $mIntDiv,
                'iva_interes'       => $mIva,
                'estatus'           => 'PE',
                'user_id'           => auth()->id(),
            ]);

            // 6. Preparar ticket
            $nombreCompleto = trim(($boleta->cliente->nombre ?? 'PÚBLICO') . ' ' . ($boleta->cliente->apellido_paterno ?? ''));

            return response()->json([
                'message'     => 'Abono procesado correctamente',
                'ticket_data' => [
                    'folio_contrato'  => $boleta->id,
                    'numero_refrendo' => $request->no_pago,
                    'no_bolsa'        => $boleta->no_bolsa,
                    'prestamo'        => $boleta->prestamo, // Nuevo saldo para imprimir
                    'recargos'        => $request->recargos,
                    'total_pagado'    => $request->total_pagado,
                    'recibido'        => $request->total_recibido,
                    'fecha_vencimiento' => $nuevaFechaVencimiento,
                    'cliente' => ['id' => $boleta->cliente_id, 'nombre' => strtoupper($nombreCompleto)]
                ]
            ]);
        });
    }

    public function cancelar(Request $request, $id)
    {
        $request->validate([
            'motivo' => 'required|string|max:1000'
        ]);

        $boleta = Boleta::findOrFail($id);

        if ($boleta->estatus === 'CA') {
            return response()->json(['message' => 'La boleta ya se encuentra cancelada'], 422);
        }

        if (in_array($boleta->estatus, ['LI', 'EN', 'CV'])) {
            return response()->json(['message' => 'No se puede cancelar una boleta liquidada o enajenada'], 422);
        }

        DB::transaction(function () use ($boleta, $request) {
            $boleta->update([
                'estatus' => 'CA',
                'motivo_cancelacion' => $request->input('motivo'),
                'cancelada_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Boleta cancelada exitosamente', 'boleta' => $boleta]);
    }
}
