<?php

namespace App\Services\Rentri\Contracts;

use App\Models\RentriTransmissione;
use App\Services\Rentri\Dto\TransmissionPayload;
use Carbon\CarbonInterface;

interface RentriRegistryServiceInterface
{
    public function buildTransmissionPayload(CarbonInterface $periodoDa, CarbonInterface $periodoA): TransmissionPayload;

    public function transmit(TransmissionPayload $payload): RentriTransmissione;
}
