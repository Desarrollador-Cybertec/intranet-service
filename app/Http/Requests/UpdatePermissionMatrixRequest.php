<?php

namespace App\Http\Requests;

use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PUT /api/permissions/matrix — guarda de una vez la matriz de varios roles.
 * Body: { "roles": [{ "id": "3", "permissions": { "sst": ["ver","crear"] } }] }
 */
class UpdatePermissionMatrixRequest extends FormRequest
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
            'roles' => ['required', 'array', 'min:1'],
            'roles.*.id' => ['required', Rule::exists('roles', 'id')],
            // 'present' (no 'required'): un rol puede legítimamente quedar sin ningún
            // permiso. 'required' trataría ese array vacío como "ausente" y lo rechazaría.
            'roles.*.permissions' => ['present', 'array', function (string $attribute, mixed $value, \Closure $fail) {
                foreach ((array) $value as $view => $actions) {
                    if (! Permissions::isValidView((string) $view)) {
                        $fail("La vista \"{$view}\" no existe.");

                        continue;
                    }

                    if (! is_array($actions)) {
                        $fail("Las acciones de \"{$view}\" deben ser una lista.");

                        continue;
                    }

                    foreach ($actions as $action) {
                        if (! Permissions::isValid((string) $view, (string) $action)) {
                            $fail("La acción \"{$action}\" no es válida para \"{$view}\".");
                        }
                    }
                }
            }],
        ];
    }
}
