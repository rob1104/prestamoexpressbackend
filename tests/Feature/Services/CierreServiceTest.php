<?php

use App\Services\CierreService;
use App\Models\SucursalConfig;
use App\Models\CierreDiario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Configuración básica para los tests de CierreService
    SucursalConfig::create([
        'no_sucursal' => 1,
        'nombre_sucursal' => 'Test',
        'razon_social' => 'TEST',
        'calle_num' => 'Test',
        'colonia' => 'Test',
        'municipio' => 'Test',
        'estado' => 'Test',
        'codigo_postal' => '12345',
        'rfc' => 'TEST123456789',
        'telefono_1' => '1234567890',
        'p_comision' => 10,
        'p_iva' => 16,
        'p_avaluo' => 5,
        'tamano_ticket' => '58mm',
        'salida_cartera_de' => '1',
        'salida_cartera_a' => '10',
        'hora_cierre' => '18:00:00',
    ]);
    
    // Usuario por defecto para el cierre
    User::factory()->create(['id' => 1]);
});

test('checkStatus arroja excepcion si no hay configuracion', function () {
    SucursalConfig::truncate();
    CierreService::checkStatus();
})->throws(\Exception::class, 'No se encontró la configuración de la sucursal.');

test('checkStatus retorna pendiente si falta un cierre anterior', function () {
    // Simulamos que el último cierre fue hace 2 días
    $haceDosDias = Carbon::now()->subDays(2)->format('Y-m-d');
    
    CierreDiario::create([
        'fecha_cierre' => $haceDosDias,
        'user_id' => 1
    ]);
    
    $status = CierreService::checkStatus();
    
    expect($status['pendiente'])->toBeTrue()
        ->and($status['dias_faltantes'])->toBe(1); // Falta el de ayer
});

test('ejecutarCierreManualmente genera cierres pendientes', function () {
    // Ultimo cierre hace 2 días
    $haceDosDias = Carbon::now()->subDays(2)->startOfDay();
    
    CierreDiario::create([
        'fecha_cierre' => $haceDosDias->format('Y-m-d'),
        'user_id' => 1
    ]);
    
    $resultado = CierreService::ejecutarCierreManualmente();
    
    expect($resultado['status'])->toBe('success')
        ->and(count($resultado['dias_cerrados']))->toBe(1); // Debe haber procesado ayer
        
    $cierreAyer = CierreDiario::where('fecha_cierre', Carbon::now()->subDay()->format('Y-m-d'))->first();
    expect($cierreAyer)->not->toBeNull();
});

test('procesarCierreDia suma correctamente los pagos y ventas', function () {
    $ayer = Carbon::now()->subDay()->startOfDay();
    
    $caja = \App\Models\Caja::factory()->create(['id' => 1]);
    $boleta = \App\Models\Boleta::factory()->create(['id' => 1, 'user_id' => 1]);
    
    // Crear un pago de préstamo nuevo (tipo_movimiento = 1)
    DB::table('pagos')->insert([
        'fecha' => $ayer->format('Y-m-d'),
        'estatus' => 'A',
        'tipo_movimiento' => 1,
        'importe' => 1000,
        'capital' => 0,
        'interestotal' => 0,
        'recargosNormal' => 0,
        'boleta_id' => 1,
        'no_pago' => 1,
        'prestamo' => 1000,
        'user_id' => 1,
        'totalPagado' => 1000,
        'totalRecibido' => 1000,
        'caja_id' => 1,
        'created_at' => $ayer
    ]);
    
    // Crear venta de joyería general
    DB::table('ventas_joyeria_general')->insert([
        'id' => 1,
        'tipo_venta' => 'T',
        'modo' => 'C',
        'fecha_movimiento' => $ayer->format('Y-m-d'),
        'cliente' => 'Test',
        'usuario_id' => 1,
        'caja_id' => 1,
        'created_at' => $ayer
    ]);

    // Crear una venta de joyería
    DB::table('ventas_joyeria_pagos')->insert([
        'venta_id' => 1,
        'no_pago' => 1,
        'fecha_pago' => $ayer->format('Y-m-d'),
        'estatus' => 'A',
        'importe' => 500,
        'saldo_pagar' => 500,
        'resto_pagar' => 0,
        'tipo_venta' => 'T',
        'modo' => 'C',
        'usuario_id' => 1,
        'caja_id' => 1,
        'created_at' => $ayer
    ]);
    
    // No hay cierres, así que intentará procesar desde la fecha del min(Boleta::created_at) o hoy
    // Forzaremos el último cierre hace 2 días para que procese ayer
    CierreDiario::create([
        'fecha_cierre' => Carbon::now()->subDays(2)->format('Y-m-d'),
        'user_id' => 1
    ]);
    
    CierreService::ejecutarCierreManualmente();
    
    $cierreAyer = CierreDiario::where('fecha_cierre', $ayer->format('Y-m-d'))->first();
    
    expect($cierreAyer->prestamos_nuevos)->toEqual(1000)
        ->and($cierreAyer->ventas_joyeria)->toEqual(500)
        ->and($cierreAyer->boletas_nuevas)->toEqual(1);
});
