<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaMovimiento extends Model
{
    use LogsActivity;

    protected $fillable = [
        'boleta_id',
        'boleta_vencimiento_id',
        'tipo',
        'capital_original',
        'comision_original',
        'capital_aplicado',
        'comision_proporcional',
        'recargos',
        'penalizacion',
        'importe_pagado',
        'importe_recibido',
        'abono_capital',
        'boleta_nueva_id',
        'estatus',
        'motivo_cancelacion',
        'usuario_id',
        'usuario_cancelacion_id',
        'fecha_movimiento',
        'fecha_cancelacion',
    ];

    protected $casts = [
        'fecha_movimiento'  => 'datetime',
        'fecha_cancelacion' => 'datetime',
        'capital_original'  => 'decimal:2',
        'comision_original' => 'decimal:2',
        'capital_aplicado'  => 'decimal:2',
        'importe_pagado'    => 'decimal:2',
        'importe_recibido'  => 'decimal:2',
        'abono_capital'     => 'decimal:2',
        'recargos'          => 'decimal:2',
        'penalizacion'      => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function vencimiento()
    {
        return $this->belongsTo(BoletaVencimiento::class, 'boleta_vencimiento_id');
    }

    public function boletaNueva()
    {
        return $this->belongsTo(Boleta::class, 'boleta_nueva_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usuarioCancelacion()
    {
        return $this->belongsTo(User::class, 'usuario_cancelacion_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boletas_Movimiento');
    }
}
