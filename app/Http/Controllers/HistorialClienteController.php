<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use App\Models\Boleta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class HistorialClienteController extends Controller
{
    /**
     * Busca por cliente o por folio y devuelve las boletas
     */
    public function buscar(Request $request)
    {
        $tipo = $request->input('tipo'); // FOLIO, FECHA BOLETA, DESCRIPCION, CLIENTE, PRESTAMO, NUMERO BOLSA
        $queryText = $request->input('query');

        if (!$queryText) {
            return response()->json([], 200);
        }

        $query = Boleta::with(['cliente', 'partidas']);

        switch (strtoupper($tipo)) {
            case '1. FOLIO':
            case 'FOLIO':
                $query->where('id', $queryText);
                break;
                
            case '2. FECHA BOLETA':
            case 'FECHA BOLETA':
                // Puede ser YYYY-MM-DD o DD/MM/YYYY o similar. Intentaremos match parcial
                $query->where('fecha_boleta', 'LIKE', "%{$queryText}%");
                break;
                
            case '3. DESCRIPCION':
            case 'DESCRIPCION':
                $query->whereHas('partidas', function ($q) use ($queryText) {
                    $q->where('descripcion', 'LIKE', "%{$queryText}%");
                });
                break;
                
            case '4. CLIENTE':
            case 'CLIENTE':
                $terms = explode(' ', $queryText);
                $query->whereHas('cliente', function($q) use ($terms) {
                    foreach ($terms as $term) {
                        if (!empty($term)) {
                            $q->where(function ($subQ) use ($term) {
                                $subQ->where('nombre', 'LIKE', "%{$term}%")
                                     ->orWhere('apellido_paterno', 'LIKE', "%{$term}%")
                                     ->orWhere('apellido_materno', 'LIKE', "%{$term}%");
                            });
                        }
                    }
                });
                break;
                
            case '5. PRESTAMO':
            case 'PRESTAMO':
                $query->where('prestamo', $queryText);
                break;
                
            case '6. NUMERO BOLSA':
            case 'NUMERO BOLSA':
                $query->where('no_bolsa', 'LIKE', "%{$queryText}%");
                break;
        }

        $boletas = $query->orderBy('id', 'desc')->take(100)->get(); // limit to 100 for performance
        return response()->json($boletas, 200);
    }

    /**
     * Obtiene los detalles completos de una boleta para el tab "Datos Generales" y "Desglose"
     */
    public function obtenerBoleta($id)
    {
        $boleta = Boleta::with([
            'cliente', 
            'partidas', 
            'pagos.user', 
            'user',
            'categoria'
        ])->find($id);

        if (!$boleta) {
            return response()->json(['message' => 'Boleta no encontrada'], 404);
        }

        return response()->json($boleta, 200);
    }

    /**
     * Obtiene las estadísticas generales de todas las boletas de un cliente
     */
    public function historiaGeneral($clienteId)
    {
        $boletas = Boleta::with('pagos')->where('cliente_id', $clienteId)->get();

        $boletasTradicional = $boletas->where('tipo_prestamo', 'tradicional');
        $boletasPagos = $boletas->where('tipo_prestamo', 'pagos');

        // Historia Tradicional
        $trad_prestamos = $boletasTradicional->count();
        $trad_importe = $boletasTradicional->sum('prestamo');
        
        $trad_refrendadas = $boletasTradicional->where('estatus', 'BOLETA REFRENDADA')->count();
        $trad_desempenadas = $boletasTradicional->where('estatus', 'BOLETA DESEMPEÑADA')->count();
        $trad_adjudicadas = $boletasTradicional->whereIn('estatus', ['ADJUDICADA', 'ADJUDICACION REAL', 'ADJUDICACION FISICA'])->count();
        $trad_comercializacion = $boletasTradicional->where('estatus', 'EN COMERCIALIZACION')->count();
        $trad_vigentes = $boletasTradicional->where('estatus', 'BOLETA VIGENTE')->count();
        
        // Sumar todos los pagos en tradicional
        $trad_total_pagado = 0;
        $trad_recargos = 0;
        foreach($boletasTradicional as $b) {
            $pagosRel = $b->getRelation('pagos');
            $trad_total_pagado += $pagosRel ? $pagosRel->sum('totalPagado') : 0;
            $trad_recargos += $pagosRel ? $pagosRel->sum('recargosNormal') : 0;
        }

        // Historia Pagos
        $pagos_prestamos = $boletasPagos->count();
        $pagos_importe = $boletasPagos->sum('prestamo');

        $pagos_terminados = $boletasPagos->whereIn('estatus', ['BOLETA LIQUIDADA NORMALMENTE', 'BOLETA DESEMPEÑADA'])->count();
        $pagos_vigentes = $boletasPagos->where('estatus', 'BOLETA VIGENTE')->count();

        // Sumar todos los pagos en pagos
        $pagos_total_pagado = 0;
        $pagos_recargos = 0;
        foreach($boletasPagos as $b) {
            $pagosRel = $b->getRelation('pagos');
            $pagos_total_pagado += $pagosRel ? $pagosRel->sum('totalPagado') : 0;
            $pagos_recargos += $pagosRel ? $pagosRel->sum('recargosNormal') : 0;
        }

        return response()->json([
            'tradicional' => [
                'prestamos' => $trad_prestamos,
                'importe' => $trad_importe,
                'refrendadas' => $trad_refrendadas,
                'desempenadas' => $trad_desempenadas,
                'adjudicaciones' => $trad_adjudicadas,
                'en_comercializacion' => $trad_comercializacion,
                'vigentes' => $trad_vigentes,
                'total_pagado' => $trad_total_pagado,
                'recargos' => $trad_recargos,
            ],
            'pagos' => [
                'prestamos' => $pagos_prestamos,
                'importe' => $pagos_importe,
                'terminados' => $pagos_terminados,
                'vigentes' => $pagos_vigentes,
                'total_pagado' => $pagos_total_pagado,
                'recargos' => $pagos_recargos,
            ]
        ], 200);
    }

    /**
     * Genera URL firmada para descargar el reporte PDF del historial
     */
    public function reportePdfUrl(Request $request, $folio)
    {
        $url = URL::temporarySignedRoute(
            'historial.pdf',
            now()->addMinutes(30),
            ['folio' => $folio]
        );

        return response()->json(['url' => $url]);
    }

    /**
     * Descargar o visualizar el reporte PDF
     */
    public function reportePdf(Request $request, $folio)
    {
        $boleta = Boleta::with(['cliente', 'partidas', 'pagos.user', 'user', 'categoria'])->findOrFail($folio);
        
        $sucursal = \App\Models\SucursalConfig::where('id', 1)->first();

        // Obtener la estadística del cliente
        $boletas_cliente = Boleta::with('pagos')->where('cliente_id', $boleta->cliente_id)->get();
        
        $boletasTradicional = $boletas_cliente->where('tipo_prestamo', 'tradicional');
        $boletasPagos = $boletas_cliente->where('tipo_prestamo', 'pagos');

        $trad_total_pagado = 0;
        $trad_recargos = 0;
        foreach($boletasTradicional as $b) {
            $pagosRel = $b->getRelation('pagos');
            $trad_total_pagado += $pagosRel ? $pagosRel->sum('totalPagado') : 0;
            $trad_recargos += $pagosRel ? $pagosRel->sum('recargosNormal') : 0;
        }

        $pagos_total_pagado = 0;
        $pagos_recargos = 0;
        foreach($boletasPagos as $b) {
            $pagosRel = $b->getRelation('pagos');
            $pagos_total_pagado += $pagosRel ? $pagosRel->sum('totalPagado') : 0;
            $pagos_recargos += $pagosRel ? $pagosRel->sum('recargosNormal') : 0;
        }

        $stats = [
            'tradicional' => [
                'prestamos' => $boletasTradicional->count(),
                'importe' => $boletasTradicional->sum('prestamo'),
                'refrendadas' => $boletasTradicional->where('estatus', 'BOLETA REFRENDADA')->count(),
                'desempenadas' => $boletasTradicional->where('estatus', 'BOLETA DESEMPEÑADA')->count(),
                'adjudicaciones' => $boletasTradicional->whereIn('estatus', ['ADJUDICADA', 'ADJUDICACION REAL', 'ADJUDICACION FISICA'])->count(),
                'en_comercializacion' => $boletasTradicional->where('estatus', 'EN COMERCIALIZACION')->count(),
                'vigentes' => $boletasTradicional->where('estatus', 'BOLETA VIGENTE')->count(),
                'total_pagado' => $trad_total_pagado,
                'recargos' => $trad_recargos,
            ],
            'pagos' => [
                'prestamos' => $boletasPagos->count(),
                'importe' => $boletasPagos->sum('prestamo'),
                'terminados' => $boletasPagos->whereIn('estatus', ['BOLETA LIQUIDADA NORMALMENTE', 'BOLETA DESEMPEÑADA'])->count(),
                'vigentes' => $boletasPagos->where('estatus', 'BOLETA VIGENTE')->count(),
                'total_pagado' => $pagos_total_pagado,
                'recargos' => $pagos_recargos,
            ]
        ];

        $pdf = Pdf::loadView('reporteHistorialCliente', compact('boleta', 'sucursal', 'stats'));
        
        // Ajustamos los márgenes si es necesario
        $pdf->setPaper('letter', 'portrait');

        return $pdf->stream("Reporte_Historial_{$boleta->id}.pdf");
    }
}
