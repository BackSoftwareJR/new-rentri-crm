<?php

namespace App\Services\Rentri\Dto;

use Carbon\CarbonInterface;

readonly class TransmissionPayload
{
    /**
     * @param  list<array<string, mixed>>  $movimenti
     */
    public function __construct(
        public CarbonInterface $periodoDa,
        public CarbonInterface $periodoA,
        public string $payloadHash,
        public array $movimenti,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'periodo_da'   => $this->periodoDa->toDateString(),
            'periodo_a'    => $this->periodoA->toDateString(),
            'payload_hash' => $this->payloadHash,
            'movimenti'    => $this->movimenti,
            'metadata'     => $this->metadata,
        ];
    }
}
