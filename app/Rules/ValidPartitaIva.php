<?php

namespace App\Rules;

use App\Support\ItalianFiscalValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPartitaIva implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ItalianFiscalValidator::isValidPartitaIva(is_string($value) ? $value : null)) {
            $fail(ItalianFiscalValidator::partitaIvaErrorMessage());
        }
    }
}
