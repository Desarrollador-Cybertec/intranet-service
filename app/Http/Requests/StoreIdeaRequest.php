<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdeaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Replica ideaSchema del front: title 5–100, description 20–500, category enum, anonymous opcional.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:100'],
            'title' => ['required', 'string', 'min:5', 'max:100'],
            'description' => ['required', 'string', 'min:20', 'max:500'],
            'anonymous' => ['sometimes', 'boolean'],
        ];
    }
}
