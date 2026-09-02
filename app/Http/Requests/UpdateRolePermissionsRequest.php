<?php

namespace App\Http\Requests;

use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRolePermissionsRequest extends FormRequest
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
            // 'present' (no 'required'): un rol puede legítimamente quedar sin ningún
            // permiso. 'required' trataría ese array vacío como "ausente" y lo rechazaría.
            'permissions' => ['present', 'array', function (string $attribute, mixed $value, \Closure $fail) {
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
