<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSumateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accionId' => ['required', 'string', 'exists:sumate_acciones,slug'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
