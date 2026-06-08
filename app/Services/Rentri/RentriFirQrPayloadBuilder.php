<?php

namespace App\Services\Rentri;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;

class RentriFirQrPayloadBuilder
{
    public function __construct(
        private RentriRuntimeModeService $runtimeMode,
    ) {}

    /**
     * @param  array<string, mixed>  $result
     * @return array<string, mixed>
     */
    public function build(
        array $result,
        string $numeroFir,
        string $codiceBlocco,
        int $progressivo,
        string $numIscrSito,
        string $transazioneId,
        ?RentriSetting $settings = null,
    ): array {
        $settings ??= RentriSetting::instance();

        return [
            'versione'       => '1.0',
            'api_mode'       => $this->runtimeMode->apiModeLabel($settings),
            'numero_fir'     => $numeroFir,
            'codice_blocco'  => $codiceBlocco,
            'progressivo'    => $progressivo,
            'num_iscr_sito'  => $numIscrSito,
            'protocollo'     => $result['protocollo'] ?? $result['protocollo_rentri'] ?? null,
            'transazione_id' => $transazioneId,
            'correlation_id' => $result['correlation_id'] ?? null,
            'qr_code'        => $result['qr_code'] ?? $result['qr_code_bytes'] ?? null,
            'data_vidimazione' => now()->toDateString(),
        ];
    }
}
