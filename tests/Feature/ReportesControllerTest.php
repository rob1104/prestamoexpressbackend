<?php

use App\Models\User;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\Caja;
use App\Models\SucursalConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = Cliente::factory()->create();
    $this->categoria = Categoria::factory()->create(['nombre' => 'ORO']);
    $this->caja = Caja::factory()->create();
    
    SucursalConfig::create([
        'nombre_sucursal' => 'Matriz',
        'razon_social' => 'Empresa SA',
        'calle_num' => 'Calle 1',
        'colonia' => 'Centro',
        'municipio' => 'Monterrey',
        'estado' => 'NL',
        'codigo_postal' => '64000',
        'rfc' => 'XAXX010101000',
        'telefono_1' => '1234567890',
        'tamano_ticket' => '80mm',
        'salida_cartera_de' => '1',
        'salida_cartera_a' => '2',
        'p_comision' => 10,
        'p_iva' => 16,
        'p_avaluo' => 10,
        'p_almacenaje' => 10,
        'p_administracion' => 10,
        'p_custodia' => 10,
        'p_interes_dividido' => 10,
        'p_iva_interes' => 16,
    ]);
});

test('puede obtener reporte de boletas diarias', function () {
    Boleta::factory()->count(3)->create([
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id,
        'fecha_boleta' => now()->format('Y-m-d')
    ]);
    
    $response = $this->actingAs($this->user)->getJson('api/reportes/boletas-diarias');
    
    $response->assertStatus(200);
});

test('puede obtener reporte de boletas vencidas', function () {
    Boleta::factory()->count(2)->create([
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id,
        'fecha_vencimiento' => now()->subDays(5)->format('Y-m-d'),
        'estatus' => 'PE'
    ]);
    
    $response = $this->actingAs($this->user)->getJson('api/reportes/boletas-vencidas');
    
    $response->assertStatus(200);
});

test('puede generar url firmada para reporte de boletas diarias pdf', function () {
    $response = $this->actingAs($this->user)->getJson('api/reportes/boletas-diarias/url-firmada-pdf');
    
    $response->assertStatus(200)
             ->assertJsonStructure(['url']);
});
