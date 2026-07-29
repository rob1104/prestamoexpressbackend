<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReporteDetallesMovimientosController extends Controller
{
    public function preview(Request $request)
    {
        $movimientos = $this->obtenerMovimientos($request);
        return response()->json(['movimientos' => $movimientos]);
    }

    public function urlFirmadaPdf(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reportes.detalles-movimientos.pdf',
            now()->addMinutes(30),
            $request->all()
        );
        return response()->json(['url' => $url]);
    }

    public function generarPDF(Request $request)
    {
        $movimientos = $this->obtenerMovimientos($request);
        $filtros = $request->input('filtros', []);
        
        $cajaId = $filtros['caja'] ?? 'Todas';
        $nombreCaja = 'TODAS LAS CAJAS';
        if ($cajaId !== 'Todas') {
            $caja = Caja::find($cajaId);
            if ($caja) {
                $nombreCaja = 'CAJA ' . $caja->id;
            }
        }

        $fechaInicio = isset($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->format('d/m/Y') : now()->format('d/m/Y');
        $fechaFin = isset($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->format('d/m/Y') : now()->format('d/m/Y');

        $data = [
            'movimientos' => collect($movimientos)->groupBy('concepto'),
            'caja' => $nombreCaja,
            'rangoFechas' => "DEL $fechaInicio AL $fechaFin",
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ];

        $pdf = Pdf::loadView('reportes.detalles-movimientos', $data);
        return $pdf->stream('Reporte_Detalles_Movimientos.pdf');
    }

    private function obtenerMovimientos(Request $request)
    {
        $filtros = $request->input('filtros', []);
        $conceptos = $request->input('conceptos', []);
        
        $fechaInicio = $filtros['fecha_inicio'] ?? now()->toDateString();
        $fechaFin = $filtros['fecha_fin'] ?? now()->toDateString();
        $caja = $filtros['caja'] ?? 'Todas';

        $movimientos = collect();

        // TRADICIONAL Y PAGOS
        if (in_array('prestamos_nuevos', $conceptos)) {
            $q = DB::table('pagos')
                ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
                ->whereBetween('pagos.fecha', [$fechaInicio, $fechaFin])
                ->where('pagos.estatus', 'A')
                ->where('pagos.tipo_movimiento', 1);
            if ($caja !== 'Todas') $q->where('pagos.caja_id', $caja);
            
            $res = $q->select('pagos.fecha', 'pagos.importe as monto', 'boletas.id as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'PRÉSTAMOS NUEVOS', 'recibo' => $r->recibo, 'tipo' => 'SALIDA', 'monto' => $r->monto]);
            }
        }

        if (in_array('refrendos', $conceptos)) {
            $q = DB::table('pagos')
                ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
                ->whereBetween('pagos.fecha', [$fechaInicio, $fechaFin])
                ->where('pagos.estatus', 'A')
                ->where('pagos.tipo_movimiento', 3);
            if ($caja !== 'Todas') $q->where('pagos.caja_id', $caja);

            $res = $q->select('pagos.fecha', 'pagos.importe as monto', 'pagos.no_pago as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'REFRENDOS TRADICIONAL', 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }
        }

        if (in_array('pagos', $conceptos)) { // Liquidaciones
            $q = DB::table('pagos')
                ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
                ->whereBetween('pagos.fecha', [$fechaInicio, $fechaFin])
                ->where('pagos.estatus', 'A')
                ->where('pagos.tipo_movimiento', 4);
            if ($caja !== 'Todas') $q->where('pagos.caja_id', $caja);

            $res = $q->select('pagos.fecha', 'pagos.importe as monto', 'pagos.no_pago as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'PAGOS / LIQUIDACIONES', 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }
        }

        if (in_array('abonos', $conceptos)) {
            $q = DB::table('pagos')
                ->join('boletas', 'pagos.boleta_id', '=', 'boletas.id')
                ->whereBetween('pagos.fecha', [$fechaInicio, $fechaFin])
                ->where('pagos.estatus', 'A')
                ->where('pagos.tipo_movimiento', 5); // Suponiendo que 5 es Abono
            if ($caja !== 'Todas') $q->where('pagos.caja_id', $caja);

            $res = $q->select('pagos.fecha', 'pagos.importe as monto', 'pagos.no_pago as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'ABONOS', 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }
        }

        // OTROS MOVIMIENTOS DE CAJA (Generales)
        if (in_array('entradas_caja', $conceptos)) {
            $q = DB::table('movimientos_cajas')
                ->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio, $fechaFin])
                ->where('tipo', 'ENTRADA')
                ->whereNull('boleta_id')
                ->whereNull('referencia_id');
            if ($caja !== 'Todas') $q->where('caja_id', $caja);

            $res = $q->select(DB::raw('DATE(created_at) as fecha'), 'monto', 'id as recibo', 'observaciones')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'ENTRADAS A CAJA ' . ($r->observaciones ? "({$r->observaciones})" : ""), 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }
        }

        if (in_array('salidas_caja', $conceptos) || in_array('gastos_generales', $conceptos) || in_array('pagos_varios', $conceptos)) {
            $q = DB::table('movimientos_cajas')
                ->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio, $fechaFin])
                ->where('tipo', 'SALIDA')
                ->whereNull('boleta_id')
                ->whereNull('referencia_id');
            if ($caja !== 'Todas') $q->where('caja_id', $caja);

            $res = $q->select(DB::raw('DATE(created_at) as fecha'), 'monto', 'id as recibo', 'observaciones')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'SALIDAS / GASTOS DE CAJA ' . ($r->observaciones ? "({$r->observaciones})" : ""), 'recibo' => $r->recibo, 'tipo' => 'SALIDA', 'monto' => $r->monto]);
            }
        }

        // VENTAS
        if (in_array('entradas_ventas_efectivo', $conceptos) || in_array('compras_articulos', $conceptos) || in_array('compras_oro', $conceptos)) {
            // Joyeria
            $q = DB::table('ventas_joyeria_pagos')
                ->join('ventas_joyeria_general', 'ventas_joyeria_pagos.venta_id', '=', 'ventas_joyeria_general.id')
                ->whereBetween('ventas_joyeria_pagos.fecha_pago', [$fechaInicio, $fechaFin])
                ->where('ventas_joyeria_pagos.estatus', 'A');
            // Nota: asumiendo que el modelo no registra caja_id en las ventas, omitiremos el filtro por caja en ventas o lo puedes agregar si existe.
            
            $res = $q->select('ventas_joyeria_pagos.fecha_pago as fecha', 'ventas_joyeria_pagos.importe as monto', 'ventas_joyeria_general.id as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'VENTAS / COMPRAS JOYERÍA', 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }

            // Electronicos
            $qe = DB::table('ventas_electronicos_pagos')
                ->join('ventas_electronicos_general', 'ventas_electronicos_pagos.venta_id', '=', 'ventas_electronicos_general.id')
                ->whereBetween('ventas_electronicos_pagos.fecha_pago', [$fechaInicio, $fechaFin])
                ->where('ventas_electronicos_pagos.estatus', 'A');
            
            $rese = $qe->select('ventas_electronicos_pagos.fecha_pago as fecha', 'ventas_electronicos_pagos.importe as monto', 'ventas_electronicos_general.id as recibo')->get();
            foreach($rese as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'VENTAS / COMPRAS ELECTRÓNICOS', 'recibo' => $r->recibo, 'tipo' => 'ENTRADA', 'monto' => $r->monto]);
            }
        }

        // Cancelaciones (ejemplo, estatus 'C' en pagos)
        if (in_array('cancelaciones_empenos', $conceptos) || in_array('cancelaciones_refrendos', $conceptos)) {
            $q = DB::table('pagos')
                ->whereBetween('fecha_cancelacion', [$fechaInicio, $fechaFin])
                ->where('estatus', 'C');
            if ($caja !== 'Todas') $q->where('caja_id', $caja);

            $res = $q->select('fecha_cancelacion as fecha', 'importe as monto', 'no_pago as recibo')->get();
            foreach($res as $r) {
                $movimientos->push(['fecha' => $r->fecha, 'concepto' => 'CANCELACIONES (PRÉSTAMOS/REFRENDOS/PAGOS)', 'recibo' => $r->recibo, 'tipo' => 'SALIDA', 'monto' => $r->monto]);
            }
        }

        return $movimientos->sortBy('fecha')->values()->all();
    }
}
