<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('La password deve essere una stringa valida.');

            return;
        }

        if (strlen($value) < 8) {
            $fail('La password deve avere almeno 8 caratteri.');

            return;
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('La password deve contenere almeno una lettera maiuscola.');

            return;
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('La password deve contenere almeno un numero.');

            return;
        }

        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('La password deve contenere almeno un carattere speciale.');
        }
    }
}
