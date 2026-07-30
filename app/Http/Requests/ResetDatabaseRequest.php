<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'confirmation' => 'required|string|in:CONFIRMAR',
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.in' => 'Debe escribir CONFIRMAR para continuar.',
        ];
    }
}
