<?php

namespace App\Services\Rentri\Dto;

use App\Models\Fir;
use App\Models\RentriSetting;
use App\Services\Rentri\RentriEndpoints;
use App\Services\Rentri\RentriXfirCoseTransmissionMapper;

/**
 * Adapter request invio payload xFIR firmato (COSE_Sign1) a RENTRI v1.0.
 */
readonly class RentriXfirTrasmissioneRequest
{
    /**
     * @param  array<string, mixed>  $signedPayload
     */
    public function __construct(
        public Fir $fir,
        public array $signedPayload,
        public RentriSetting $settings,
    ) {}

    public function logicalEndpoint(): string
    {
        return '/xfir/trasmetti';
    }

    public function livePath(): string
    {
        return RentriEndpoints::XFIR_TRASMISSIONE;
    }

    /**
     * @return array<string, mixed>
     */
    public function body(): array
    {
        $identificativo = $this->settings->cf_operatore ?: $this->settings->cf ?: '';

        return [
            'identificativo'  => $identificativo,
            'num_iscr_sito'   => $this->settings->num_iscr_sito,
            'numero_fir'      => $this->fir->numero_fir,
            'codice_blocco'   => $this->fir->codice_blocco,
            'progressivo'     => $this->fir->progressivo,
            'typ'             => $this->signedPayload['typ'] ?? 'COSE_Sign1',
            'payload_firmato' => RentriXfirCoseTransmissionMapper::forTransmission($this->signedPayload),
        ];
    }
}
