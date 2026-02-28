<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaPago extends Model
{
    use LogsActivity;
    // Campos permitidos para asignación masiva
    protected $fillable = [
        'boleta_id', 'user_id', 'caja_id',
        'referencia', 'autorizacion',
        'importe', 'comision', 'total', 'importe_recibido', 'cambio',
        'estatus',
        'usuario_cancelacion_id', 'motivo_cancelacion', 'fecha_cancelacion'
    ];

    // Relación con la Boleta
    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    // Relación con el Cajero que cobró
    public function cajero()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con el Supervisor/Gerente que canceló (si aplica)
    public function canceladoPor()
    {
        return $this->belongsTo(User::class, 'usuario_cancelacion_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boleta Pago');
    }
}
