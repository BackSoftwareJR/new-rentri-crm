<?php

namespace App\Services\Rentri\Dto;

use App\Services\Rentri\RentriEndpoints;
use App\Services\Rentri\RentriFirVidimaTransmissionMapper;

/**
 * Adapter request vidimazione FIR RENTRI v1.0.
 *
 * @see https://demoapi.rentri.gov.it/docs?page=api-flussi-operativi-formulari
 */
readonly class RentriFirVidimaRequest
{
    public function __construct(
        public string $codiceBlocco,
        public string $numIscrSito,
        /** @var array<string, mixed> */
        public array $payload = [],
    ) {}

    public function httpMethod(): string
    {
        return 'POST';
    }

    public function logicalEndpoint(): string
    {
        return '/fir/vidima';
    }

    public function livePath(): string
    {
        return RentriEndpoints::firVidimaPath($this->codiceBlocco);
    }

    /**
     * Body HTTP ministeriale (senza metadati CRM).
     *
     * @return array<string, mixed>
     */
    public function body(): array
    {
        return RentriFirVidimaTransmissionMapper::forTransmission($this->numIscrSito, $this->payload);
    }

    /**
     * Metadati CRM per audit locale (non inviati a MASE).
     *
     * @return array<string, mixed>
     */
    public function crmAuditPayload(): array
    {
        return RentriFirVidimaTransmissionMapper::crmAuditOnly($this->payload);
    }
}
