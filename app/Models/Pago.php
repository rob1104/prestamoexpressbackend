<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
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
}
