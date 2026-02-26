<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotaCredito extends Model
{
    use LogsActivity;

    protected $fillable = [
        'boleta_id',
        'tipo_prestamo',
        'cantidad',
        'cantidad_sugerida',
        'estatus',
        'motivo_cancelacion',
        'fecha_cancelacion',
        'caja_id',
        'user_id',
        'user_autorizo_id',
        'user_cancelo_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Nota Credito');
    }
}
