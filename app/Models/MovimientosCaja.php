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
        "denominacion",
        "referencia_id",
        "observaciones",
        "concepto_id",
        "recibido_por",
        "entregado_por",
        "autorizado_por",
        "adicional_1",
        "adicional_2",
        "es_pago_relacionado"
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

    public function conceptoFlujo() {
        return $this->belongsTo(FlujoConcepto::class, 'concepto_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Caja Movimientos');
    }
}
