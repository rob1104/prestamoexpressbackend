<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Pago extends Model
{
    use HasFactory;
    //
    protected $fillable = [
        'boleta_id',
        'no_pago',
        'fecha',
        'tipo_movimiento',
        'interestotal',
        'prestamo',
        'recargosNormal',
        'dias_vencidos',
        'importe',
        'user_id',
        'totalPagado',
        'totalRecibido',
        'caja_id',
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class, 'boleta_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
