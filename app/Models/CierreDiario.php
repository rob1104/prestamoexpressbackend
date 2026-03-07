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
        'interes_recuperado',
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
