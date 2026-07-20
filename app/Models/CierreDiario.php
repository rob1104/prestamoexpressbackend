<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CierreDiario extends Model
{
    use LogsActivity;

    protected $fillable = [
        'fecha_cierre',
        'prestamos_nuevos',
        'capital_recuperado',
        'interes_cobrado',
        'interes_recuperado',
        'recargos_cobrados',
        'entradas_otros',
        'salidas_otros',
        'ventas_joyeria',
        'ventas_electronicos',
        'boletas_nuevas',
        'boletas_liquidadas',
        'user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('CIERRE DIARIO');
    }
}
