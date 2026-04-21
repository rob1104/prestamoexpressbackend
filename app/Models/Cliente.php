<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Cliente extends Model
{
    use LogsActivity;

    protected $fillable = [
        'nombre', 'identificacion', 'clasificacion', 'telefono1', 'telefono2',
        'apellido_paterno','apellido_materno','rfc','estado_origen','fecha_nacimiento',
        'ineFrente', 'ineReverso', 'callenum', 'colonia', 'municipio',
        'estado', 'codPostal', 'ocupacion', 'observacion','email'
    ];

    public function boletas()
    {
        return $this->hasMany(Boleta::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Clientes');
    }
}
