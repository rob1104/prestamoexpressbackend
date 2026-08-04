<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adjudicacion extends Model
{
    use HasFactory;

    protected $table = 'adjudicaciones';

    protected $fillable = [
        'boleta_id',
        'user_id',
        'observaciones',
        'monto_adjudicado',
        'fecha_adjudicacion'
    ];

    public function boleta()
    {
        return $this->belongsTo(Boleta::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
