<?php

namespace App\Http\Requests;

use App\Support\Permissions;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['sometimes', 'array', $this->permissionsShapeRule()],
        ];
    }

    protected function permissionsShapeRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
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
        };
    }
}
