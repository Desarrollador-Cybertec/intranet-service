<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreModuleRequest extends FormRequest
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
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'slug' => [$required, 'string', 'max:255'],
            'label' => [$required, 'string', 'max:255'],
            'icon' => [$required, 'string', 'max:255'],
            'color' => [$required, 'string', 'max:255'],
            'bg' => [$required, 'string', 'max:255'],
            'desc' => [$required, 'string'],
        ];
    }
}
