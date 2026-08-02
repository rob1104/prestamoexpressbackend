<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\BoletaController;
use App\Http\Controllers\BoletaMovimientoPagoController;
use App\Http\Controllers\CatalogoJoyeriaController;
use App\Http\Controllers\CierreDiarioController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\CotizacionOroController;
use App\Http\Controllers\MovimientoCajaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ParametrosController;
use App\Http\Controllers\PromocionController;
use App\Http\Controllers\ReporteCarteraController;
use App\Http\Controllers\ReporteFlujoCajaController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlujoCajaController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VentaElectronicosController;
use App\Http\Controllers\VentaJoyeriaController;
use App\Http\Controllers\DatabaseAdminController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\FlujoConceptoController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'active' => $user->active
        ]);
    });

    Route::put('/profile', [ProfileController::class, 'update']);
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
    Route::patch('/clientes/{id}/clasificacion', [ClienteController::class, 'updateClasificacion'])->name('clientes.updateClasificacion');

    Route::prefix('/config')->group(function () {
        Route::get('/cotizacionoro', [CotizacionOroController::class, 'index'])->name('config.cotizacionoro.index');
        Route::post('/cotizacionoro/bulk-update', [CotizacionOroController::class, 'bulkUpdate'])->name('config.cotizacionoro.update');
        Route::get('/parametros', [ParametrosController::class, 'index'])->name('config.parametros.index');
        Route::post('/parametros', [ParametrosController::class, 'store'])->name('config.parametros.store');
    });

    Route::prefix('/reportes')->group(function() {
        Route::get('/cartera', [ReporteCarteraController::class, 'generarReporteCartera'])->name('reportes.cartera');
        Route::get('/cartera/url-firmada', [ReporteCarteraController::class, 'generaUrlFirmadaReporteCartera']);
        Route::get('/flujo-caja', [ReporteFlujoCajaController::class, 'generarFlujoCaja'])->name('reportes.flujocaja');
        Route::get('/flujo-caja/url-firmada', [ReporteFlujoCajaController::class, 'generarUrlFirmada']);

        Route::get('/cierre-diario', [ReportesController::class, 'cierreDiario']);
        Route::get('/cierre-diario/url-firmada-pdf', [ReportesController::class, 'cierreDiarioUrlFirmadaPdf']);
        
        Route::post('/detalles-movimientos/preview', [App\Http\Controllers\ReporteDetallesMovimientosController::class, 'preview']);
        Route::post('/detalles-movimientos/url-firmada-pdf', [App\Http\Controllers\ReporteDetallesMovimientosController::class, 'urlFirmadaPdf']);
    });

    Route::prefix('/historial-cliente')->group(function() {
        Route::get('/buscar', [App\Http\Controllers\HistorialClienteController::class, 'buscar']);
        Route::get('/boleta/{id}', [App\Http\Controllers\HistorialClienteController::class, 'obtenerBoleta']);
        Route::get('/estadisticas/{clienteId}', [App\Http\Controllers\HistorialClienteController::class, 'historiaGeneral']);
        Route::get('/boleta/{folio}/pdf-url', [App\Http\Controllers\HistorialClienteController::class, 'reportePdfUrl']);
    });

    Route::get('/promociones', [PromocionController::class, 'index'])->name('promociones.index');
    Route::post('/boletas', [BoletaController::class, 'store'])->name('boletas.store');
    Route::get('/boletas/{id}', [BoletaController::class, 'show'])->name('boletas.show');
    Route::post('/boletas/pagos/refrendo', [PagoController::class, 'registrarRefrendo'])->name('boletas.refrendo');
    Route::post('/boletas/pagos/liquidacion', [BoletaController::class, 'procesarLiquidacion']);
    Route::post('/boletas/pagos/abono', [BoletaController::class, 'procesarAbono']);
    Route::get('/boletas', [BoletaController::class, 'index'])->name('boletas.index');
    Route::get('/boletas/{id}/pdf', [BoletaController::class, 'downloadPdf'])->name('boletas.pdf');
    Route::get('/boletas/{id}/detalles', [BoletaController::class, 'detalles']);

    Route::post('/movimientoscaja/{id}/registrar-efectivo', [MovimientoCajaController::class, 'registrarEfectivo'])->name('movimientoscaja.registrar-efectivo');

    Route::get('/movimientos/boleta/{id}/pagos', [BoletaMovimientoPagoController::class, 'consultaBoleta']);
    Route::post('/movimientos/registrar-pago', [BoletaMovimientoPagoController::class, 'registrarPago']);

    Route::get('cierre-diario/status', [CierreDiarioController::class, 'status'])->name('cierre-diario.status');
    Route::post('cierre-diario/procesar', [CierreDiarioController::class, 'ejecutarCierreManualmente'])->name('cierre-diario.procesar');

    Route::get('/ventas-joyeria/nota/{folio}', [VentaJoyeriaController::class, 'buscarNota']);
    Route::get('/ventas-joyeria/siguiente-folio', [VentaJoyeriaController::class, 'siguienteFolio']);
    Route::post('/ventas-joyeria/procesar', [VentaJoyeriaController::class, 'procesarVenta']);

    Route::get('/catalogos-joyeria/categorias', [CatalogoJoyeriaController::class, 'getCategorias']);
    Route::post('/catalogos-joyeria/categorias', [CatalogoJoyeriaController::class, 'storeCategoria']);
    Route::put('/catalogos-joyeria/categorias/{id}', [CatalogoJoyeriaController::class, 'updateCategoria']);
    Route::delete('/catalogos-joyeria/categorias/{id}', [CatalogoJoyeriaController::class, 'destroyCategoria']);

    Route::get('/catalogos-joyeria/clasificaciones', [CatalogoJoyeriaController::class, 'getClasificaciones']);
    Route::post('/catalogos-joyeria/clasificaciones', [CatalogoJoyeriaController::class, 'storeClasificacion']);
    Route::put('/catalogos-joyeria/clasificaciones/{id}', [CatalogoJoyeriaController::class, 'updateClasificacion']);
    Route::delete('/catalogos-joyeria/clasificaciones/{id}', [CatalogoJoyeriaController::class, 'destroyClasificacion']);

    Route::get('/ventas-electronicos/siguiente-folio', [VentaElectronicosController::class, 'siguienteFolio']);
    Route::get('/ventas-electronicos/nota/{folio}', [VentaElectronicosController::class, 'buscarNota']);
    Route::post('/ventas-electronicos/procesar', [VentaElectronicosController::class, 'procesarVenta']);

    Route::get('/caja/conceptos', [FlujoCajaController::class, 'getConceptos']);
    Route::get('/caja/movimientos', [FlujoCajaController::class, 'historial']);
    Route::get('/caja/inventario', [FlujoCajaController::class, 'inventarioCaja']);
    Route::apiResource('/flujo-conceptos', FlujoConceptoController::class);
    Route::post('/caja/entrada-manual', [FlujoCajaController::class, 'registrarEntrada']);
    Route::post('/caja/salida-manual', [FlujoCajaController::class, 'registrarSalida']);
    Route::get('/caja/movimiento/{id}/ticket-url', [FlujoCajaController::class, 'ticketUrlFirmada']);
    Route::get('/caja/check-apertura', [MovimientoCajaController::class, 'checkApertura']);
    Route::post('/caja/apertura', [MovimientoCajaController::class, 'registrarApertura']);
    // Route::get('reportes/flujo-caja', [ReportesController::class, 'flujoCaja']);
    Route::get('reportes/boletas-diarias', [ReportesController::class, 'boletasDiarias']);
    Route::get('reportes/boletas-diarias/url-firmada-pdf', [ReportesController::class, 'urlFirmadaBoletasDiariasPDF']);
    Route::get('/reportes/boletas-vencidas', [ReportesController::class, 'boletasVencidas']);
    Route::get('/reportes/boletas-vencidas/url-firmada-pdf', [ReportesController::class, 'boletasVencidasUrlFirmadaPdf']);
    //Route::get('/reportes/boletas-vencidas/url-firmada-excel', [ReportesController::class, 'boletasVencidasUrlFirmadaExcel']);
    // Reporte de Ventas Detallado
    Route::get('/reportes/ventas-detallado', [ReportesController::class, 'ventasDetallado']);
    Route::get('/reportes/ventas-detallado/url-firmada-pdf', [ReportesController::class, 'ventasUrlFirmadaPdf']);
    Route::get('/reportes/ventas-detallado/url-firmada-excel', [ReportesController::class, 'ventasUrlFirmadaExcel']);

    // Reporte de Compras Detallado
    Route::get('/reportes/compras-detallado', [ReportesController::class, 'comprasDetallado']);
    Route::get('/reportes/compras-detallado/url-firmada-pdf', [ReportesController::class, 'comprasUrlFirmadaPdf']);
    Route::get('/reportes/compras-detallado/url-firmada-excel', [ReportesController::class, 'comprasUrlFirmadaExcel']);

    Route::get('/dashboard/resumen', [DashboardController::class, 'resumenDiario']);

    Route::prefix('/database')->group(function () {
        Route::get('/backups', [DatabaseAdminController::class, 'index'])->name('database.backups.index');
        Route::post('/backups', [DatabaseAdminController::class, 'store'])->name('database.backups.store');
        Route::delete('/backups/{filename}', [DatabaseAdminController::class, 'destroy'])->name('database.backups.destroy');
        Route::get('/backups/{filename}/download', [DatabaseAdminController::class, 'download'])->name('database.backups.download');
        Route::post('/backups/restore', [DatabaseAdminController::class, 'restore'])->name('database.backups.restore');
        Route::post('/reset', [DatabaseAdminController::class, 'reset'])->name('database.reset');
    });
});

