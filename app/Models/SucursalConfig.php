<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SucursalConfig extends Model
{
    use LogsActivity;

    protected $guarded = [];

    // Auditoría para cambios en parámetros críticos
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->useLogName('configuracion_sucursal');
    }

    // Mutator para guardar todo en mayúsculas
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($model) {
            foreach ($model->getAttributes() as $key => $value) {
                if (is_string($value) && !in_array($key, ['created_at', 'updated_at'])) {
                    $model->{$key} = mb_strtoupper($value, 'UTF-8');
                }
            }
        });
    }
}
