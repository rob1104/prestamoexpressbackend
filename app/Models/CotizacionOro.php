<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CotizacionOro extends Model
{
    use LogsActivity;

    protected $fillable = [
        'categoria', 'descripcion', 'precio_nuevo',
        'precio_bueno', 'precio_excelente', 'precio_compra'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('COTIZACIONES_ORO');
    }
}
