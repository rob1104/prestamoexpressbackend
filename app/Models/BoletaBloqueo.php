<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BoletaBloqueo extends Model
{
    use LogsActivity;

    protected $fillable = [
        'boleta_id',
        'motivo',
        'usuario_id',
        'motivo_desbloqueo',
        'usuario_desbloqueo_id',
        'desbloqueado_at',
        'activo',
    ];

    protected $casts = [
        'desbloqueado_at' => 'datetime',
        'activo'          => 'boolean',
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class);
    }

    public function usuarioDesbloqueo()
    {
        return $this->belongsTo(User::class, 'usuario_desbloqueo_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Boletas_Bloqueo');
    }
}
