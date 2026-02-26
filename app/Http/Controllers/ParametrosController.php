<?php

namespace App\Http\Controllers;

use App\Models\ComisionConfig;
use App\Models\RecargoConfig;
use App\Models\SucursalConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ParametrosController extends Controller
{
    /**
     * Carga toda la configuración inicial para el frontend
     */
    public function index()
    {
        return response()->json([
            'generales'  => SucursalConfig::first() ?? new SucursalConfig(),
            'comisiones' => ComisionConfig::all(),
            'recargos'   => RecargoConfig::orderBy('dia_inicio', 'asc')->get()
        ]);
    }

    /**
     * Guardado Masivo (Lógica F6 - Confirmar)
     */
    public function store(Request $request)
    {
        try {
            // Iniciamos la transacción para asegurar la integridad de los datos
            DB::beginTransaction();

            // 1. Actualizar Datos Generales (Fila única ID 1)
            // Se usa updateOrCreate para garantizar que el registro exista siempre
            SucursalConfig::updateOrCreate(
                ['id' => 1],
                $request->generales
            );

            // 2. Sincronizar Tabla de Comisiones
            // IMPORTANTE: Se usa delete() en lugar de truncate() para no romper la transacción
            ComisionConfig::query()->delete();

            if (isset($request->comisiones) && is_array($request->comisiones)) {
                foreach ($request->comisiones as $com) {
                    ComisionConfig::create([
                        'categorias'          => $com['categorias'],
                        'limite_inferior'     => $com['limite_inferior'],
                        'limite_superior'     => $com['limite_superior'],
                        'mes_inferior'        => $com['mes_inferior'],
                        'mes_superior'        => $com['mes_superior'],
                        'porcentaje_comision' => $com['porcentaje_comision'],
                    ]);
                }
            }

            // 3. Sincronizar Tabla de Recargos
            RecargoConfig::query()->delete();

            if (isset($request->recargos) && is_array($request->recargos)) {
                foreach ($request->recargos as $rec) {
                    RecargoConfig::create([
                        'dia_inicio' => $rec['dia_inicio'],
                        'dia_fin'    => $rec['dia_fin'],
                        'valor'      => $rec['valor'],
                        'tipo'       => $rec['tipo'], // 'D' o 'T'
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'CONFIGURACIÓN GLOBAL ACTUALIZADA EXITOSAMENTE',
                'status'  => 'success'
            ]);

        } catch (\Exception $e) {
            // En caso de error, verificamos si hay una transacción activa para revertir
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'message' => 'Error al guardar parámetros',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
