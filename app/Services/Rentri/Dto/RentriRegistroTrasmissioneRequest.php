<?php

namespace App\Services\Rentri\Dto;

use App\Models\RentriSetting;
use App\Services\Rentri\RentriEndpoints;
use Illuminate\Support\Carbon;

/**
 * Adapter request trasmissione registro cronologico RENTRI v1.0.
 *
 * @see https://demoapi.rentri.gov.it/docs
 */
readonly class RentriRegistroTrasmissioneRequest
{
    public function __construct(
        public TransmissionPayload $payload,
        public RentriSetting $settings,
    ) {}

    public static function fromPayload(TransmissionPayload $payload, RentriSetting $settings): self
    {
        return new self($payload, $settings);
    }

    public function httpMethod(): string
    {
        return 'POST';
    }

    public function logicalEndpoint(): string
    {
        return '/registro/trasmetti';
    }

    public function livePath(): string
    {
        return RentriEndpoints::REGISTRO_TRASMISSIONE;
    }

    /**
     * Payload conforme schema ministeriale RENTRI v1.0.
     *
     * @return array<string, mixed>
     */
    public function body(): array
    {
        $identificativo = $this->settings->cf_operatore ?: $this->settings->cf ?: '';

        return [
            'identificativo' => $identificativo,
            'num_iscr_sito'  => $this->settings->num_iscr_sito,
            'periodo_dal'    => $this->payload->periodoDa->toDateString(),
            'periodo_al'     => $this->payload->periodoA->toDateString(),
            'movimenti'      => array_map(
                fn (array $movimento) => $this->mapMovimento($movimento),
                $this->payload->movimenti,
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $movimento
     * @return array<string, mixed>
     */
    private function mapMovimento(array $movimento): array
    {
        return [
            'codice_cer'          => (string) ($movimento['codice_cer'] ?? ''),
            'tipo_movimento'      => strtoupper((string) ($movimento['tipo'] ?? '')),
            'quantita_kg'         => (float) ($movimento['peso_kg'] ?? 0),
            'data_movimento'      => Carbon::parse((string) ($movimento['data'] ?? now()))->toDateString(),
            'riferimento_interno' => isset($movimento['id']) ? (string) $movimento['id'] : null,
        ];
    }
}
