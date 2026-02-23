<?php

namespace App\Http\Controllers;

use App\Models\CotizacionOro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CotizacionOroController extends Controller
{
    public function index()
    {
        return response()->json([
            'gramos' => CotizacionOro::where('categoria', 'GRAMOS')->get(),
            'monedas' => CotizacionOro::where('categoria', 'MONEDAS')->get()
        ]);
    }

    public function bulkUpdate(Request $request)
    {
        try {
            DB::beginTransaction();

            // Combinamos gramos y monedas en una sola lista para procesar
            $items = array_merge($request->gramos, $request->monedas);

            foreach ($items as $item) {
                CotizacionOro::updateOrCreate(
                    ['descripcion' => $item['descripcion'], 'categoria' => $item['categoria']],
                    [
                        'precio_nuevo'     => $item['precio_nuevo'],
                        'precio_bueno'     => $item['precio_bueno'],
                        'precio_excelente' => $item['precio_excelente'],
                        'precio_compra'    => $item['precio_compra'],
                    ]
                );
            }

            DB::commit();
            return response()->json(['message' => 'Cotizaciones actualizadas globalmente.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al sincronizar precios: ' . $e->getMessage()], 500);
        }
    }
}
