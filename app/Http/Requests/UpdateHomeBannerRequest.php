<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:600'],
            'colorFrom' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colorTo' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'ctaLabel' => ['sometimes', 'nullable', 'string', 'max:60'],
            'ctaSection' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }
}
