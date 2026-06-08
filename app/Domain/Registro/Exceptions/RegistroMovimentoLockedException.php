<?php

namespace App\Domain\Registro\Exceptions;

use RuntimeException;

class RegistroMovimentoLockedException extends RuntimeException
{
    public static function cannotModify(): self
    {
        return new self('Movimento registro già trasmesso a RENTRI: modifica o cancellazione non consentita.');
    }
}
