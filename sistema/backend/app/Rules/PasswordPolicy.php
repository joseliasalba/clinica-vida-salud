<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordPolicy implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || mb_strlen($value) < 15 || mb_strlen($value) > 128
            || in_array(mb_strtolower($value), config('seguridad.claves_comunes'), true)
            || preg_match('/^(.)\1+$/us', $value)) {
            $fail('Usa una frase de entre 15 y 128 caracteres. Evita claves comunes o un solo carácter repetido.');
        }
    }
}
