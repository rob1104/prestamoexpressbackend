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
                'cliente' => $boleta->cliente ? trim($boleta->cliente->nombre . ' ' . $boleta->cliente->apellido_paterno) : 'PÚBLICO GENERAL',
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
    
    // --- MÉTODOS DUMMY (Ya no se usan pero los dejamos por compatibilidad por si se llega accidentalmente) ---
    public function exportarBoletasExcel(Request $request) { return "Exportación Excel Boletas Vencidas. Filtros: " . json_encode($request->all()); }
    public function exportarVentasExcel(Request $request) { return "Exportación Excel Ventas. Filtros: " . json_encode($request->all()); }
}
