<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de un usuario desde la administración (admin): perfil, rol y estado.
 * Las guardas de negocio (no auto-degradarse, no dejar cero admins) viven en
 * UserController, porque dependen del usuario autenticado y del resto de la tabla.
 */
class UpdateUserRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'roleType' => ['sometimes', 'in:admin,user'],
            'active' => ['sometimes', 'boolean'],
            'role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'joinedAt' => ['sometimes', 'nullable', 'date'],
            'extension' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
        ];
    }
}
