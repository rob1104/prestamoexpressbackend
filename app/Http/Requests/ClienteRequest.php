<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clienteId = $this->route('cliente') ? $this->route('cliente')->id : null;

        return [
            'nombre'         => 'required|string|max:191',
            'identificacion' => [
                'required',
                'string',
                'max:191',
                Rule::unique('clientes')->ignore($clienteId),
            ],
            'clasificacion'  => 'required|in:NUEVO,EXCELENTE,BUENO,REGULAR,MALO',
            'telefono1'      => 'required|digits:10',
            'telefono2'      => 'nullable|digits:10',
            'callenum'       => 'required|string|max:191',
            'colonia'        => 'required|string|max:191',
            'municipio'      => 'required|string|max:191',
            'estado'         => 'required|string|max:191',
            'codPostal'      => 'required|string|size:5',
            'ocupacion'      => 'nullable|string|max:191',
            'observacion'    => 'nullable|string',
            'ineFrente'      => 'nullable|string',
            'ineReverso'     => 'nullable|string',
            'email'          => 'nullable|string'
        ];
    }

    public function messages(): array
    {
        return [
            'identificacion.unique' => 'Esta identificación ya está registrada con otro cliente.',
            'telefono1.digits'      => 'El teléfono debe ser exactamente de 10 dígitos.',
            'codPostal.size'        => 'El código postal debe tener 5 dígitos.',
        ];
    }
}
