<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ComisionConfig extends Model
{
    use LogsActivity;
    protected $fillable = [
        'categorias', 'limite_inferior', 'limite_superior',
        'mes_inferior', 'mes_superior', 'porcentaje_comision'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('COTIZACIONES_ORO');
    }
}
