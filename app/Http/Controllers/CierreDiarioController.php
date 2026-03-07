<?php

namespace App\Http\Controllers;

use App\Services\CierreService;
use Exception;

class CierreDiarioController extends Controller
{
    public function status()
    {
        $status = CierreService::checkStatus();
        return response()->json($status);
    }

    public function ejecutarCierreManualmente()
    {
        try {
            $resultado = CierreService::ejecutarCierreManualmente();
            return response()->json($resultado);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error en el proceso de cierre: ' . $e->getMessage()
            ], 500);
        }
    }
}
