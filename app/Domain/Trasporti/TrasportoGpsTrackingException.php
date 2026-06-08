<?php

namespace App\Domain\Trasporti;

use RuntimeException;

class TrasportoGpsTrackingException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message);
    }
}
