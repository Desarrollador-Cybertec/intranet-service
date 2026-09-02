<?php

namespace App\Http\Requests;

use App\Models\FormSubmission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(FormSubmission::STATUSES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
