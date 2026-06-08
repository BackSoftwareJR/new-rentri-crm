<?php

namespace App\Services\Rentri;

use App\Domain\Rentri\RentriRegistroConformitaValidator;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Domain\Audit\ActivityLogService;
use App\Models\RegistroMovimento;
use App\Models\RentriSetting;
use App\Models\RentriTransmissione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\TransmissionPayload;
use App\Services\Rentri\Exceptions\RentriApiException;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RentriRegistryService implements RentriRegistryServiceInterface
{
    /** @var list<string> */
    private const ESITI_SUCCESSO = ['accettato', 'ok', 'completata', 'completato'];

    public function __construct(
        protected RentriApiClientInterface $apiClient,
        protected RentriRegistroConformitaValidator $conformitaValidator,
    ) {}

    public function buildTransmissionPayload(CarbonInterface $periodoDa, CarbonInterface $periodoA): TransmissionPayload
    {
        $movimenti = $this->pendingMovimentiQuery($periodoDa, $periodoA)
            ->with('codiceCer')
            ->orderBy('data_movimento')
            ->get()
            ->map(fn (RegistroMovimento $m) => [
                'id'          => $m->id,
                'tipo'        => $m->tipo->value,
                'codice_cer'  => $m->codiceCer->codice,
                'peso_kg'     => (float) $m->peso_kg,
                'data'        => $m->data_movimento->toIso8601String(),
                'source_type' => $m->source_type,
                'source_id'   => $m->source_id,
            ])
            ->values()
            ->all();

        $body = [
            'periodo_da' => $periodoDa->toDateString(),
            'periodo_a'  => $periodoA->toDateString(),
            'movimenti'  => $movimenti,
        ];

        return new TransmissionPayload(
            periodoDa: $periodoDa,
            periodoA: $periodoA,
            payloadHash: hash('sha256', json_encode($body, JSON_THROW_ON_ERROR)),
            movimenti: $movimenti,
            metadata: ['count' => count($movimenti)],
        );
    }

    public function transmit(TransmissionPayload $payload): RentriTransmissione
    {
        if ($payload->metadata['count'] === 0) {
            throw new \InvalidArgumentException('Nessun movimento da trasmettere nel periodo selezionato.');
        }

        $settings = RentriSetting::instance();
        $this->conformitaValidator->assertConforme($payload, $settings);

        $transmissione = DB::transaction(function () use ($payload, $settings) {
            $movimentiIds = $this->pendingMovimentiQuery($payload->periodoDa, $payload->periodoA)
                ->pluck('id');

            $transmissione = RentriTransmissione::create([
                'periodo_da'   => $payload->periodoDa,
                'periodo_a'    => $payload->periodoA,
                'payload_hash' => $payload->payloadHash,
                'esito'        => 'in_attesa',
            ]);

            $request = RentriRegistroTrasmissioneRequest::fromPayload($payload, $settings);

            try {
                $submit = $this->apiClient->submitRegistroTrasmissione($request);
                $transazioneId = (string) ($submit['transazione_id'] ?? $submit['transazioneId'] ?? '');

                if ($transazioneId === '') {
                    throw new RentriApiException('RENTRI non ha restituito transazione_id per la trasmissione registro.', 502);
                }

                $result = $this->apiClient->waitRegistroTrasmissioneResult($transazioneId);
            } catch (RentriApiException $e) {
                $transmissione->update([
                    'esito'         => 'errore',
                    'trasmesso_at'  => now(),
                    'response_json' => ['error' => $e->getMessage()],
                ]);

                throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            $response = array_merge($result, [
                'transazione_id' => $transazioneId,
                'api_mode'       => app(RentriRuntimeModeService::class)->apiModeLabel(RentriSetting::instance()),
            ]);

            $esito = (string) ($response['esito'] ?? 'accettato');

            $transmissione->update([
                'esito'         => $esito,
                'trasmesso_at'  => now(),
                'response_json' => $response,
            ]);

            if ($this->isEsitoSuccesso($esito)) {
                $this->lockMovimenti($movimentiIds, $transmissione->id);
            }

            return $transmissione->fresh();
        });

        app(ActivityLogService::class)->record(
            'rentri',
            'Trasmissione registro RENTRI completata',
            $transmissione,
            [
                'movimenti_count' => $payload->metadata['count'],
                'periodo_da'      => $payload->periodoDa->toDateString(),
                'periodo_a'         => $payload->periodoA->toDateString(),
                'protocollo'        => $transmissione->response_json['protocollo'] ?? null,
            ],
        );

        return $transmissione;
    }

    /**
     * @param  Collection<int, int>|list<int>  $movimentiIds
     */
    private function lockMovimenti(Collection|array $movimentiIds, int $transmissioneId): void
    {
        RegistroMovimento::query()
            ->whereIn('id', $movimentiIds)
            ->where('rentri_trasmesso', false)
            ->whereNull('locked_at')
            ->update([
                'rentri_trasmesso'       => true,
                'rentri_transmission_id' => $transmissioneId,
                'locked_at'              => now(),
            ]);
    }

    private function isEsitoSuccesso(string $esito): bool
    {
        return in_array(strtolower($esito), self::ESITI_SUCCESSO, true);
    }

    private function pendingMovimentiQuery(CarbonInterface $periodoDa, CarbonInterface $periodoA): Builder
    {
        return RegistroMovimento::query()
            ->forActiveSito()
            ->where('rentri_trasmesso', false)
            ->whereNull('locked_at')
            ->whereBetween('data_movimento', [
                $periodoDa->copy()->startOfDay(),
                $periodoA->copy()->endOfDay(),
            ]);
    }
}
