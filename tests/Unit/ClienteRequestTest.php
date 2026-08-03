<?php

use App\Http\Requests\ClienteRequest;
use Illuminate\Support\Facades\Validator;
use App\Models\Cliente;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

function validateField($field, $value, $rules)
{
    return Validator::make([$field => $value], [$field => $rules]);
}

test('requiere nombre', function () {
    $request = new ClienteRequest();
    $validator = validateField('nombre', '', $request->rules()['nombre']);
    
    expect($validator->fails())->toBeTrue();
});

test('requiere identificacion unica', function () {
    $cliente = Cliente::factory()->create();
    $request = new ClienteRequest();
    
    $validator = Validator::make(
        ['identificacion' => $cliente->identificacion],
        $request->rules()
    );
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('identificacion'))->toBeTrue();
});

test('requiere clasificacion valida', function () {
    $request = new ClienteRequest();
    $validator = validateField('clasificacion', 'INVALIDO', $request->rules()['clasificacion']);
    
    expect($validator->fails())->toBeTrue();
});

test('requiere telefonos de 10 digitos', function () {
    $request = new ClienteRequest();
    $validator1 = validateField('telefono1', '123', $request->rules()['telefono1']);
    $validator2 = validateField('telefono2', '12345678901', $request->rules()['telefono2']);
    
    expect($validator1->fails())->toBeTrue()
        ->and($validator2->fails())->toBeTrue();
});

test('requiere codigo postal de 5 digitos', function () {
    $request = new ClienteRequest();
    $validator = validateField('codPostal', '1234', $request->rules()['codPostal']);
    
    expect($validator->fails())->toBeTrue();
});

test('pasa con datos validos', function () {
    $request = new ClienteRequest();
    $data = [
        'nombre' => 'Juan',
        'apellido_paterno' => 'Perez',
        'identificacion' => 'INE1234567890',
        'clasificacion' => 'NUEVO',
        'telefono1' => '1234567890',
        'callenum' => 'Calle 1',
        'colonia' => 'Colonia 1',
        'municipio' => 'Municipio 1',
        'estado' => 'Estado 1',
        'codPostal' => '12345'
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeFalse();
});
