<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RecargoConfig extends Model
{
    use LogsActivity;
    protected $fillable = ['dia_inicio', 'dia_fin', 'valor', 'tipo'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('COTIZACIONES_ORO');
    }
}
