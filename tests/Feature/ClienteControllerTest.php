<?php

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('puede listar clientes', function () {
    Cliente::factory()->count(3)->create();
    
    $response = $this->actingAs($this->user)->getJson(route('clientes.index'));
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 '*' => ['id', 'nombre', 'identificacion', 'clasificacion', 'telefono1']
             ]);
});

test('puede crear un cliente', function () {
    $clienteData = [
        'nombre' => 'Test Cliente',
        'apellido_paterno' => 'Perez',
        'identificacion' => 'INE9999999999',
        'clasificacion' => 'NUEVO',
        'telefono1' => '1234567890',
        'callenum' => 'Calle Falsa',
        'colonia' => 'Centro',
        'municipio' => 'Monterrey',
        'estado' => 'NL',
        'codPostal' => '64000',
    ];
    
    $response = $this->actingAs($this->user)->postJson(route('clientes.store'), $clienteData);
    
    $response->assertStatus(201)
             ->assertJsonPath('cliente.nombre', 'TEST CLIENTE');
             
    $this->assertDatabaseHas('clientes', ['identificacion' => 'INE9999999999']);
});

test('puede mostrar un cliente', function () {
    $cliente = Cliente::factory()->create();
    
    $response = $this->actingAs($this->user)->getJson(route('clientes.show', $cliente->id));
    
    $response->assertStatus(200)
             ->assertJsonPath('id', $cliente->id);
});

test('puede actualizar un cliente', function () {
    $cliente = Cliente::factory()->create();
    
    $updateData = $cliente->toArray();
    $updateData['nombre'] = 'Nombre Actualizado';
    
    $response = $this->actingAs($this->user)->putJson(route('clientes.update', $cliente->id), $updateData);
    
    $response->assertStatus(200)
             ->assertJsonPath('cliente.nombre', 'Nombre Actualizado');
             
    $this->assertDatabaseHas('clientes', ['id' => $cliente->id, 'nombre' => 'Nombre Actualizado']);
});

test('puede buscar clientes', function () {
    $cliente = Cliente::factory()->create(['nombre' => 'BuscarMe']);
    Cliente::factory()->create(['nombre' => 'Otro']);
    
    $response = $this->actingAs($this->user)->getJson(route('clientes.search', ['query' => 'BuscarMe']));
    
    $response->assertStatus(200);
    $data = $response->json();
    expect(count($data))->toBeGreaterThanOrEqual(1);
    expect($data[0]['nombre'])->toBe('BuscarMe');
});

test('puede actualizar clasificacion', function () {
    $cliente = Cliente::factory()->create(['clasificacion' => 'NUEVO']);
    
    $response = $this->actingAs($this->user)->patchJson(route('clientes.updateClasificacion', $cliente->id), [
        'clasificacion' => 'EXCELENTE'
    ]);
    
    $response->assertStatus(200);
    expect($cliente->fresh()->clasificacion)->toBe('EXCELENTE');
});
