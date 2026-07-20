<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El email NO es editable por el usuario (ver contrato PATCH /auth/me).
     * Estos son también los campos del onboarding de usuarios importados.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'role' => ['sometimes', 'string', 'max:255'],
            'area' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:255'],
            'joinedAt' => ['sometimes', 'date'],
            'extension' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * El contrato usa camelCase; las columnas, snake_case.
     *
     * @return array<string,mixed>
     */
    public function profileData(): array
    {
        $data = $this->validated();

        if (array_key_exists('joinedAt', $data)) {
            $data['joined_at'] = $data['joinedAt'];
            unset($data['joinedAt']);
        }

        return $data;
    }
}
