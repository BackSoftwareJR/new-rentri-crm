<?php

namespace App\Services\Rentri\Exceptions;

use RuntimeException;

class RentriRegistroConformitaException extends RuntimeException
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly array $errors,
    ) {
        parent::__construct('Payload registro non conforme: '.implode(' ', $errors));
    }
}
