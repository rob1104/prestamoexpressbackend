<?php

namespace App\Http\Controllers;

use App\Models\FlujoConcepto;
use App\Models\MovimientosCaja;
use Illuminate\Http\Request;

class FlujoConceptoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(FlujoConcepto::all());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|in:ENTRADA,SALIDA',
            'activo' => 'boolean'
        ]);

        $concepto = FlujoConcepto::create($validated);
        return response()->json($concepto, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(FlujoConcepto::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'tipo' => 'sometimes|in:ENTRADA,SALIDA',
            'activo' => 'sometimes|boolean'
        ]);

        $concepto = FlujoConcepto::findOrFail($id);
        $concepto->update($validated);
        return response()->json($concepto);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $concepto = FlujoConcepto::findOrFail($id);
        
        $enUso = MovimientosCaja::where('flujo_concepto_id', $id)->exists();
        if ($enUso) {
            return response()->json(['message' => 'No se puede eliminar el concepto porque ya está en uso. Puede desactivarlo en su lugar.'], 409);
        }

        $concepto->delete();
        return response()->json(['message' => 'Concepto eliminado']);
    }
}
