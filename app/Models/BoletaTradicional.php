<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaTradicional extends Model
{
    use LogsActivity;

    protected $fillable = [
        'boleta_id', 'refrendo', 'fecha_vencimiento', 'dias_reales', 'capital',
        'interes', 'almacenaje', 'administracion', 'custodia', 'interesdividido',
        'iva_interes', 'estatus', 'user_id',
        'updated_at'
    ];

    public function boleta() {
        return $this->belongsTo(Boleta::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boleta Tradicional');
    }
}
