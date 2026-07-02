<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SumateActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * { "accionId": "yoAporto", "delta": 1 } — registrar/retirar una acción.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'accionId' => ['required', 'string', 'exists:sumate_acciones,slug'],
            'delta' => ['required', 'integer', 'in:-1,1'],
        ];
    }
}
