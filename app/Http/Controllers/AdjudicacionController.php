<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Adjudicacion;
use App\Models\Boleta;
use App\Models\BoletaBloqueo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdjudicacionController extends Controller
{
    /**
     * Lista todas las adjudicaciones con sus relaciones.
     */
    public function index()
    {
        $adjudicaciones = Adjudicacion::with(['boleta', 'boleta.cliente', 'boleta.partidas', 'user'])
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($adj) {
                return [
                    'id' => $adj->id,
                    'boleta_id' => $adj->boleta_id,
                    'folio_boleta' => $adj->boleta->id ?? null,
                    'cliente_nombre' => $adj->boleta->cliente->nombre ?? 'N/A',
                    'monto_adjudicado' => $adj->monto_adjudicado,
                    'fecha_adjudicacion' => $adj->fecha_adjudicacion,
                    'observaciones' => $adj->observaciones,
                    'usuario' => $adj->user->name ?? 'Sistema'
                ];
            });

        return response()->json($adjudicaciones);
    }

    /**
     * Busca una boleta por folio para el modal de adjudicación y valida reglas.
     */
    public function buscarBoleta(Request $request, $folio)
    {
        $boleta = Boleta::with([
            'cliente', 
            'partidas', 
            'categoria', 
            'calendarioPagos', 
            'tradicional', 
            'reporteActivo', 
            'bloqueoActivo',
            'pagos.user'
        ])->find($folio);

        if (!$boleta) {
            return response()->json(['message' => 'Folio no encontrado en el sistema.'], 404);
        }

        $modoDetalle = filter_var($request->query('modo_detalle', false), FILTER_VALIDATE_BOOLEAN);

        if (!$modoDetalle && $boleta->estatus === 'EN') {
            return response()->json(['message' => 'La boleta ya se encuentra adjudicada en el sistema. No se puede volver a adjudicar.'], 400);
        }

        if (!$modoDetalle && in_array($boleta->estatus, ['LI', 'CA'])) {
            return response()->json(['message' => "La boleta tiene estatus {$boleta->estatus} (Liquidada/Cancelada). No puede ser adjudicada."], 400);
        }

        $advertencias = [];
        $valida = true;

        // 1. Debe estar Activa/Vigente (Pendiente)
        if ($boleta->estatus !== 'PE') {
            $advertencias[] = "La boleta no está en estatus Pendiente (Estatus actual: {$boleta->estatus}).";
            $valida = false;
        }

        // 2. Haber superado fecha de vencimiento/remate
        $hoy = now()->startOfDay();
        $fechaVencimiento = Carbon::parse($boleta->fecha_vencimiento)->startOfDay();
        
        // Normalmente el remate se evalúa con la 'fecha_remate' o 'fecha_vencimiento' + días de gracia.
        // Asumiendo que 'fecha_remate' es la fecha final si existe, o usamos fecha de vencimiento.
        $fechaLimite = $boleta->fecha_remate ? Carbon::parse($boleta->fecha_remate)->startOfDay() : $fechaVencimiento;

        if ($hoy->lte($fechaLimite)) {
            $advertencias[] = "Aún no concluye el plazo de vencimiento o periodo de gracia (Fecha límite: {$fechaLimite->format('d-M-Y')}).";
            $valida = false;
        }

        // 3. No debe tener bloqueo administrativo, jurídico o auditoría
        $bloqueo = BoletaBloqueo::where('boleta_id', $boleta->id)->where('activo', true)->first();
        if ($bloqueo) {
            $advertencias[] = "La boleta tiene un bloqueo activo: {$bloqueo->motivo}.";
            $valida = false;
        }

        // Calcular días vencidos generales
        $diasVencidos = 0;
        if ($hoy->gt($fechaVencimiento)) {
            $diasVencidos = $fechaVencimiento->diffInDays($hoy);
        }

        return response()->json([
            'boleta' => $boleta,
            'dias_vencidos' => $diasVencidos,
            'valida' => $valida,
            'advertencias' => $advertencias,
            'monto_adjudicacion_sugerido' => $boleta->prestamo // Usamos préstamo como monto de absorción
        ]);
    }

    /**
     * Procesa la adjudicación manual de la boleta.
     */
    public function adjudicarManual(Request $request)
    {
        $request->validate([
            'boleta_id' => 'required|exists:boletas,id',
            'monto_adjudicado' => 'required|numeric',
            'observaciones' => 'nullable|string'
        ]);

        return DB::transaction(function () use ($request) {
            try {
                $boleta = Boleta::findOrFail($request->boleta_id);

                if ($boleta->estatus === 'EN') {
                    return response()->json(['message' => 'La boleta ya fue adjudicada previamente.'], 400);
                }

                // 1. Crear el registro en `adjudicaciones`
                Adjudicacion::create([
                    'boleta_id' => $boleta->id,
                    'user_id' => auth()->id() ?? 1,
                    'observaciones' => $request->observaciones,
                    'monto_adjudicado' => $request->monto_adjudicado,
                    'fecha_adjudicacion' => now()
                ]);

                // 2. Cambiar estatus de la boleta a "EN"
                $boleta->update(['estatus' => 'EN', 'cancelada_at' => now()]);

                return response()->json(['message' => 'Adjudicación completada exitosamente.']);
            } catch (\Exception $e) {
                Log::error("Error adjudicando boleta: " . $e->getMessage());
                return response()->json(['message' => 'Error interno al adjudicar: ' . $e->getMessage()], 500);
            }
        });
    }

    /**
     * Revierte la adjudicación, regresando la boleta a estatus PE.
     */
    public function revertirAdjudicacion($id)
    {
        return DB::transaction(function () use ($id) {
            try {
                $adjudicacion = Adjudicacion::findOrFail($id);
                $boleta = $adjudicacion->boleta;

                if ($boleta->estatus !== 'EN') {
                    return response()->json(['message' => 'La boleta asociada no está en estatus adjudicado.'], 400);
                }

                // 1. Regresar la boleta a PE y limpiar fecha
                $boleta->update(['estatus' => 'PE', 'cancelada_at' => null]);

                // 2. Eliminar el registro de adjudicación (o podría marcarse como cancelado)
                $adjudicacion->delete();

                return response()->json(['message' => 'Adjudicación revertida exitosamente.']);
            } catch (\Exception $e) {
                Log::error("Error revirtiendo adjudicacion: " . $e->getMessage());
                return response()->json(['message' => 'Error interno al revertir: ' . $e->getMessage()], 500);
            }
        });
    }
}
