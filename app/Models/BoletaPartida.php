<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaPartida extends Model
{
    use LogsActivity;
    protected $fillable = [
        'boleta_id',
        'tipo',
        'subtipo',
        'gramos_cantidad',
        'costo_unitario',
        'valor',
        'descripcion',
    ];

    protected $casts = [
        'gramos_cantidad' => 'decimal:3',
        'costo_unitario'  => 'decimal:2',
        'valor'           => 'decimal:2',
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
            ->useLogName('Boletas_Partida');
    }

}
