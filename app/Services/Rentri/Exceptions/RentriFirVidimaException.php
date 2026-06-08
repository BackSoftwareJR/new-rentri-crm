<?php

namespace App\Services\Rentri\Exceptions;

use RuntimeException;

class RentriFirVidimaException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct('Vidimazione FIR non consentita: '.implode(' ', $errors));
    }
}
