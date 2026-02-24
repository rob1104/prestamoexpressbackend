<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaReporte extends Model
{
    use LogsActivity;

    protected $fillable = [
        'boleta_id',
        'usuario_id',
        'fecha_reporte',
        'fecha_termino',
        'motivo_termino',
        'fecha_quito_reporte',
        'motivo_quito_reporte',
        'usuario_quito_reporte_id',
        'activo',
        'pagado',
    ];

    protected $casts = [
        'fecha_reporte'       => 'date',
        'fecha_termino'       => 'date',
        'fecha_quito_reporte' => 'date',
        'activo'              => 'boolean',
        'pagado'              => 'boolean',
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function usuarioQuitoReporte()
    {
        return $this->belongsTo(User::class, 'usuario_quito_reporte_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boletas_Reporte');
    }
}
