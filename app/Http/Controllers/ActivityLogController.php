<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    public function index()
    {
        return Activity::with('causer')
            ->orderBy('created_at', 'desc')
            ->limit(500)
            ->get()
            ->map(function($activity) {
                return [
                    'id' => $activity->id,
                    'fecha' => $activity->created_at->format('d/m/Y H:i:s'),
                    'modulo' => strtoupper($activity->log_name),
                    'descripcion' => $activity->description,
                    'usuario' => $activity->causer ? $activity->causer->name : 'Sistema/Automático',
                    'propiedades' => $activity->properties,
                    'ip' => $activity->getExtraProperty('ip') ?? 'N/A'
                ];
            });
    }
}
