<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AllowedEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $domains = config('insumma.allowed_registration_domains');

        if ($domains === []) {
            return;
        }

        $domain = Str::lower(Str::after((string) $value, '@'));

        if (! in_array($domain, array_map(Str::lower(...), $domains), true)) {
            $fail('Solo se permite el registro con un correo institucional ('.implode(', ', $domains).').');
        }
    }
}
