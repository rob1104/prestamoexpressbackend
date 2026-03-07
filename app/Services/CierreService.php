<?php

namespace App\Services;

use App\Models\Boleta;
use App\Models\CierreDiario;
use App\Models\SucursalConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CierreService
{
    public static function checkStatus(): array
    {
        $ahora = Carbon::now();
        $hoy = $ahora->copy()->startOfDay();
        $ayer = $ahora->copy()->subDay()->startOfDay();

        $configDB = SucursalConfig::first();
        if (!$configDB) {
            throw new \Exception("No se encontró la configuración de la sucursal.");
        }

        $horaCierre = $configDB->hora_cierre  ?? '18:00:00';

        $ultimoCierre = DB::table('cierre_diarios')->latest('fecha_cierre')->first();


        $fechaUltimo = $ultimoCierre ? Carbon::parse($ultimoCierre->fecha_cierre)->startOfDay() : null;
        $diasFaltantes = $fechaUltimo->diffInDays($ayer);
        $pendiente = $diasFaltantes > 0;

        $yaEsHora = $ahora->format('H:i:s') >= $horaCierre;
        $sugerirHoy = (!$pendiente && $fechaUltimo->eq($ayer) && $yaEsHora);

        return [
            'pendiente' => $pendiente,
            'dias_faltantes' => $diasFaltantes,
            'ultima_fecha' => $fechaUltimo->format('Y-m-d'),
            'ayer' => $ayer->format('Y-m-d'),
            'sugerir_cierre_hoy' => $sugerirHoy,
            'hora_configurada' => $horaCierre,
            'proxima_fecha_a_cerrar' => $fechaUltimo ? $fechaUltimo->addDay()->format('Y-m-d') : $hoy->format('Y-m-d')
        ];
    }

    public static function ejecutarCierreManualmente()
    {
        return DB::transaction(function () {
            $hoy = now()->startOfDay();
            $diasProcesados = [];

            // 1. Obtener la fecha de inicio corregida
            $ultimoCierre = CierreDiario::latest('fecha_cierre')->first();

            if ($ultimoCierre) {
                $fechaAProcesar = Carbon::parse($ultimoCierre->fecha_cierre)->addDay()->startOfDay();
            } else {
                $minFecha = Boleta::min('created_at');
                // FIX: Parseamos el string a Carbon antes de usar startOfDay()
                $fechaAProcesar = $minFecha ? Carbon::parse($minFecha)->startOfDay() : $hoy;
            }

            // 2. Bucle de "Catch-up": Procesamos hasta el día de ayer
            while ($fechaAProcesar->lt($hoy)) {
                self::procesarCierreDia($fechaAProcesar);
                $diasProcesados[] = $fechaAProcesar->format('Y-m-d');
                $fechaAProcesar->addDay(); // Carbon muta el objeto aquí
            }

            return [
                'status' => 'success',
                'message' => count($diasProcesados) > 0 ? 'Cierres completados' : 'El sistema ya está al día',
                'dias_cerrados' => $diasProcesados
            ];
        });
    }

    private static function procesarCierreDia(Carbon $fecha): void
    {
        // 3. Estadísticas detalladas
        $stats = DB::table('pagos')
            ->whereDate('fecha', $fecha)
            ->where('estatus', 'A')
            ->selectRaw("
                SUM(CASE WHEN tipo_movimiento = 1 THEN importe ELSE 0 END) as prestamos_nuevos,
                SUM(CASE WHEN tipo_movimiento = 4 THEN capital ELSE 0 END) as capital_recuperado,
                SUM(CASE WHEN tipo_movimiento IN (3, 4) THEN interestotal ELSE 0 END) as interes_recuperado,
                SUM(recargosNormal) as recargos_cobrados,
                COUNT(CASE WHEN tipo_movimiento = 1 THEN 1 END) as cant_boletas,
                COUNT(CASE WHEN tipo_movimiento = 3 THEN 1 END) as cant_refrendos,
                COUNT(CASE WHEN tipo_movimiento = 4 THEN 1 END) as cant_liquidaciones")->first();

        // 4. Calcular Bonificaciones NC del día (vienen de la tabla nota_creditos)
        $bonificaciones = DB::table('nota_creditos')
            ->whereDate('created_at', $fecha)
            ->where('estatus', 'aplicado')
            ->sum('cantidad');

        // 5. Guardar el registro maestro del día
        CierreDiario::create([
            'fecha_cierre' => $fecha->format('Y-m-d'),
            'prestamos_nuevos' => $stats->prestamos_nuevos ?? 0,
            'capital_recuperado' => $stats->capital_recuperado ?? 0,
            'interes_recuperado' => $stats->interes_recuperado ?? 0,
            'user_id' => auth()->id(),
        ]);
    }
}
