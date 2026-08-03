<?php

use App\Models\Pago;
use App\Models\Boleta;
use App\Models\Cliente;
use App\Models\Categoria;
use App\Models\User;
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



test('puede registrar refrendo', function () {
    $boleta = Boleta::factory()->create([
        'cliente_id' => $this->cliente->id,
        'categoria_id' => $this->categoria->id,
        'prestamo' => 1000,
        'estatus' => 'PE'
    ]);
    
    $boleta->tradicional()->create([
        'refrendo' => 1,
        'fecha_vencimiento' => now()->addDays(30)->format('Y-m-d'),
        'dias_reales' => 30,
        'capital' => 1000,
        'interes' => 200,
        'almacenaje' => 0,
        'administracion' => 0,
        'custodia' => 0,
        'interesdividido' => 0,
        'iva_interes' => 0,
        'estatus' => 'PE',
        'user_id' => $this->user->id
    ]);
    
    $pagoData = [
        'boleta_id' => $boleta->id,
        'tipo_pago' => 'refrendo',
        'importe_pago' => 200,
        'dias_vencidos' => 0,
        'recargos' => 0,
        'total_pagado' => 200,
        'total_recibido' => 200,
        'caja_id' => $this->caja->id,
        'denominaciones' => json_encode(['500' => 1])
    ];
    
    $this->withoutExceptionHandling();
    $response = $this->actingAs($this->user)->postJson(route('boletas.refrendo'), $pagoData);
    
    $response->assertStatus(200)
             ->assertJsonStructure(['message', 'ticket_data']);
             
    $this->assertDatabaseHas('pagos', [
        'boleta_id' => $boleta->id,
        'tipo_movimiento' => 3,
        'importe' => 200
    ]);
});
