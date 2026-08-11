<?php

namespace App\Http\Controllers;

use App\Models\SucursalConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ReporteCarteraController extends Controller
{
    public function generarReporteCartera(Request $request)
    {
        // 1. PARÁMETROS
        $fecha = $request->input('fecha', date('Y-m-d'));
        $diasAdj = (int) $request->input('dias_adjudicar', 15);

        // Fecha de corte para adjudicación
        $fechaLimiteAdj = \Carbon\Carbon::parse($fecha)->subDays($diasAdj)->format('Y-m-d');

        // --- BLOQUE 1: RESUMEN (HOJA 1) ---
        $p_nuevos = DB::table('boletas')
            ->whereDate('fecha_boleta', $fecha)
            ->where('estatus', '!=', 'ANULADO')
            ->selectRaw('COUNT(*) as cantidad, SUM(prestamo) as monto')
            ->first();

        $c_hoy = DB::table('pagos')
            ->whereDate('fecha', $fecha)
            ->where('estatus', 'A')
            ->selectRaw('
            COUNT(*) as cantidad,
            SUM(capital) as capital,
            SUM(interestotal + recargosNormal + ivaIC) as utilidad,
            SUM(importe) as total')
            ->first();

        // --- BLOQUE 2: CARTERA AGREGADA (HOJA 1) ---
        $vig_total = DB::table('boletas')->where('estatus', 'PE')->whereDate('fecha_vencimiento', '>=', $fecha)
            ->selectRaw('COUNT(*) as cant, SUM(prestamo) as monto')->first();

        $ven_total = DB::table('boletas')->where('estatus', 'PE')->whereDate('fecha_vencimiento', '<', $fecha)
            ->whereDate('fecha_vencimiento', '>=', $fechaLimiteAdj)
            ->selectRaw('COUNT(*) as cant, SUM(prestamo) as monto')->first();

        $adj_total = DB::table('boletas')->where('estatus', 'PE')->whereDate('fecha_vencimiento', '<', $fechaLimiteAdj)
            ->selectRaw('COUNT(*) as cant, SUM(prestamo) as monto')->first();

        // --- BLOQUE 3: DETALLE POR PRODUCTO (HOJA 2) ---
        $obtenerDetalle = function($tipo) use ($fecha, $fechaLimiteAdj) {
            $t = ($tipo == 'PA') ? ['PA', 'pagos'] : ['TR', 'tradicional'];

            return [
                'nuevos' => DB::table('boletas')->whereIn('tipo_prestamo', $t)->whereDate('fecha_boleta', $fecha)->where('estatus', '!=', 'ANULADO')
                    ->selectRaw('SUM(prestamo) as capital, SUM(comision + iva_comision) as comision')->first(),

                'vigente' => DB::table('boletas')->whereIn('tipo_prestamo', $t)->where('estatus', 'PE')->whereDate('fecha_vencimiento', '>=', $fecha)
                    ->selectRaw('SUM(prestamo) as capital, SUM(comision + iva_comision) as comision')->first(),

                'adjudicado' => DB::table('boletas')->whereIn('tipo_prestamo', $t)->where('estatus', 'PE')->whereDate('fecha_vencimiento', '<', $fechaLimiteAdj)
                    ->selectRaw('COUNT(*) as cantidad, SUM(prestamo) as capital, SUM(comision + iva_comision) as comision')->first(),
            ];
        };

        $det_pagos = $obtenerDetalle('PA');
        $det_trad = $obtenerDetalle('TR');

        // --- BLOQUE 4: RESUMEN DE CAPITAL (NUEVO) ---
        $capitalTrabajo = DB::table('sucursal_configs')->value('capital_trabajo') ?? 0;

        // Saldo en caja (Todo el historial de efectivo)
        $totalEntradasCaja = DB::table('movimientos_cajas')->where('tipo', 'ENTRADA')->sum('monto');
        $totalSalidasCaja = DB::table('movimientos_cajas')->where('tipo', 'SALIDA')->sum('monto');
        $saldoEnCaja = $totalEntradasCaja - $totalSalidasCaja;

        // Gastos (Histórico de salidas en caja que no son préstamos o compras)
        // Por simplicidad, tomaremos cualquier SALIDA manual registrada en flujo_conceptos como gasto/compra
        $gastosPendientes = DB::table('movimientos_cajas')
            ->where('tipo', 'SALIDA')
            ->whereNotNull('flujo_concepto_id')
            ->sum('monto');
            
        // Contratos vigentes: Suma de todos los préstamos activos (no cancelados, no liquidados, no enajenados)
        $contratosVigentes = DB::table('boletas')->where('estatus', 'PE')->sum('prestamo');

        // Compras de Joyería
        $comprasJoyeria = DB::table('movimientos_cajas')
            ->where('tipo', 'SALIDA')
            ->where('observaciones', 'LIKE', '%Compra de Joyería%')
            ->sum('monto');

        // Entradas por préstamo (Pagos a capital histórico)
        $entradasPrestamo = DB::table('pagos')->where('estatus', 'A')->sum('capital');

        // Recargos y Almacenaje históricos
        $recargos = DB::table('pagos')->where('estatus', 'A')->sum('recargosNormal');
        $almacenaje = DB::table('pagos')->where('estatus', 'A')->sum(DB::raw('interestotal + ivaIC'));

        // Ventas totales
        $ventasAparatos = DB::table('ventas_electronicos_pagos')->where('estatus', 'A')->sum('importe');
        $ventasOro = DB::table('ventas_joyeria_pagos')->where('estatus', 'A')->sum('importe');

        // Otras entradas (Flujo manual que no es apertura de capital)
        $otrasEntradas = DB::table('movimientos_cajas')
            ->where('tipo', 'ENTRADA')
            ->whereNotNull('flujo_concepto_id')
            ->sum('monto');

        // Abonitos (si existe alguna entrada así, por defecto 0 si no lo usan)
        $abonitos = DB::table('movimientos_cajas')
            ->where('tipo', 'ENTRADA')
            ->where('observaciones', 'LIKE', '%Abonito%')
            ->sum('monto');

        // Fórmula: (Activos) - (Pasivos/Ingresos) - Capital Inicial = Diferencia
        // Activos = Gastos + Saldo en Caja + Contratos Vigentes + Compras
        // Pasivos = Entradas Préstamo + Recargos + Almacenaje + Ventas + OtrasEntradas + Abonitos
        $sumaActivos = $gastosPendientes + $saldoEnCaja + $contratosVigentes + $comprasJoyeria;
        $sumaPasivos = $entradasPrestamo + $recargos + $almacenaje + $ventasAparatos + $ventasOro + $otrasEntradas + $abonitos;
        
        $diferenciaCapital = $sumaActivos - $sumaPasivos - $capitalTrabajo;

        // --- CÁLCULO DE TOTALES FINALES ---
        $cap_final = ($vig_total->monto ?? 0) + ($ven_total->monto ?? 0) + ($adj_total->monto ?? 0);
        // Suma de comisiones de lo pendiente
        $com_final = DB::table('boletas')->where('estatus', 'PE')->sum(DB::raw('comision + iva_comision'));

        // --- RESPUESTA ESTRUCTURADA EXACTA PARA EL FRONT ---
        return response()->json([
            'fecha_reporte' => $fecha,
            'resumen' => [
                'prestamos' => [
                    'cantidad' => $p_nuevos->cantidad ?? 0,
                    'monto' => $p_nuevos->monto ?? 0
                ],
                'cobranza' => [
                    'cantidad' => $c_hoy->cantidad ?? 0,
                    'capital' => $c_hoy->capital ?? 0,
                    'utilidad' => $c_hoy->utilidad ?? 0,
                    'total' => $c_hoy->total ?? 0
                ]
            ],
            'cartera' => [
                'vigente_cant' => $vig_total->cant ?? 0,
                'vigente_monto' => $vig_total->monto ?? 0,
                'vencida_cant' => $ven_total->cant ?? 0,
                'vencida_monto' => $ven_total->monto ?? 0,
                'adjudicada_cant' => $adj_total->cant ?? 0,
                'adjudicada_monto' => $adj_total->monto ?? 0,
                'total_general' => $cap_final
            ],
            'detalle' => [
                'pagos' => [
                    'nuevos' => $det_pagos['nuevos'],
                    'vigente' => $det_pagos['vigente'],
                    'adjudicado' => $det_pagos['adjudicado']
                ],
                'tradicional' => [
                    'nuevos' => $det_trad['nuevos'],
                    'vigente' => $det_trad['vigente'],
                    'adjudicado' => $det_trad['adjudicado']
                ]
            ],
            'resumen_capital' => [
                'capital' => $capitalTrabajo,
                'gastos_cartera_pendiente' => 0,
                'gastos_pendientes_pago' => $gastosPendientes,
                'saldo_en_caja' => $saldoEnCaja,
                'contratos_vigentes' => $contratosVigentes,
                'compras_menores_100' => $comprasJoyeria,
                'entradas_prestamo' => $entradasPrestamo,
                'recargos' => $recargos,
                'almacenaje' => $almacenaje,
                'venta_aparatos' => $ventasAparatos,
                'venta_oro' => $ventasOro,
                'entradas_varias' => $otrasEntradas,
                'abonitos' => $abonitos,
                'diferencia' => $diferenciaCapital
            ],
            'totales' => [
                'total_general' => $cap_final + $com_final
            ]
        ]);
    }

    public function generaUrlFirmadaReporteCartera(Request $request)
    {
        $url = URL::temporarySignedRoute(
                    'reportes.cartera.pdf',
                    now()->addMinutes(1),
                    [
                        'fecha' => $request->fecha,
                        'dias_adjudicar' => $request->dias_adjudicar
                    ]
        );
        return response()->json(['url' => $url]);
    }

    public function generarPDF(Request $request)
    {
        // Reutilizamos la lógica de obtención de datos
        $response = $this->generarReporteCartera($request);
        $data = $response->getData(true); // Convertimos el JSON
        $sucu = SucursalConfig::first();

        $config = [
            'sucursal' => 'PRESTAMO EXPRESS',
            'empresa' => $sucu->nombre_sucursal,
            'fecha_reporte' => $data['fecha_reporte']
        ];

        $pdf = Pdf::loadView('reportes.cartera_pdf', [
            'data' => $data,
            'config' => $config
        ])->setPaper('letter', 'portrait');
        return $pdf->stream("Cartera_{$data['fecha_reporte']}.pdf");
    }
}
