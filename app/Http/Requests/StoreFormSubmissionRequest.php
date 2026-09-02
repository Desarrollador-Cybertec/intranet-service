<?php

namespace App\Http\Requests;

use App\Forms\FormRegistry;
use Illuminate\Foundation\Http\FormRequest;

class StoreFormSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $form = FormRegistry::find((string) $this->route('slug'));
        if (! $form) {
            return [];
        }

        $rules = $form->fieldRules();

        if ($form->allowsAttachments()) {
            $rules['attachments'] = ['sometimes', 'array', 'max:'.$form->maxAttachments()];
            $rules['attachments.*'] = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'];
        }

        return $rules;
    }
}
