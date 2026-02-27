<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BoletaController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionOroController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    return [
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
        'active' => $user->active
    ];
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/roles/names', [RoleController::class, 'getRolesName'])->name('roles.names');
    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-log');
    Route::apiResource('roles', RoleController::class);
    Route::get('/permissions', [RoleController::class, 'getAllPermissions'])->name('permissions.all');
    Route::prefix('/users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users.index');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::patch('/{user}/toggle', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
    });
    Route::get('/clientes/search', [ClienteController::class, 'search'])->name('clientes.search');
    Route::apiResource('clientes', ClienteController::class);
    Route::get('/clientes/ines/{path}', [ClienteController::class, 'verIne'])->where('path', '.*');
    Route::get('/clientes/{cliente}/stats', [ClienteController::class, 'resumenOperaciones']);

    Route::prefix('/config')->group(function () {
        Route::get('/cotizacionoro', [CotizacionOroController::class, 'index'])->name('config.cotizacionoro.index');
        Route::post('/cotizacionoro/bulk-update', [CotizacionOroController::class, 'bulkUpdate'])->name('config.cotizacionoro.update');
        Route::get('/parametros', [ParametrosController::class, 'index'])->name('config.parametros.index');
        Route::post('/parametros', [ParametrosController::class, 'store'])->name('config.parametros.store');
    });

    Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');

    Route::post('/boletas', [BoletaController::class, 'store'])->name('boletas.store');
    Route::get('/boletas/{id}', [BoletaController::class, 'show'])->name('boletas.show');

    Route::post('/boletas/pagos/refrendo', [PagoController::class, 'registrarRefrendo'])->name('boletas.refrendo');
    Route::post('/boletas/pagos/liquidacion', [BoletaController::class, 'procesarLiquidacion']);

    Route::post('/movimientoscaja/{id}/registrar-efectivo', [MovimientoCajaController::class, 'registrarEfectivo'])->name('movimientoscaja.registrar-efectivo');
});

