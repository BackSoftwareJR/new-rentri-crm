<?php

namespace App\Support\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

/**
 * Formatter JSON strutturato per canali produzione (CloudWatch / SIEM friendly).
 */
class JsonLogTap
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
