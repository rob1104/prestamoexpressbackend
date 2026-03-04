<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Boleta extends Model
{
    use LogsActivity;
    protected $fillable = [
        'cliente_id',
        'categoria_id',
        'cotizacion_oro_id',
        'promocion_id',
        'user_id',
        'no_bolsa',
        'tipo_prestamo',
        'meses',
        'periodo',
        'pagos',
        'prestamo',
        'valor_comercial',
        'p_interes',
        'comision',
        'iva_comision',
        'pago_fijo',
        'ultimo_pago',
        'total_pagar',
        'fecha_boleta',
        'fecha_vencimiento',
        'estatus',
        'cotizacion_valor',
        'numero_pagos',
    ];

    protected $casts = [
        'fecha_boleta'      => 'date',
        'fecha_vencimiento' => 'date',
        'prestamo'          => 'decimal:2',
        'valor_comercial'   => 'decimal:2',
        'comision'          => 'decimal:2',
        'iva_comision'      => 'decimal:2',
        'pago_fijo'         => 'decimal:2',
        'ultimo_pago'       => 'decimal:2',
        'total_pagar'       => 'decimal:2',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function cotizacionOro()
    {
        return $this->belongsTo(CotizacionOro::class);
    }

    public function promocion()
    {
        return $this->belongsTo(Promocion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function partidas()
    {
        return $this->hasMany(BoletaPartida::class);
    }

    public function vencimientos()
    {
        return $this->hasMany(BoletaVencimiento::class)->orderBy('no_pago');
    }

    /** Primer vencimiento (o el único en boleta tradicional) */
    public function vencimiento()
    {
        return $this->hasOne(BoletaVencimiento::class)->where('no_pago', 1);
    }

    public function movimientos()
    {
        return $this->hasMany(BoletaMovimiento::class)->latest('fecha_movimiento');
    }

    public function bloqueos()
    {
        return $this->hasMany(BoletaBloqueo::class);
    }

    public function bloqueoActivo()
    {
        return $this->hasOne(BoletaBloqueo::class)->where('activo', true);
    }

    public function reportes()
    {
        return $this->hasMany(BoletaReporte::class);
    }

    public function reporteActivo()
    {
        return $this->hasOne(BoletaReporte::class)->where('activo', true);
    }

    // ── Helpers ─────────────────────────────────────────────────

    public function esBloqueada(): bool
    {
        return $this->bloqueoActivo()->exists();
    }

    public function esTradicional(): bool
    {
        return $this->tipo_prestamo === 'tradicional';
    }

    public function calendarioPagos()
    {
        return $this->hasMany(CalendarioPago::class, 'boleta_id')->orderBy('num_pago', 'asc');
    }

    public function tradicional()
    {
        return $this->hasOne(BoletaTradicional::class);
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boletas');
    }
}
