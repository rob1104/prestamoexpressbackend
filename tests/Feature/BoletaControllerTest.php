<?php

use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\User;
use App\Models\SucursalConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cliente = Cliente::factory()->create();
    $this->categoria = Categoria::factory()->create(['nombre' => 'ORO']);
    
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

test('puede listar boletas', function () {
    Boleta::factory()->count(3)->create([
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id
    ]);
    
    $response = $this->actingAs($this->user)->getJson(route('boletas.index'));
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 'data' => [
                     '*' => ['id', 'cliente_id', 'categoria_id', 'prestamo']
                 ]
             ]);
});

test('puede crear una boleta', function () {
    $boletaData = [
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id,
        'fecha_boleta' => '2026-08-01',
        'fecha_vencimiento' => '2026-09-01',
        'fecha_vencimiento_raw' => '2026-09-01',
        'fecha_comercializacion' => '2026-10-01',
        'cotizacion_gramo' => 500,
        'prestamo' => 1000,
        'tipo_prestamo' => 'tradicional',
        'meses' => 1,
        'numero_pagos' => 1,
        'plazo_dias' => 30,
        'valor_comercial' => 1500,
        'p_interes' => 20,
        'comision' => 200,
        'iva_comision' => 32,
        'total_pagar' => 1232,
        'observaciones' => 'Test',
        'caja_id' => 1,
        'no_bolsa' => '12345',
        'partidas' => [
            [
                'tipo' => 'kilate',
                'subtipo' => '14K',
                'gramos_cantidad' => 2.5,
                'costo_unitario' => 500,
                'valor' => 1500,
                'descripcion' => 'Anillo de oro'
            ]
        ]
    ];
    
    $this->withoutExceptionHandling();
    $response = $this->actingAs($this->user)->postJson(route('boletas.store'), $boletaData);
    
    $response->assertStatus(201)
             ->assertJsonPath('boleta.prestamo', "1000.00");
             
    $this->assertDatabaseHas('boletas', ['prestamo' => 1000, 'cliente_id' => $this->cliente->id]);
});

test('puede mostrar una boleta', function () {
    $boleta = Boleta::factory()->create([
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id,
        'tipo_prestamo' => 'tradicional',
        'estatus' => 'PE'
    ]);
    
    $this->withoutExceptionHandling();
    $response = $this->actingAs($this->user)->getJson(route('boletas.show', $boleta->id));
    
    $response->assertStatus(200)
             ->assertJsonPath('boleta.id', $boleta->id);
});
