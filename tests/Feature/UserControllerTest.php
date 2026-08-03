<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Configurar roles requeridos por el sistema
    Role::create(['name' => 'Administrador']);
    Role::create(['name' => 'Cajero']);
    
    // Usuario autenticado con rol Administrador
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Administrador');
    
    // Usuario normal
    $this->user = User::factory()->create();
    $this->user->assignRole('Cajero');
});

test('usuarios sin autenticacion reciben 401', function () {
    $response = $this->getJson(route('users.index'));
    $response->assertStatus(401);
});

test('administradores pueden listar usuarios', function () {
    $response = $this->actingAs($this->admin)->getJson(route('users.index'));
    
    $response->assertStatus(200)
             ->assertJsonStructure([
                 '*' => ['id', 'name', 'email', 'roles', 'active']
             ]);
});

test('puede crear un nuevo usuario con rol', function () {
    $userData = [
        'name' => 'Nuevo Usuario',
        'email' => 'nuevo@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'Cajero'
    ];
    
    $response = $this->actingAs($this->admin)->postJson(route('users.store'), $userData);
    
    $response->assertStatus(201)
             ->assertJsonPath('user.email', 'nuevo@test.com');
             
    $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
    
    // Verificar que se le asignó el rol
    $newUser = User::where('email', 'nuevo@test.com')->first();
    expect($newUser->hasRole('Cajero'))->toBeTrue();
});

test('valida campos obligatorios al crear usuario', function () {
    $response = $this->actingAs($this->admin)->postJson(route('users.store'), []);
    $response->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
});

test('puede actualizar un usuario', function () {
    $userData = [
        'name' => 'Usuario Actualizado',
        'email' => $this->user->email,
        'role' => 'Administrador'
    ];
    
    $response = $this->actingAs($this->admin)->putJson(route('users.update', $this->user->id), $userData);
    
    $response->assertStatus(200)
             ->assertJsonPath('user.name', 'Usuario Actualizado');
             
    expect($this->user->fresh()->hasRole('Administrador'))->toBeTrue();
});

test('puede alternar el estado activo de un usuario', function () {
    expect((bool) $this->user->active)->toBeTrue();
    
    $response = $this->actingAs($this->admin)->patchJson(route('users.toggleStatus', $this->user->id));
    
    $response->assertStatus(200);
    expect((bool) $this->user->fresh()->active)->toBeFalse();
});

test('puede eliminar un usuario', function () {
    $response = $this->actingAs($this->admin)->deleteJson(route('users.destroy', $this->user->id));
    
    $response->assertStatus(200);
    $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
});
