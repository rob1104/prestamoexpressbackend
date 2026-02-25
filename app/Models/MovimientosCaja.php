<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MovimientosCaja extends Model
{
    use LogsActivity;

    protected $fillable = [
        "caja_id",
        "boleta_id",
        "user_id",
        "tipo",
        "monto",
        "denominacion"
    ];

    protected $casts = [
        'denominacion' => 'array',
        'monto'        => 'decimal:2',
    ];

    public function boleta() {
        return $this->belongsTo(Boleta::class);
    }

    public function caja() {
        return $this->belongsTo(Caja::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Caja Movimientos');
    }
}
