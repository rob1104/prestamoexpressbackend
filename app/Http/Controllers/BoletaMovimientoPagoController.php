<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletaMovimientoPagoController extends Controller
{
    /**
     * Consulta la boleta y su calendario de pagos pendientes.
     */
    public function consultaBoleta($id)
    {
        // 1. Buscamos la boleta filtrando por tipo 'pagos'
        $boleta = Boleta::with(['cliente', 'partidas', 'categoria'])
            ->where('id', $id)
            ->where('tipo_prestamo', 'pagos')
            ->first();

        if (!$boleta) {
            return response()->json(['message' => 'Folio no encontrado en el sistema de pagos'], 404);
        }

        if ($boleta->estatus !== 'PE') {
            return response()->json(['message' => 'Folio no encontrado o ya liquidado'], 404);
        }

        // 2. Traemos el Calendario de Pagos Pendientes
        // Filtramos por boleta_id y estatus 'PE' (Pendiente)
        $hoy = now()->startOfDay();
        $calendario = DB::table('calendario_pagos')
            ->where('boleta_id', $id)
            ->where('estatus', 'PE')
            ->orderBy('num_pago', 'asc')
            ->get()
            ->map(function($pago) use ($hoy) {
                $vencimiento = Carbon::parse($pago->fecha_vencimiento)->startOfDay();
                // Si hoy es mayor al vencimiento, calculamos la diferencia, si no, es 0
                $pago->dias_vencidos = $hoy->gt($vencimiento)
                    ? $hoy->diffInDays($vencimiento)
                    : 0;
                return $pago;
            });

        // 3. Lógica Financiera para Recargos y Bonificaciones
        $hoy = now();
        $vencimiento = Carbon::parse($boleta->fecha_vencimiento);
        $interesAcumulado = (float) $boleta->comision;

        $recargos = 0;
        if ($hoy->gt($vencimiento)) {
            $diasAtraso = $hoy->diffInDays($vencimiento);
            $recargos = $this->calcularRecargosMora($diasAtraso, $boleta->prestamo);
        }

        $bonificacion = $this->calcularBonificacion($boleta, $hoy);

        // 4. Retornamos la respuesta incluyendo el 'calendario'
        return response()->json([
            'boleta' => $boleta,
            'calendario' => $calendario, // <--- Este es el dato que usa el componente de Quasar
            'calculos' => [
                'interes' => round($interesAcumulado, 2),
                'recargos' => round($recargos, 2),
                'bonificacion' => round($bonificacion, 2),
                // El total inicial se calcula sobre la boleta,
                // pero el componente de Quasar lo actualizará al seleccionar pagos
                'total' => round(($boleta->prestamo + $interesAcumulado + $recargos) - $bonificacion, 2)
            ]
        ]);
    }

    /**
     * Calcula recargos moratorios según la configuración de la base de datos.
     */
    private function calcularRecargosMora($dias, $prestamo)
    {
        if ($dias <= 0) return 0;

        $configRecargo = DB::table('recargo_configs')
            ->where('dias_min', '<=', $dias)
            ->where('dias_max', '>=', $dias)
            ->first();

        $porcentajeRecargo = $configRecargo ? $configRecargo->porcentaje : 1.0;
        $montoRecargo = $prestamo * ($porcentajeRecargo / 100);

        return round($montoRecargo, 2);
    }

    /**
     * Calcula bonificación por pronto pago.
     */
    private function calcularBonificacion($boleta, $hoy)
    {
        $vencimiento = Carbon::parse($boleta->fecha_vencimiento);
        if ($hoy->gt($vencimiento)) return 0;

        $fechaCreacion = Carbon::parse($boleta->created_at);
        $diasTranscurridos = $fechaCreacion->diffInDays($hoy);

        $interesBase = (float) $boleta->comision;
        $bonificacion = 0;

        if ($diasTranscurridos >= 0 && $diasTranscurridos <= 15) {
            $bonificacion = $interesBase * 0.50;
        } elseif ($diasTranscurridos >= 16 && $diasTranscurridos <= 21) {
            $bonificacion = $interesBase * 0.25;
        }

        return round($bonificacion, 2);
    }
}
