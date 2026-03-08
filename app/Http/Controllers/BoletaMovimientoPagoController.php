<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BoletaMovimientoPagoController extends Controller
{

    public function registrarPago(Request $request)
    {
        // 1. Validación de los datos recibidos del frontend
        $request->validate([
            'boleta_id'      => 'required|exists:boletas,id',
            'pagos_ids'      => 'required|array',
            'total_pagado'   => 'required|numeric',
            'total_recibido' => 'required|numeric',
            'interes'        => 'required|numeric',
            'recargos'       => 'required|numeric',
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $boleta = Boleta::findOrFail($request->boleta_id);

                // 2. Actualizar estatus en la tabla calendario_pagos
                DB::table('calendario_pagos')
                    ->whereIn('id', $request->pagos_ids)
                    ->update([
                        'estatus' => 'PA', // PA = Pagado
                        'fecha_pago' => now(),
                        'updated_at' => now()
                    ]);

                // 3. Crear el registro contable en la tabla 'pagos'
                $pagoId = DB::table('pagos')->insertGetId([
                    'boleta_id'       => $boleta->id,
                    'no_pago'         => DB::table('pagos')->where('boleta_id', $boleta->id)->count() + 1, //
                    'fecha'           => now()->format('Y-m-d'), //
                    'tipo_movimiento' => 4, // 4 = Liquidación/Abono en pagos fijos
                    'prestamo'        => $boleta->prestamo,
                    'interestotal'    => $request->interes,
                    'recargosNormal'  => $request->recargos,
                    'importe'         => $request->total_pagado,
                    'totalPagado'     => $request->total_pagado,
                    'totalRecibido'   => $request->total_recibido,
                    'tipoPrestamo'    => 'PG', // 'PG' para diferenciar de Tradicional 'TR'
                    'user_id'         => auth()->id() ?? 1,
                    'caja_id'         => 1,
                    'estatus'         => 'A', //
                    'created_at'      => now(),
                    'updated_at'      => now()
                ]);

                // 4. Verificar si la boleta debe liquidarse totalmente
                // Si ya no quedan pagos con estatus 'PE' (Pendiente)
                $pendientes = DB::table('calendario_pagos')
                    ->where('boleta_id', $boleta->id)
                    ->where('estatus', 'PE')
                    ->count();

                if ($pendientes === 0) {
                    $boleta->update(['estatus' => 'LI']);
                }

                // 5. Preparar datos para el ticket
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pago registrado correctamente',
                    'pago_id' => $pagoId,
                    'es_liquidacion' => ($pendientes === 0)
                ]);

            } catch (\Exception $e) {
                Log::error("Error registrando pago: " . $e->getMessage());
                return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
            }
        });
    }

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
