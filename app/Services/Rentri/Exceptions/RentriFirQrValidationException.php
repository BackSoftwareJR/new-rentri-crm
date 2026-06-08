<?php

namespace App\Services\Rentri\Exceptions;

use RuntimeException;

class RentriFirQrValidationException extends RuntimeException
{
    /**
     * @param  list<string>  $italianMessages
     */
    public function __construct(
        public readonly array $italianMessages,
    ) {
        parent::__construct(implode(' ', $italianMessages));
    }
}
