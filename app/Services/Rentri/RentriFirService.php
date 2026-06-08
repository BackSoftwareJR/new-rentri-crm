<?php

namespace App\Services\Rentri;

use App\Domain\Fir\FirBloccoService;
use App\Domain\Rentri\RentriFirVidimaValidator;
use App\Enums\FirStato;
use App\Enums\TrasportoStato;
use App\Models\Fir;
use App\Models\FirBlocco;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Support\Facades\DB;

class RentriFirService implements RentriFirServiceInterface
{
    public function __construct(
        protected RentriApiClientInterface $apiClient,
        protected FirBloccoService $blocchi,
        protected RentriFirQrPayloadBuilder $qrPayloadBuilder,
        protected RentriFirQrPayloadValidator $qrPayloadValidator,
        protected RentriFirVidimaValidator $vidimaValidator,
    ) {}

    public function vidima(Trasporto $trasporto): Fir
    {
        if ($trasporto->fir_id !== null) {
            throw new \RuntimeException('Il trasporto ha già un FIR vidimato.');
        }

        if (! in_array($trasporto->stato, [TrasportoStato::InPreparazione, TrasportoStato::InTransito], true)) {
            throw new \RuntimeException('Il FIR può essere vidimato solo per trasporti in preparazione o in transito.');
        }

        $settings = RentriSetting::instance();
        $this->vidimaValidator->assertReady($settings);

        $numIscrSito = $settings->num_iscr_sito ?? '';

        return DB::transaction(function () use ($trasporto, $numIscrSito) {
            $blocco = FirBlocco::query()
                ->when($numIscrSito !== '', fn ($q) => $q->where('num_iscr_sito', $numIscrSito))
                ->lockForUpdate()
                ->orderBy('id')
                ->first();

            if ($blocco === null) {
                throw new \RuntimeException('Blocco FIR non configurato per il sito.');
            }

            $this->blocchi->assertDisponibilePerVidima($blocco);

            $progressivo = $this->nextProgressivo($blocco->codice_blocco, $blocco->num_iscr_sito);

            if ($progressivo > FirBlocco::progressivoMax()) {
                throw new \RuntimeException(sprintf(
                    'Blocco FIR «%s» esaurito: progressivo massimo %d raggiunto.',
                    $blocco->codice_blocco,
                    FirBlocco::progressivoMax(),
                ));
            }

            $request = new RentriFirVidimaRequest(
                codiceBlocco: $blocco->codice_blocco,
                numIscrSito: $blocco->num_iscr_sito,
                payload: [
                    'trasporto_id'  => $trasporto->id,
                    'progressivo'   => $progressivo,
                    'codice_blocco' => $blocco->codice_blocco,
                    'num_iscr_sito' => $blocco->num_iscr_sito,
                ],
            );

            try {
                $submit = $this->apiClient->submitFirVidima($request);
                $transazioneId = (string) ($submit['transazione_id'] ?? $submit['transazioneId'] ?? '');

                if ($transazioneId === '') {
                    throw new RentriApiException('RENTRI non ha restituito transazione_id per la vidimazione.', 502);
                }

                $result = $this->apiClient->waitFirVidimaResult($transazioneId);
            } catch (RentriApiException $e) {
                throw new \RuntimeException(RentriFirVidimaMessageMapper::fromException($e), $e->getCode(), $e);
            }

            $progressivo = (int) ($result['progressivo'] ?? $progressivo);
            $numeroFir = (string) ($result['numero_fir'] ?? $result['numeroFir'] ?? sprintf(
                '%s-%s-%04d',
                $blocco->num_iscr_sito,
                $blocco->codice_blocco,
                $progressivo,
            ));

            $qrPayload = $this->qrPayloadBuilder->build(
                $result,
                $numeroFir,
                $blocco->codice_blocco,
                $progressivo,
                $blocco->num_iscr_sito,
                $transazioneId,
            );

            $this->qrPayloadValidator->validate($qrPayload);

            $fir = Fir::create([
                'numero_fir'       => $numeroFir,
                'codice_blocco'    => $blocco->codice_blocco,
                'progressivo'      => $progressivo,
                'stato'            => FirStato::Vidimato,
                'vidimato_at'      => now(),
                'trasporto_id'     => $trasporto->id,
                'peso_partenza_kg' => $trasporto->quantita_kg,
                'qr_payload'       => json_encode($qrPayload, JSON_THROW_ON_ERROR),
            ]);

            $trasporto->fir_id = $fir->id;
            $trasporto->save();
            $blocco->update(['progressivo_ultimo' => $progressivo]);

            return $fir;
        });
    }

    public function nextProgressivo(string $codiceBlocco, string $numIscrSito): int
    {
        $blocco = FirBlocco::query()
            ->where('codice_blocco', $codiceBlocco)
            ->where('num_iscr_sito', $numIscrSito)
            ->first();

        return ($blocco?->progressivo_ultimo ?? 0) + 1;
    }
}
