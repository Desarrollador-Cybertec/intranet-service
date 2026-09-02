<?php

namespace App\Http\Requests;

use App\Rules\AllowedEmailDomain;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El chequeo de duplicados en AuthController@register es `where('email', ...)`:
     * sin normalizar a minúsculas aquí, "Juan@Insumma.co" y "juan@insumma.co"
     * crearían cuentas distintas que luego chocan al iniciar sesión.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => strtolower((string) $this->input('email'))]);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', new AllowedEmailDomain],
            'password' => ['required', 'string', 'min:8'],
            'area' => ['required', 'string', 'max:255'],
        ];
    }
}
