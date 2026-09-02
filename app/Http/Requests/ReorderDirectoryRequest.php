<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderDirectoryRequest extends FormRequest
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
            'ids' => ['present', 'array'],
            'ids.*' => ['integer', 'exists:directory_people,id'],
        ];
    }
}
