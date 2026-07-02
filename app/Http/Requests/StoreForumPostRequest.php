<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreForumPostRequest extends FormRequest
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
            'title' => [$required, 'string', 'max:255'],
            'body' => [$required, 'string'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string'],
            'tag' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
