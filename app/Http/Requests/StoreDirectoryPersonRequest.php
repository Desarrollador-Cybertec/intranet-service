<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDirectoryPersonRequest extends FormRequest
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
            'name' => [$required, 'string', 'max:255'],
            'area' => ['sometimes', 'nullable', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'extension' => ['sometimes', 'nullable', 'string', 'max:10'],
            'email' => [
                'sometimes', 'nullable', 'email', 'max:255',
                Rule::unique('directory_people', 'email')->ignore($this->route('directoryPerson')),
            ],
            'userId' => ['sometimes', 'nullable', Rule::exists('users', 'id')],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function mapped(): array
    {
        $data = $this->validated();

        if (array_key_exists('userId', $data)) {
            $data['user_id'] = $data['userId'];
            unset($data['userId']);
        }

        return $data;
    }
}
