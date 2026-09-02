<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncUserRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            // 'present' (no 'required'): un usuario puede legítimamente quedarse sin
            // ningún rol propio (solo Cualquiera). 'required' en Laravel trata un array
            // vacío como "ausente" y rechazaría precisamente ese caso válido.
            'roleSlugs' => ['present', 'array'],
            'roleSlugs.*' => ['string', 'exists:roles,slug'],
        ];
    }
}
