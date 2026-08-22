<?php

namespace App\Http\Controllers;

use App\Models\SucursalConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class ReporteFlujoCajaController extends Controller
{
    public function generarFlujoCaja(Request $request)
    {
        $f1 = $request->input('fecha_inicio') . ' 00:00:00';
        $f2 = $request->input('fecha_fin') . ' 23:59:59';
        $cajaId = $request->input('caja_id', 1); // Por defecto Caja 1

        // --- 1. CÁLCULO DEL SALDO INICIAL (Histórico antes de f1) ---
        $entradasH = DB::table('pagos')
                ->where('fecha', '<', $f1)
                ->where('estatus', 'A')
                ->where('caja_id', $cajaId)
                ->sum('importe') +
            DB::table('movimientos_cajas')
                ->whereDate('created_at', '<', $f1)
                ->where('tipo', 'ENTRADA')
                ->where('caja_id', $cajaId)
                ->whereNull('boleta_id')
                ->sum('monto');

        $salidasH = DB::table('boletas')
                ->whereDate('fecha_boleta', '<', $f1)
                ->whereNotIn('estatus', ['ANULADO', 'CA'])
                ->sum('prestamo') +
            DB::table('movimientos_cajas')
                ->whereDate('created_at', '<', $f1)
                ->where('tipo', 'SALIDA')
                ->where('caja_id', $cajaId)
                ->whereNull('boleta_id')
                ->sum('monto');

        $saldoInicial = $entradasH - $salidasH;

        // --- 2. MOVIMIENTOS DEL PERIODO (f1 a f2)  ---

        // 2.1 PAGOS (Plan Pagos / Auto)
        $pagos_entradas = DB::table('pagos')
            ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
            ->whereBetween('pagos.fecha', [$f1, $f2])
            ->where('pagos.estatus', 'A')
            ->where('pagos.caja_id', $cajaId)
            ->whereIn('boletas.tipo_prestamo', ['pagos', 'auto'])
            ->selectRaw("SUM(pagos.capital + pagos.interestotal + pagos.ivaIC) as pagos, SUM(pagos.recargosNormal) as recargos")
            ->first();

        $pagos_nc = DB::table('nota_creditos')
            ->whereBetween('created_at', [$f1, $f2])
            ->where('estatus', 'aplicado')
            ->where('caja_id', $cajaId)
            ->whereIn('tipo_prestamo', ['pagos', 'auto'])
            ->sum('cantidad');

        // 2.2 TRADICIONAL
        $trad_refrendos = DB::table('pagos')
            ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
            ->whereBetween('pagos.fecha', [$f1, $f2])
            ->where('pagos.estatus', 'A')
            ->where('pagos.caja_id', $cajaId)
            ->where('boletas.tipo_prestamo', 'tradicional')
            ->whereIn('pagos.tipo_movimiento', [1, 3])
            ->selectRaw("SUM(pagos.interestotal + pagos.ivaIC) as comision, SUM(pagos.recargosNormal) as recargos")
            ->first();

        $trad_liquidaciones = DB::table('pagos')
            ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
            ->whereBetween('pagos.fecha', [$f1, $f2])
            ->where('pagos.estatus', 'A')
            ->where('pagos.caja_id', $cajaId)
            ->where('boletas.tipo_prestamo', 'tradicional')
            ->where('pagos.tipo_movimiento', 4)
            ->selectRaw("SUM(pagos.capital) as capital, SUM(pagos.interestotal + pagos.ivaIC) as comision, SUM(pagos.recargosNormal) as recargos")
            ->first();

        $trad_nc = DB::table('nota_creditos')
            ->whereBetween('created_at', [$f1, $f2])
            ->where('estatus', 'aplicado')
            ->where('caja_id', $cajaId)
            ->where('tipo_prestamo', 'tradicional')
            ->sum('cantidad');

        // 2.3 VENTAS
        $ventas_elec = DB::table('ventas_electronicos_pagos')
            ->whereBetween('fecha_pago', [$f1, $f2])
            ->where('estatus', 'A')
            ->sum('importe');

        $ventas_oro = DB::table('ventas_joyeria_pagos')
            ->whereBetween('fecha_pago', [$f1, $f2])
            ->where('estatus', 'A')
            ->sum('importe');

        // 2.4 OTROS MOVIMIENTOS
        $otrosMov = DB::table('movimientos_cajas')
            ->whereBetween('created_at', [$f1, $f2])
            ->where('caja_id', $cajaId)
            ->whereNull('boleta_id')
            ->selectRaw("
                SUM(CASE WHEN tipo = 'ENTRADA' AND observaciones LIKE '%Fondo%' THEN monto ELSE 0 END) as entradas_fondo,
                SUM(CASE WHEN tipo = 'ENTRADA' AND observaciones NOT LIKE '%Fondo%' THEN monto ELSE 0 END) as entradas_otros,
                SUM(CASE WHEN tipo = 'SALIDA' AND observaciones LIKE 'Compra de Joyería%' THEN monto ELSE 0 END) as salidas_compras,
                SUM(CASE WHEN tipo = 'SALIDA' AND observaciones NOT LIKE 'Compra de Joyería%' THEN monto ELSE 0 END) as salidas_otros
            ")->first();

        // --- 3. MOVIMIENTOS DEL PERIODO (SALIDAS) ---
        $salidas_pagos = DB::table('boletas')
            ->whereBetween('fecha_boleta', [$f1, $f2])
            ->whereNotIn('estatus', ['ANULADO', 'CA'])
            ->whereIn('tipo_prestamo', ['pagos', 'auto'])
            ->sum('prestamo');

        $salidas_trad = DB::table('boletas')
            ->whereBetween('fecha_boleta', [$f1, $f2])
            ->whereNotIn('estatus', ['ANULADO', 'CA'])
            ->where('tipo_prestamo', 'tradicional')
            ->sum('prestamo');

        // CALCULO TOTALES
        $total_pagos_entradas = ($pagos_entradas->pagos ?? 0) + ($pagos_entradas->recargos ?? 0) - $pagos_nc;
        $total_trad_entradas = ($trad_refrendos->comision ?? 0) + ($trad_refrendos->recargos ?? 0) +
                               ($trad_liquidaciones->capital ?? 0) + ($trad_liquidaciones->comision ?? 0) + ($trad_liquidaciones->recargos ?? 0) - $trad_nc;

        $total_entradas = $total_pagos_entradas + $total_trad_entradas + $ventas_elec + $ventas_oro + ($otrosMov->entradas_otros ?? 0);
        $total_salidas = $salidas_pagos + $salidas_trad + ($otrosMov->salidas_otros ?? 0) + ($otrosMov->salidas_compras ?? 0);

        return response()->json([
            'config' => [
                'caja' => 'CAJA ' . $cajaId,
                'fecha_rango' => 'DEL ' . date('d-M-Y', strtotime($f1)) . ' AL ' . date('d-M-Y', strtotime($f2)),
                'saldo_inicial' => $saldoInicial,
                'fondo_fijo' => $otrosMov->entradas_fondo ?? 0,
                'saldo_final' => $saldoInicial + ($otrosMov->entradas_fondo ?? 0) + $total_entradas - $total_salidas,
                'fecha_impresion' => date('d-M-Y'),
                'hora_impresion' => date('h:i:s a')
            ],
            'entradas' => [
                'pagos' => [
                    'pagos' => $pagos_entradas->pagos ?? 0,
                    'recargos' => $pagos_entradas->recargos ?? 0,
                    'comision_restructura' => 0,
                    'comision_cambio_plan' => 0,
                    'comision_cambio_trad' => 0,
                    'notas_credito' => $pagos_nc,
                    'total' => $total_pagos_entradas
                ],
                'tradicional' => [
                    'refrendos' => [
                        'comision' => $trad_refrendos->comision ?? 0,
                        'recargos' => $trad_refrendos->recargos ?? 0,
                        'total' => ($trad_refrendos->comision ?? 0) + ($trad_refrendos->recargos ?? 0)
                    ],
                    'liquidaciones' => [
                        'capital' => $trad_liquidaciones->capital ?? 0,
                        'comision' => $trad_liquidaciones->comision ?? 0,
                        'recargos' => $trad_liquidaciones->recargos ?? 0,
                        'total' => ($trad_liquidaciones->capital ?? 0) + ($trad_liquidaciones->comision ?? 0) + ($trad_liquidaciones->recargos ?? 0)
                    ],
                    'notas_credito' => $trad_nc,
                    'total' => $total_trad_entradas
                ],
                'ventas' => [
                    'electronicos' => [
                        'ventas' => $ventas_elec,
                        'separado' => 0,
                        'liq_separado' => 0,
                        'total' => $ventas_elec
                    ],
                    'oro' => [
                        'ventas' => $ventas_oro,
                        'separado' => 0,
                        'liq_separado' => 0,
                        'total' => $ventas_oro
                    ]
                ],
                'otros' => [
                    'pagos_servicios' => [
                        'importe' => 0,
                        'comision' => 0,
                        'total' => 0
                    ],
                    'venta_dolares' => 0,
                    'aportaciones_cajas' => 0,
                    'notas_extravio' => 0,
                    'entradas_caja' => $otrosMov->entradas_otros ?? 0,
                    'abonitos' => 0
                ],
                'total_general' => $total_entradas
            ],
            'salidas' => [
                'pagos' => [
                    'prestamos_nuevos' => $salidas_pagos,
                    'canc_capital_cambio_plan' => 0,
                    'canc_capital_cambio_trad' => 0,
                    'total' => $salidas_pagos
                ],
                'tradicional' => [
                    'prestamos_nuevos' => $salidas_trad,
                    'total' => $salidas_trad
                ],
                'otros' => [
                    'compra_dolares' => 0,
                    'retiros_cajas' => 0,
                    'gastos_generales' => 0,
                    'salidas_caja' => $otrosMov->salidas_otros ?? 0,
                    'compra_oro_plata' => $otrosMov->salidas_compras ?? 0,
                    'depositos_vouchers' => 0
                ],
                'total_general' => $total_salidas
            ],
            'dolares' => [
                'fondo_fijo' => 0,
                'saldo_inicial' => 0,
                'aportaciones' => 0,
                'retiros' => 0,
                'compras' => 0,
                'ventas' => 0,
                'total' => 0,
                'saldo_final' => 0,
                'menos_fondo' => 0,
                'tipo_cambio_compra' => 0,
                'tipo_cambio_venta' => 0,
                'utilidad' => 0
            ]
        ]);
    }

    public function generarUrlFirmada(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reporte.flujocaja.pdf',
            now()->addMinutes(1),
            [
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'caja_id' => $request->caja_id ?? 1
            ]
        );

        return response()->json(['url' => $url]);
    }

    public function generarPDF(Request $request)
    {
        $data = $this->generarFlujoCaja($request)->getData(true);
        $sucu = SucursalConfig::first();
        $pdf = Pdf::loadView('reportes.flujocaja.pdf', [
            'reporte' => $data,
            'sucursal' => 'PRESTAMO EXPRESS',
            'empresa' => $sucu->nombre_sucursal ?? 'MATRIZ'
        ])->setPaper('letter', 'portrait');
        return $pdf->stream("Flujo_Caja_" . date('Y-m-d') . ".pdf");
    }
}
