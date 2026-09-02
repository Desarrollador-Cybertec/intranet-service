<?php

namespace App\Http\Requests;

use App\Forms\FormRegistry;
use App\Models\Module;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
        $section = $this->route('section') ?? 'rh';

        return [
            'slug' => [
                $required, 'string', 'max:255', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('modules', 'slug')->where('section', $section)->ignore($this->route('slug'), 'slug'),
            ],
            'label' => [$required, 'string', 'max:255'],
            'icon' => [$required, 'string', 'max:255'],
            'color' => [$required, 'string', 'max:255'],
            'bg' => [$required, 'string', 'max:255'],
            'desc' => [$required, 'string'],
            'type' => ['sometimes', Rule::in(Module::TYPES)],
            'href' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'config' => ['sometimes', 'nullable', 'array'],
            'visible' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            // En PATCH parcial, si no llega `type` no hay nada nuevo que revisar aquí.
            if (! $this->has('type') && $this->isMethod('put')) {
                return;
            }

            $type = $this->input('type', 'enlace');
            $config = (array) $this->input('config', []);
            $href = $this->input('href');

            if ($type === 'enlace' && ! $href && empty($config['section'])) {
                $validator->errors()->add('href', 'Un módulo de tipo enlace necesita un destino (href o config.section).');
            }

            if ($type === 'documento' && ! $href && empty($config['docs'])) {
                $validator->errors()->add('href', 'Un módulo de tipo documento necesita un archivo (href o config.docs).');
            }

            if ($type === 'formulario') {
                if (empty($config['formSlug'])) {
                    $validator->errors()->add('config', 'Un módulo de tipo formulario necesita config.formSlug.');
                } elseif (! in_array($config['formSlug'], FormRegistry::slugs(), true)) {
                    $validator->errors()->add('config', 'config.formSlug no corresponde a ningún formulario registrado.');
                }
            }

            if ($type === 'calendario' && empty($config['calendarUrl'])) {
                $validator->errors()->add('config', 'Un módulo de tipo calendario necesita config.calendarUrl.');
            }
        });
    }
}
