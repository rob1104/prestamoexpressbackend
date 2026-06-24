<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CategoriaJoyeria extends Model
{
    use LogsActivity;

    protected $table = 'categorias_joyeria';
    protected $fillable = ['nombre', 'activo'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('Categoria Joyeria');
    }
}
