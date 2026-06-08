<?php

namespace App\Rules;

use App\Support\ItalianFiscalValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCodiceFiscale implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ItalianFiscalValidator::isValidCodiceFiscale(is_string($value) ? $value : null)) {
            $fail(ItalianFiscalValidator::codiceFiscaleErrorMessage());
        }
    }
}
