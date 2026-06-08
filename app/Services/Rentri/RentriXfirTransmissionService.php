<?php

namespace App\Services\Rentri;

use App\Enums\FirStato;
use App\Models\Fir;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriXfirTransmissionServiceInterface;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RentriXfirTransmissionService implements RentriXfirTransmissionServiceInterface
{
    public function __construct(
        private RentriApiClientInterface $apiClient,
    ) {}

    public function transmit(Fir $fir): Fir
    {
        if (! $this->canTransmit($fir)) {
            if ($fir->xfir_trasmesso_at !== null) {
                throw new RuntimeException('Il payload xFIR firmato è già stato inviato a RENTRI.');
            }

            throw new RuntimeException('Il FIR deve essere firmato xFIR prima dell\'invio a RENTRI.');
        }

        /** @var array<string, mixed> $signedPayload */
        $signedPayload = json_decode($fir->xfir_signed_payload ?? '{}', true, 512, JSON_THROW_ON_ERROR);

        if ($signedPayload === []) {
            throw new RuntimeException('Payload xFIR firmato assente.');
        }

        $settings = RentriSetting::instance();
        $request = new RentriXfirTrasmissioneRequest($fir, $signedPayload, $settings);

        try {
            $submit = $this->apiClient->submitXfirFirmato($request);
            $transazioneId = (string) ($submit['transazione_id'] ?? $submit['transazioneId'] ?? '');

            if ($transazioneId === '') {
                throw new RentriApiException('RENTRI non ha restituito transazione_id per l\'invio xFIR.', 502);
            }

            $result = $this->apiClient->waitXfirTrasmissioneResult($transazioneId);
        } catch (RentriApiException $e) {
            throw new RuntimeException(RentriXfirTransmissionMessageMapper::fromException($e), $e->getCode(), $e);
        }

        $protocollo = (string) ($result['protocollo'] ?? $result['protocollo_rentri'] ?? '');

        return DB::transaction(function () use ($fir, $transazioneId, $protocollo): Fir {
            $fir->forceFill([
                'xfir_trasmesso_at'    => now(),
                'xfir_protocollo'      => $protocollo !== '' ? $protocollo : null,
                'xfir_transazione_id'  => $transazioneId,
                'stato'                => FirStato::Trasmesso,
            ])->save();

            return $fir->fresh();
        });
    }

    public function canTransmit(Fir $fir): bool
    {
        return $fir->stato === FirStato::Firmato
            && $fir->firmato_at !== null
            && filled($fir->xfir_signed_payload)
            && $fir->xfir_trasmesso_at === null;
    }
}
