<?php

namespace App\Services\Rentri\Contracts;

interface RentriFirBlocchiSyncInterface
{
    /**
     * @return array{created: int, updated: int, skipped: int}
     */
    public function sync(): array;
}
