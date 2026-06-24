<?php

namespace App\Http\Controllers;

use App\Models\CategoriaJoyeria;
use App\Models\ClasificacionJoyeria;
use Illuminate\Http\Request;

class CatalogoJoyeriaController extends Controller
{
    // --- CATEGORÍAS ---
    public function getCategorias() {
        return response()->json(CategoriaJoyeria::orderBy('nombre')->get());
    }

    public function storeCategoria(Request $request) {
        $request->validate(['nombre' => 'required|string|unique:categorias_joyeria,nombre']);
        $cat = CategoriaJoyeria::create(['nombre' => strtoupper($request->nombre)]);
        return response()->json($cat);
    }

    public function updateCategoria(Request $request, $id) {
        $request->validate(['nombre' => 'required|string|unique:categorias_joyeria,nombre,'.$id]);
        $cat = CategoriaJoyeria::findOrFail($id);
        $cat->update(['nombre' => strtoupper($request->nombre)]);
        return response()->json($cat);
    }

    public function destroyCategoria($id) {
        CategoriaJoyeria::findOrFail($id)->delete();
        return response()->json(['message' => 'Eliminada']);
    }

    // --- CLASIFICACIONES ---
    public function getClasificaciones() {
        return response()->json(ClasificacionJoyeria::orderBy('nombre')->get());
    }

    public function storeClasificacion(Request $request) {
        $request->validate(['nombre' => 'required|string|unique:clasificaciones_joyeria,nombre']);
        $clasif = ClasificacionJoyeria::create(['nombre' => strtoupper($request->nombre)]);
        return response()->json($clasif);
    }

    public function updateClasificacion(Request $request, $id) {
        $request->validate(['nombre' => 'required|string|unique:clasificaciones_joyeria,nombre,'.$id]);
        $clasif = ClasificacionJoyeria::findOrFail($id);
        $clasif->update(['nombre' => strtoupper($request->nombre)]);
        return response()->json($clasif);
    }

    public function destroyClasificacion($id) {
        ClasificacionJoyeria::findOrFail($id)->delete();
        return response()->json(['message' => 'Eliminada']);
    }
}
