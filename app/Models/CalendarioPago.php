<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CalendarioPago extends Model
{
    use LogsActivity;

    protected $table = 'calendario_pagos';

    protected $fillable = [
        'boleta_id', 'num_pago', 'fecha_vencimiento',
        'monto', 'estatus', 'fecha_pago'
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Calendario Pago');
    }
}
