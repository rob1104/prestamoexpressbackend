<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArqueoCaja extends Model
{
    use HasFactory;

    protected $fillable = [
        'caja_id',
        'user_id',
        'importe_sistema',
        'importe_arqueo',
        'diferencia',
        'desglose'
    ];

    protected $casts = [
        'desglose' => 'array',
        'importe_sistema' => 'decimal:2',
        'importe_arqueo' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
