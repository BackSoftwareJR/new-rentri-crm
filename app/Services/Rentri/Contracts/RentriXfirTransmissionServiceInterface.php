<?php

namespace App\Services\Rentri\Contracts;

use App\Models\Fir;

interface RentriXfirTransmissionServiceInterface
{
    public function transmit(Fir $fir): Fir;

    public function canTransmit(Fir $fir): bool;
}
