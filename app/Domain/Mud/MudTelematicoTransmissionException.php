<?php

namespace App\Domain\Mud;

use RuntimeException;

class MudTelematicoTransmissionException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $httpStatus = null,
        public readonly ?string $correlationId = null,
    ) {
        parent::__construct($message);
    }
}
