<?php

namespace App\Services\Rentri\Contracts;

use App\Models\Fir;

interface RentriFirSigningServiceInterface
{
    public function sign(Fir $fir): Fir;

    public function canSign(Fir $fir): bool;

    public function signBlockReason(Fir $fir): ?string;

    public function signedPayloadFilename(Fir $fir): string;
}
