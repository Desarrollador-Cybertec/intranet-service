<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SumateActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * { "participantId": 7, "accionId": "yoAporto", "delta": 1 }
     * El admin otorga/retira una acción a un participante concreto.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'participantId' => ['required', 'integer', 'exists:sumate_participants,id'],
            'accionId' => ['required', 'string', 'exists:sumate_acciones,slug'],
            'delta' => ['required', 'integer', 'in:-1,1'],
        ];
    }
}
