<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // We'll handle authorization via middleware/gates on the controller
    }

    public function rules(): array
    {
        return [
            'filename' => 'required|string',
        ];
    }
}
