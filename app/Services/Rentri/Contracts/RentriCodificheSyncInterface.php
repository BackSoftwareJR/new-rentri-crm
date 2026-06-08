<?php

namespace App\Services\Rentri\Contracts;

interface RentriCodificheSyncInterface
{
    /**
     * Sincronizza codici CER e riferimenti RENTRI dal catalogo ufficiale.
     *
     * @return array{
     *   created: int,
     *   updated: int,
     *   deactivated: int,
     *   skipped: int,
     *   created_codes: list<string>,
     *   updated_codes: list<string>,
     *   deactivated_codes: list<string>
     * }
     */
    public function sync(): array;
}
