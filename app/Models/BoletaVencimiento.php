<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class BoletaVencimiento extends Model
{
    protected $fillable = [
        'boleta_id',
        'no_pago',
        'fecha_vencimiento',
        'dias_reales',
        'capital',
        'comision',
        'iva_comision',
        'total',
        'estatus',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'capital'           => 'decimal:2',
        'comision'          => 'decimal:2',
        'iva_comision'      => 'decimal:2',
        'total'             => 'decimal:2',
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function movimientos()
    {
        return $this->hasMany(BoletaMovimiento::class);
    }

    public function esPendiente(): bool
    {
        return $this->estatus === 'pendiente';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boletas_vencimiento');
    }
}