Route::get('/reportes/cartera/pdf', [ReporteCarteraController::class, 'generarPDF'])->name('reportes.cartera.pdf')->middleware('signed');
Route::get('/reportes/flujo-caja/pdf', [ReporteFlujoCajaController::class, 'generarPDF'])->name('reporte.flujocaja.pdf')->middleware('signed');
Route::get('/reportes/boletas-diarias/pdf', [ReportesController::class, 'reporteBoletasDiariasPDF'])->name('reportes.boletas_diarias_pdf')->middleware('signed');

Route::get('/exportar/boletas-vencidas/pdf', [ReportesController::class, 'exportarBoletasPdf'])->name('reportes.boletas-vencidas.pdf')->middleware('signed');
Route::get('/exportar/boletas-vencidas/excel', [ReportesController::class, 'exportarBoletasExcel'])->name('reportes.boletas-vencidas.excel')->middleware('signed');
Route::get('/caja/movimiento/{id}/ticket', [FlujoCajaController::class, 'imprimirTicket'])->name('caja.movimiento.ticket')->middleware('signed');
// Descargas Ventas
Route::get('/exportar/ventas/pdf', [ReportesController::class, 'exportarVentasPdf'])->name('reportes.ventas.pdf')->middleware('signed');
Route::get('/exportar/ventas/excel', [ReportesController::class, 'exportarVentasExcel'])->name('reportes.ventas.excel')->middleware('signed');

// Descargas Compras
Route::get('/exportar/compras/pdf', [ReportesController::class, 'exportarComprasPdf'])->name('reportes.compras.pdf')->middleware('signed');
Route::get('/exportar/compras/excel', [ReportesController::class, 'exportarComprasExcel'])->name('reportes.compras.excel')->middleware('signed');
Route::get('/exportar/cierre-diario/pdf', [ReportesController::class, 'exportarCierreDiarioPdf'])->name('reportes.cierre-diario.pdf')->middleware('signed');
Route::get('/exportar/detalles-movimientos/pdf', [App\Http\Controllers\ReporteDetallesMovimientosController::class, 'generarPDF'])->name('reportes.detalles-movimientos.pdf')->middleware('signed');

Route::get('/historial-cliente/boleta/{folio}/pdf', [App\Http\Controllers\HistorialClienteController::class, 'reportePdf'])->name('historial.pdf')->middleware('signed');
