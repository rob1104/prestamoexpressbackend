<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use App\Models\CierreDiario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    private function getDatosBoletasDiarias(Request $request)
    {
        $fechaInicial = $request->input('fecha_inicial', now()->toDateString());
        $fechaFinal = $request->input('fecha_final', now()->toDateString());
        $tipoReporte = $request->input('tipo_reporte', 'boletas');

        $boletasDiarias = collect();

        if ($tipoReporte === 'boletas') {
            // Boletas creadas
            $boletas = \App\Models\Boleta::with(['cliente', 'tradicional'])
                ->whereBetween(DB::raw('DATE(fecha_boleta)'), [$fechaInicial, $fechaFinal])
                ->get();

            foreach ($boletas as $boleta) {
                $cliente = $boleta->cliente;
                $nombreCliente = $cliente ? trim($cliente->nombre . ' ' . $cliente->apellido_paterno) : 'PÃƒÅ¡BLICO GENERAL';

                $capital = (float)$boleta->prestamo;
                $almacenaje = 0;
                $iva = 0;
                $admin = 0;
                $interes = 0;
                $interes_cobrado = (float)$boleta->comision;

                if ($boleta->tipo_prestamo === 'tradicional') {
                    $trad = $boleta->tradicional->first();
                    if ($trad) {
                        $almacenaje = (float)$trad->almacenaje;
                        $iva = (float)$trad->iva_interes;
                        $admin = (float)$trad->administracion;
                        $interes = (float)$trad->interesdividido;
                        if ($interes == 0 && $almacenaje == 0 && $admin == 0 && $iva == 0) {
                            $interes = $interes_cobrado; // fallback
                        }
                    }
                } else {
                    $subtotalSinIva = $interes_cobrado / 1.16;
                    $iva = round($interes_cobrado - $subtotalSinIva, 2);
                    $almacenaje = round($subtotalSinIva * 0.739027, 2);
                    $interes = round($subtotalSinIva * 0.260973, 2);
                    $diferencia = round($subtotalSinIva - ($almacenaje + $interes), 2);
                    $almacenaje += $diferencia;
                }

                $boletasDiarias->push([
                    'id' => $boleta->id,
                    'fecha' => Carbon::parse($boleta->fecha_boleta)->toDateString(),
                    'folio' => $boleta->id,
                    'pag' => 0,
                    'tipo' => 'B',
                    'no_cliente' => $cliente ? $cliente->id : '',
                    'cliente' => $nombreCliente,
                    'capital' => $capital,
                    'interes' => $interes,
                    'custodia' => $almacenaje,
                    'administracion' => $admin,
                    'iva' => $iva,
                    'interes_iva' => $interes_cobrado,
                    'total' => (float)$boleta->total_pagar
                ]);
            }

            return $boletasDiarias->sortBy('folio')->values();
        }

        // Pagos y Refrendos
        $query = \App\Models\Pago::with(['boleta.cliente', 'boleta.tradicional'])
            ->whereBetween(DB::raw('DATE(fecha)'), [$fechaInicial, $fechaFinal]);

        if ($tipoReporte === 'pagos') {
            // Pagos: en abonos tipo 4 o 'P'
            $query->whereIn('tipo_movimiento', [4, 'P']);
        } elseif ($tipoReporte === 'refrendos') {
            // Refrendos: tradicional tipo 1 o 'R'
            $query->whereIn('tipo_movimiento', [1, 'R']);
        }

        $pagos = $query->get();

        foreach ($pagos as $pago) {
            $boleta = $pago->boleta;
            if (!$boleta) continue;
            
            $cliente = $boleta->cliente;
            $nombreCliente = $cliente ? trim($cliente->nombre . ' ' . $cliente->apellido_paterno) : 'PÃƒÅ¡BLICO GENERAL';

            $interes_cobrado = (float)$pago->interestotal;
            
            $almacenaje = 0;
            $iva = 0;
            $admin = 0;
            $interes = 0;

            if ($boleta->tipo_prestamo === 'tradicional') {
                // Para tradicional, el capital pagado solo existe si es DesempeÃƒÂ±o (Tipo 2 o 'D')
                if ($pago->tipo_movimiento == 2 || strtoupper(substr($pago->tipo_movimiento, 0, 1)) === 'D') {
                    $capital = (float)$pago->prestamo;
                } else {
                    $capital = 0; // Refrendo no paga capital
                }

                $trad = $boleta->tradicional->first();
                if ($trad) {
                    $almacenaje_mes = (float)$trad->almacenaje;
                    $iva_mes = (float)$trad->iva_interes;
                    $admin_mes = (float)$trad->administracion;
                    $comision_total = (float)$boleta->comision;

                    if ($comision_total > 0) {
                        $pct_almacenaje = $almacenaje_mes / $comision_total;
                        $pct_iva = $iva_mes / $comision_total;
                        $pct_admin = $admin_mes / $comision_total;

                        $almacenaje = round($interes_cobrado * $pct_almacenaje, 2);
                        $iva = round($interes_cobrado * $pct_iva, 2);
                        $admin = round($interes_cobrado * $pct_admin, 2);
                        $interes = round($interes_cobrado - $almacenaje - $iva - $admin, 2);
                    }
                }
            } else {
                // Pagos
                $boletaPago = \App\Models\BoletaPago::where('boleta_id', $boleta->id)->where('num_pago', $pago->no_pago)->first();
                if ($boletaPago) {
                    $capital = (float)$boletaPago->importe;
                    $interes_cobrado = (float)$boletaPago->comision;
                } else {
                    // Fallback
                    $capital = (float)$pago->prestamo; 
                }

                $subtotalSinIva = $interes_cobrado / 1.16;
                $iva = round($interes_cobrado - $subtotalSinIva, 2);
                $almacenaje = round($subtotalSinIva * 0.739027, 2);
                $interes = round($subtotalSinIva * 0.260973, 2);
                
                $diferencia = round($subtotalSinIva - ($almacenaje + $interes), 2);
                $almacenaje += $diferencia;
            }

            // 'R' = REFRENDO (1), 'D' = DESEMPEÃƒâ€˜O (2), 'P' = PAGO (4)
            $tipoNum = $pago->tipo_movimiento;
            $tipoStr = $tipoNum == 1 ? 'R' : ($tipoNum == 2 ? 'D' : ($tipoNum == 4 ? 'P' : $tipoNum));

            $boletasDiarias->push([
                'id' => $pago->id, // just for unique key
                'fecha' => Carbon::parse($pago->fecha)->toDateString(),
                'folio' => $boleta->id,
                'pag' => $pago->no_pago ?? 0,
                'tipo' => $tipoStr,
                'no_cliente' => $cliente ? $cliente->id : '',
                'cliente' => $nombreCliente,
                'capital' => $capital,
                'interes' => $interes,
                'custodia' => $almacenaje,
                'administracion' => $admin,
                'iva' => $iva,
                'interes_iva' => $interes_cobrado,
                'total' => (float)$pago->totalPagado
            ]);
        }

        return $boletasDiarias->sortBy('folio')->values();
    }

    public function boletasDiarias(Request $request)
    {
        return response()->json($this->getDatosBoletasDiarias($request));
    }

    public function urlFirmadaBoletasDiariasPDF(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reportes.boletas_diarias_pdf',
            now()->addMinutes(30),
            $request->all()
        );
        return response()->json(['url' => $url]);
    }

    public function reporteBoletasDiariasPDF(Request $request)
    {
        $boletas = $this->getDatosBoletasDiarias($request);
        $pdf = Pdf::loadView('reportes.boletas-diarias', [
            'boletas' => $boletas,
            'filtros' => $request->all(),
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ])->setPaper('a4', 'portrait');
        
        return $pdf->stream('Relacion_Movimientos.pdf');
    }

    private function getDatosBoletasVencidas(Request $request)
    {
        $fechaCorte = $request->input('fecha_corte', now()->toDateString());
        $diasTolerancia = $request->input('dias_tolerancia', 0);
        $estatus = $request->input('estatus', 'Todos');
        
        $query = Boleta::with(['cliente', 'partidas'])->where('estatus', 'PE');
        
        $boletas = $query->get()->map(function($boleta) use ($fechaCorte) {
            $diasVencido = Carbon::parse($boleta->fecha_vencimiento)->diffInDays($fechaCorte, false);
            
            return [
                'folio' => $boleta->id,
                'cliente' => $boleta->cliente ? trim($boleta->cliente->nombre . ' ' . $boleta->cliente->apellido_paterno) : 'PÃƒÅ¡BLICO GENERAL',
                'articulo' => $boleta->partidas->pluck('descripcion')->join(', ') ?: 'N/A',
                'fecha_prestamo' => $boleta->fecha_boleta ? $boleta->fecha_boleta->toDateString() : null,
                'fecha_vencimiento' => $boleta->fecha_vencimiento ? Carbon::parse($boleta->fecha_vencimiento)->toDateString() : null,
                'dias_vencido' => $diasVencido,
                'monto' => $boleta->prestamo
            ];
        });

        if ($estatus === 'Vencidas') {
            $boletas = $boletas->where('dias_vencido', '>', $diasTolerancia);
        } elseif ($estatus === 'Por Vencer') {
            $boletas = $boletas->where('dias_vencido', '<=', $diasTolerancia);
        }

        return $boletas->sortBy('dias_vencido')->values();
    }

    public function boletasVencidas(Request $request)
    {
        $boletas = $this->getDatosBoletasVencidas($request);
        return response()->json($boletas);
    }

    public function exportarBoletasPdf(Request $request)
    {
        $boletas = $this->getDatosBoletasVencidas($request);
        $pdf = Pdf::loadView('reportes.boletas-vencidas', [
            'boletas' => $boletas,
            'filtros' => $request->all(),
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ]);
        return $pdf->download('Reporte_Boletas_Vencidas.pdf');
    }

    public function boletasVencidasUrlFirmadaPdf(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reportes.boletas-vencidas.pdf',
            now()->addMinutes(30),
            $request->all()
        );
        return response()->json(['url' => $url]);
    }
    
    public function boletasVencidasUrlFirmadaExcel(Request $request)
    {
        $url = URL::temporarySignedRoute(
            'reportes.boletas-vencidas.excel',
            now()->addMinutes(30),
            $request->all()
        );
        return response()->json(['url' => $url]);
    }


    private function getDatosVentasDetallado(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());
        $categoria = $request->input('categoria', 'Todas');

        $ventasJoyeria = collect();
        if ($categoria === 'Todas' || $categoria === 'Joyería') {
            $ventasJoyeria = DB::table('ventas_joyeria_general as g')
                ->join('ventas_joyeria_detalle as d', 'g.id', '=', 'd.venta_id')
                ->leftJoin('movimientos_cajas as m', function($join) {
                    $join->on('m.referencia_id', '=', 'g.id')
                         ->where('m.tipo', 'SALIDA')
                         ->where('m.observaciones', 'like', 'Compra de Joyer%');
                })
                ->whereNull('m.id') // Excluye las que son compras
                ->whereBetween('g.fecha_movimiento', [$fechaInicio, $fechaFin])
                ->where('g.estatus', '!=', 'C')
                ->select(
                    'g.id as folio',
                    'g.fecha_movimiento as fecha',
                    'g.cliente',
                    'd.concepto as articulo',
                    'd.categoria',
                    DB::raw('0 as costo'),
                    'd.importe as precio_venta'
                )->get()->map(function($item) {
                    $item->categoria = 'Joyería';
                    $item->utilidad = $item->precio_venta;
                    return $item;
                });
        }

        $ventasElectronicos = collect();
        if ($categoria === 'Todas' || $categoria === 'Electrónicos') {
            $ventasElectronicos = DB::table('ventas_electronicos_general as g')
                ->join('ventas_electronicos_detalle as d', 'g.id', '=', 'd.venta_id')
                ->leftJoin('movimientos_cajas as m', function($join) {
                    $join->on('m.referencia_id', '=', 'g.id')
                         ->where('m.tipo', 'SALIDA')
                         ->where('m.observaciones', 'like', 'Compra de Electr%');
                })
                ->whereNull('m.id') // Excluye las que son compras
                ->whereBetween('g.fecha_movimiento', [$fechaInicio, $fechaFin])
                ->where('g.estatus', '!=', 'C')
                ->select(
                    'g.id as folio',
                    'g.fecha_movimiento as fecha',
                    'g.cliente',
                    'd.descripcion as articulo',
                    'd.clasificacion as categoria',
                    DB::raw('0 as costo'),
                    'd.importe as precio_venta'
                )->get()->map(function($item) {
                    $item->categoria = 'Electrónicos';
                    $item->utilidad = $item->precio_venta;
                    return $item;
                });
        }

        $todasVentas = $ventasJoyeria->merge($ventasElectronicos)->sortByDesc('fecha')->values();

        $totales = [
            'monto_total' => $todasVentas->sum('precio_venta'),
            'utilidad_total' => $todasVentas->sum('utilidad'),
            'cantidad_articulos' => $todasVentas->count(),
        ];

        return [
            'ventas' => $todasVentas,
            'totales' => $totales
        ];
    }

    public function ventasDetallado(Request $request)
    {
        $datos = $this->getDatosVentasDetallado($request);
        return response()->json($datos);
    }

    public function exportarVentasPdf(Request $request)
    {
        $datos = $this->getDatosVentasDetallado($request);
        $pdf = Pdf::loadView('reportes.ventas-detallado', [
            'ventas' => $datos['ventas'],
            'totales' => $datos['totales'],
            'filtros' => $request->all(),
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ]);
        return $pdf->download('Reporte_Ventas_Detallado.pdf');
    }

    public function ventasUrlFirmadaPdf(Request $request)
    {
        $url = URL::temporarySignedRoute('reportes.ventas.pdf', now()->addMinutes(30), $request->all());
        return response()->json(['url' => $url]);
    }
    
    public function ventasUrlFirmadaExcel(Request $request)
    {
        $url = URL::temporarySignedRoute('reportes.ventas.excel', now()->addMinutes(30), $request->all());
        return response()->json(['url' => $url]);
    }
    
    private function getDatosCierreDiario(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());

        $cierres = CierreDiario::whereBetween('fecha_cierre', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_cierre', 'desc')
            ->get();

        $totales = [
            'prestamos_nuevos' => $cierres->sum('prestamos_nuevos'),
            'capital_recuperado' => $cierres->sum('capital_recuperado'),
            'interes_recuperado' => $cierres->sum('interes_recuperado'),
            'recargos_cobrados' => $cierres->sum('recargos_cobrados'),
            'ventas_joyeria' => $cierres->sum('ventas_joyeria'),
            'ventas_electronicos' => $cierres->sum('ventas_electronicos'),
            'entradas_otros' => $cierres->sum('entradas_otros'),
            'salidas_otros' => $cierres->sum('salidas_otros'),
        ];

        return [
            'cierres' => $cierres,
            'totales' => $totales
        ];
    }

    public function cierreDiario(Request $request)
    {
        $datos = $this->getDatosCierreDiario($request);
        return response()->json($datos);
    }

    public function exportarCierreDiarioPdf(Request $request)
    {
        $datos = $this->getDatosCierreDiario($request);
        $pdf = Pdf::loadView('reportes.cierre-diario', [
            'cierres' => $datos['cierres'],
            'totales' => $datos['totales'],
            'filtros' => $request->all(),
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ]);
        return $pdf->download('Reporte_Cierre_Diario.pdf');
    }

    public function cierreDiarioUrlFirmadaPdf(Request $request)
    {
        $url = URL::temporarySignedRoute('reportes.cierre-diario.pdf', now()->addMinutes(30), $request->all());
        return response()->json(['url' => $url]);
    }
    
    // --- MÃƒâ€°TODOS DUMMY (Ya no se usan pero los dejamos por compatibilidad por si se llega accidentalmente) ---
    public function exportarBoletasExcel(Request $request) { return "Exportación Excel Boletas Vencidas. Filtros: " . json_encode($request->all()); }
    public function exportarVentasExcel(Request $request) { return "Exportación Excel Ventas. Filtros: " . json_encode($request->all()); }

    private function getDatosComprasDetallado(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', now()->toDateString());
        $fechaFin = $request->input('fecha_fin', now()->toDateString());
        $categoria = $request->input('categoria', 'Todas');

        $comprasJoyeria = collect();
        if ($categoria === 'Todas' || $categoria === 'Joyería') {
            $comprasJoyeria = DB::table('ventas_joyeria_general as g')
                ->join('ventas_joyeria_detalle as d', 'g.id', '=', 'd.venta_id')
                ->join('movimientos_cajas as m', 'm.referencia_id', '=', 'g.id')
                ->where('m.tipo', 'SALIDA')
                ->where('m.observaciones', 'like', 'Compra de Joyer%')
                ->whereBetween('g.fecha_movimiento', [$fechaInicio, $fechaFin])
                ->where('g.estatus', '!=', 'C')
                ->select(
                    'g.id as folio',
                    'g.fecha_movimiento as fecha',
                    'g.cliente',
                    'd.concepto as articulo',
                    'd.categoria as categoria_detalle',
                    'd.importe as precio_compra'
                )->get()->map(function($item) {
                    $item->categoria = 'Joyería';
                    return $item;
                });
        }

        $comprasElectronicos = collect();
        if ($categoria === 'Todas' || $categoria === 'Electrónicos') {
            $comprasElectronicos = DB::table('ventas_electronicos_general as g')
                ->join('ventas_electronicos_detalle as d', 'g.id', '=', 'd.venta_id')
                ->join('movimientos_cajas as m', 'm.referencia_id', '=', 'g.id')
                ->where('m.tipo', 'SALIDA')
                ->where('m.observaciones', 'like', 'Compra de Electr%')
                ->whereBetween('g.fecha_movimiento', [$fechaInicio, $fechaFin])
                ->where('g.estatus', '!=', 'C')
                ->select(
                    'g.id as folio',
                    'g.fecha_movimiento as fecha',
                    'g.cliente',
                    'd.descripcion as articulo',
                    'd.clasificacion as categoria_detalle',
                    'd.importe as precio_compra'
                )->get()->map(function($item) {
                    $item->categoria = 'Electrónicos';
                    return $item;
                });
        }

        $todasCompras = $comprasJoyeria->merge($comprasElectronicos)->sortByDesc('fecha')->values();

        $totales = [
            'monto_total' => $todasCompras->sum('precio_compra'),
            'cantidad_articulos' => $todasCompras->count(),
        ];

        return [
            'compras' => $todasCompras,
            'totales' => $totales
        ];
    }

    public function comprasDetallado(Request $request)
    {
        $datos = $this->getDatosComprasDetallado($request);
        return response()->json($datos);
    }

    public function exportarComprasPdf(Request $request)
    {
        $datos = $this->getDatosComprasDetallado($request);
        $pdf = Pdf::loadView('reportes.compras-detallado', [
            'compras' => $datos['compras'],
            'totales' => $datos['totales'],
            'filtros' => $request->all(),
            'fechaImpresion' => now()->format('d/m/Y H:i:s')
        ]);
        return $pdf->stream('Reporte_Compras_Detallado.pdf');
    }

    public function exportarComprasExcel(Request $request)
    {
        // TODO: Implement Excel for compras
        return response()->json(['message' => 'Not implemented yet'], 501);
    }

    public function comprasUrlFirmadaPdf(Request $request)
    {
        $url = URL::temporarySignedRoute('reportes.compras.pdf', now()->addMinutes(30), $request->all());
        return response()->json(['url' => $url]);
    }
    
    public function comprasUrlFirmadaExcel(Request $request)
    {
        $url = URL::temporarySignedRoute('reportes.compras.excel', now()->addMinutes(30), $request->all());
        return response()->json(['url' => $url]);
    }
}




