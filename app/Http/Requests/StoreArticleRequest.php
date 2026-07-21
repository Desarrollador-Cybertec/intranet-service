<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * En update (PUT) todos los campos son opcionales (sometimes).
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        $required = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'type' => [$required, Rule::in(['noticias', 'comunicados', 'reconocimientos', 'eventos'])],
            'tag' => [$required, 'string', 'max:255'],
            'tagBg' => [$required, 'string', 'max:255'],
            'tagColor' => [$required, 'string', 'max:255'],
            'title' => [$required, 'string', 'max:255'],
            'excerpt' => [$required, 'string'],
            'date' => [$required, 'string', 'max:255'],
            'eventDate' => ['sometimes', 'nullable', 'date'],
            'author' => [$required, 'string', 'max:255'],
            'imgs' => ['sometimes', 'array'],
            'imgs.*' => ['string'],
            'body' => [$required, 'string'],
        ];
    }

    /**
     * Mapea claves camelCase del contrato a columnas snake_case.
     *
     * @return array<string,mixed>
     */
    public function mapped(): array
    {
        $data = $this->validated();
        if (array_key_exists('tagBg', $data)) {
            $data['tag_bg'] = $data['tagBg'];
            unset($data['tagBg']);
        }
        if (array_key_exists('tagColor', $data)) {
            $data['tag_color'] = $data['tagColor'];
            unset($data['tagColor']);
        }
        if (array_key_exists('eventDate', $data)) {
            $data['event_date'] = $data['eventDate'];
            unset($data['eventDate']);
        }

        return $data;
    }
}
