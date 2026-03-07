<?php

namespace App\Http\Middleware;

use App\Models\CierreDiario;
use App\Services\CierreService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCierreDiario
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $status = CierreService::checkStatus();
        if ($status['pendiente']) {
            return response()->json([
                'status' => 'error',
                'code' => 'CIERRE_PENDIENTE',
                'message' => "Debe cerrar {$status['dias_faltantes']} día(s) pendiente(s)."
            ], 403);
        }
        return $next($request);
    }
}
