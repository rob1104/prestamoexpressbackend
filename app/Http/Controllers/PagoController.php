<?php

namespace App\Http\Controllers;

use App\Models\Boleta;
use App\Models\BoletaTradicional;
use App\Models\MovimientosCaja;
use App\Models\NotaCredito;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagoController extends Controller
{
    public function registrarRefrendo(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $boletaId = $request->boleta_id;
            $hoy = now();

            MovimientosCaja::create([
                'caja_id'      => $request->caja_id ?? 1,
                'boleta_id'    => $boletaId,
                'user_id'      => auth()->id(),
                'tipo'         => 'ENTRADA',
                'monto'        => $request->total_pagado,
                'denominacion' => $request->denominaciones,
            ]);

            // 1. Localizar el registro actual pendiente en boletas_tradicionales
            $tradicionalActual = BoletaTradicional::where('boleta_id', $boletaId)
                ->where('estatus', 'PE')
                ->first();

            $bonificacionNC = 0;
            $vencimiento = Carbon::parse($tradicionalActual->fecha_vencimiento);
            $diasRestantes = $hoy->diffInDays($vencimiento, false);
            $diasTranscurridos = $tradicionalActual->dias_reales - $diasRestantes;

            if ($diasTranscurridos >= 0 && $diasTranscurridos <= 15) {
                $bonificacionNC = $tradicionalActual->interes / 2; // 50% de descuento
            } elseif ($diasTranscurridos >= 16 && $diasTranscurridos <= 21) {
                $bonificacionNC = $tradicionalActual->interes / 4; // 25% de descuento
            }

            // 2. Insertar el registro en la tabla 'pagos' (Tu nueva tabla)
            $pagoId = DB::table('pagos')->insertGetId([
                'boleta_id'         => $boletaId,
                'no_pago'           => $tradicionalActual->refrendo,
                'fecha'             => $hoy->format('Y-m-d'),
                'tipo_movimiento'   => 3, // 3 = Refrendo según VB6
                'prestamo'          => $tradicionalActual->capital,
                'interestotal'      => $tradicionalActual->interes,
                'recargosNormal'    => $request->recargos,
                'dias_vencidos'     => $request->dias_vencidos,
                'importe'           => $request->importe_pago,
                'user_id'           => auth()->id(),
                'totalPagado'       => $request->total_pagado,
                'totalRecibido'     => $request->total_recibido,
                'caja_id'           => $request->caja_id,
                'estatus'           => 'A',
                'created_at'        => now(),
            ]);

            NotaCredito::create([
                'boleta_id' => $boletaId,
                'tipo_prestamo' => 'tradicional',
                'cantidad' => $bonificacionNC,
                'cantidad_sugerida' => $bonificacionNC,
                'estatus' => 'aplicado',
                'caja_id' => 1,
                'user_id' => \Auth::id(),
            ]);

            // 3. Actualizar el estatus del periodo actual a REFRENDADO ('RE')
            $tradicionalActual->update(['estatus' => 'RE']);


            // 4. GENERAR EL PRÓXIMO PERIODO (Refrendo + 1)
            // Obtenemos porcentajes de configuración para el nuevo desglose
            $config = DB::table('sucursal_configs')->first();

            // Recalculamos el desglose basado en el capital actual
            $capital = $tradicionalActual->capital;
            $interesNuevo = $tradicionalActual->interes; // En un refrendo simple el interés se mantiene

            $mAlmacenaje = round($capital * (($config->p_almacenaje ?? 0) / 100), 2);
            $mAdmin      = round($capital * (($config->p_administracion ?? 0) / 100), 2);
            $mCustodia   = round($capital * (($config->p_custodia ?? 0) / 100), 2);
            $mIntDiv     = round($capital * (($config->p_interes_dividido ?? 0) / 100), 2);
            $mIva        = round($capital * (($config->p_iva_interes ?? 0) / 100), 2);

            // Ajuste de centavos en Almacenaje
            $diferencia = $interesNuevo - ($mAlmacenaje + $mAdmin + $mCustodia + $mIntDiv + $mIva);
            $mAlmacenaje += $diferencia;

            // Calculamos nueva fecha de vencimiento (Fecha de hoy + periodo de la boleta)
            $nuevaFechaVenc = $hoy->copy()->addDays($tradicionalActual->dias_reales);

            $trad = BoletaTradicional::create([
                'boleta_id'         => $boletaId,
                'refrendo'          => $tradicionalActual->refrendo + 1,
                'fecha_vencimiento' => $nuevaFechaVenc->format('Y-m-d'),
                'dias_reales'       => $tradicionalActual->dias_reales,
                'capital'           => $capital,
                'interes'           => $interesNuevo,
                'almacenaje'        => $mAlmacenaje,
                'administracion'    => $mAdmin,
                'custodia'          => $mCustodia,
                'interesdividido'   => $mIntDiv,
                'iva_interes'       => $mIva,
                'estatus'           => 'PE',
                'user_id'           => auth()->id(),
            ]);

            $bol = Boleta::with('cliente')->where('id', $boletaId)->first();

            $capital = $bol->prestamo;
            $interes = $bol->comision;


            $fecha1 = now()->addDays(15);
            $fecha2 = now()->addDays(21);
            $fecha3 = now()->addDays(30);
            $meses = ['Jan'=>'ene', 'Feb'=>'feb', 'Mar'=>'mar', 'Apr'=>'abr', 'May'=>'may', 'Jun'=>'jun', 'Jul'=>'jul', 'Aug'=>'ago', 'Sep'=>'sep', 'Oct'=>'oct', 'Nov'=>'nov', 'Dec'=>'dic'];

            $liquidacion1 = $capital + ($interes * 0.50);
            $liquidacion2 = $capital + ($interes * 0.75);
            $liquidacion3 = $capital + $interes;

            $pago_facil = [
                [
                    'fecha' => $fecha1->format('d') . '-' . $meses[$fecha1->format('M')] . '-' . $fecha1->format('Y'),
                    'monto' => $liquidacion1
                ],
                [
                    'fecha' => $fecha2->format('d') . '-' . $meses[$fecha2->format('M')] . '-' . $fecha2->format('Y'),
                    'monto' => $liquidacion2
                ],
                [
                    'fecha' => $fecha3->format('d') . '-' . $meses[$fecha3->format('M')] . '-' . $fecha3->format('Y'),
                    'monto' => $liquidacion3
                ]
            ];


            // 5. Retornar datos para el ticket
            return response()->json([
                'status' => 'success',
                'message' => 'Refrendo procesado con éxito',
                'ticket_data' => [
                    'folio' => $boletaId,
                    'no_pago' => $tradicionalActual->refrendo,
                    'fecha_vencimiento' => $nuevaFechaVenc->format('d-M-Y'), // Formato visual SICAE
                    'cliente_id' => $bol->cliente->id,
                    'cliente_nombre' => $bol->cliente->nombre,
                    'total_pagado' => $liquidacion3,
                    'dias' => $trad->dias_reales,
                    'no_bolsa' => $bol->no_bolsa,
                    'pago_facil' => $pago_facil,
                    'bonificacion' => $bonificacionNC
                ]
            ]);
        });
    }
}
