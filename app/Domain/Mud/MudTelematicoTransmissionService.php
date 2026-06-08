<?php

namespace App\Domain\Mud;

use App\Models\MudDichiarazione;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Adapter invio telematico MUD async (stub / HTTP gateway RENTRI-aligned).
 *
 * @see docs/SPRINT-95-AUDIT-NOTES.md
 * @see docs/SPRINT-101-AUDIT-NOTES.md
 */
class MudTelematicoTransmissionService
{
    public function __construct(
        private MudTelematicoRuntimeModeService $runtime,
        private MudTelematicoEndpoints $endpoints,
    ) {}

    /**
     * Submit + poll fino a esito (pattern async RENTRI).
     *
     * @return array{esito: string, protocollo: string, stub: bool, transazione_id: string, ricevuto_il: string, canale: string}
     */
    public function submitAndWait(MudDichiarazione $dichiarazione, string $xml): array
    {
        $submit = $this->submit($dichiarazione, $xml);
        $transazioneId = (string) ($submit['transazione_id'] ?? '');

        if ($transazioneId === '') {
            throw new MudTelematicoTransmissionException('Invio MUD: transazione_id mancante nella risposta submit.');
        }

        return $this->waitResult($transazioneId);
    }

    /**
     * @return array{transazione_id: string, stub?: bool}
     */
    public function submit(MudDichiarazione $dichiarazione, string $xml): array
    {
        if ($this->runtime->isStub()) {
            return $this->submitStub($dichiarazione, $xml);
        }

        return $this->submitLive($dichiarazione, $xml);
    }

    /**
     * @return array{esito: string, protocollo: string, stub: bool, transazione_id: string, ricevuto_il: string, canale: string}
     */
    public function waitResult(string $transazioneId): array
    {
        if ($this->runtime->isStub()) {
            return $this->waitResultStub($transazioneId);
        }

        return $this->waitResultLive($transazioneId);
    }

    /**
     * @return array{transazione_id: string, stub: true}
     */
    private function submitStub(MudDichiarazione $dichiarazione, string $xml): array
    {
        $transazioneId = (string) Str::uuid();

        Cache::put($this->stubCacheKey($transazioneId), [
            'anno_riferimento' => $dichiarazione->anno_riferimento,
            'xml_hash'         => sha1($xml),
            'dichiarazione_id' => $dichiarazione->id,
        ], now()->addMinutes(10));

        return [
            'transazione_id' => $transazioneId,
            'stub'           => true,
        ];
    }

    /**
     * @return array{esito: string, protocollo: string, stub: true, transazione_id: string, ricevuto_il: string, canale: string}
     */
    private function waitResultStub(string $transazioneId): array
    {
        /** @var array<string, mixed>|null $context */
        $context = Cache::get($this->stubCacheKey($transazioneId));

        if ($context === null) {
            throw new MudTelematicoTransmissionException('Transazione MUD stub non trovata.');
        }

        $anno = (int) ($context['anno_riferimento'] ?? date('Y'));
        $protocollo = sprintf('MUD-STUB-%d-%s', $anno, strtoupper(Str::random(8)));

        return [
            'esito'          => 'accettato',
            'stub'           => true,
            'protocollo'     => $protocollo,
            'transazione_id' => $transazioneId,
            'ricevuto_il'    => now()->toIso8601String(),
            'canale'         => 'ministero_stub',
        ];
    }

    /**
     * @return array{transazione_id: string}
     */
    private function submitLive(MudDichiarazione $dichiarazione, string $xml): array
    {
        $submitUrl = $this->endpoints->submitUrl();

        if ($this->endpoints->baseUrl() === '') {
            throw new MudTelematicoTransmissionException('MUD telematico: base URL non configurato (MUD_TELEMATICO_ENV / RENTRI_BASE_URL_*).');
        }

        $payload = $this->buildLiveSubmitPayload($dichiarazione, $xml);

        $response = Http::timeout((int) config('services.mud_telematico.timeout', 30))
            ->acceptJson()
            ->post($submitUrl, $payload);

        if (! $response->successful()) {
            throw new MudTelematicoTransmissionException(
                'Invio MUD rifiutato: '.($response->json('message') ?? $response->body()),
                $response->status(),
                $response->header('X-Correlation-Id'),
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];
        $transazioneId = (string) ($body['transazione_id'] ?? $body['transazioneId'] ?? '');

        if ($transazioneId === '') {
            throw new MudTelematicoTransmissionException('Invio MUD: transazione_id mancante nella risposta HTTP.');
        }

        return ['transazione_id' => $transazioneId];
    }

    /**
     * @return array{esito: string, protocollo: string, stub: false, transazione_id: string, ricevuto_il: string, canale: string}
     */
    private function waitResultLive(string $transazioneId): array
    {
        $maxAttempts = (int) config('services.mud_telematico.poll_max_attempts', 15);
        $intervalMs = (int) config('services.mud_telematico.poll_interval_ms', 200);
        $timeout = (int) config('services.mud_telematico.timeout', 30);

        $statusUrl = $this->endpoints->statusUrl($transazioneId);
        $resultUrl = $this->endpoints->resultUrl($transazioneId);
        $resultQuery = $this->endpoints->resultQuery($transazioneId);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $statusResponse = Http::timeout($timeout)->acceptJson()->get($statusUrl);

            if (! $statusResponse->successful()) {
                throw new MudTelematicoTransmissionException(
                    'Polling stato MUD fallito: '.$statusResponse->body(),
                    $statusResponse->status(),
                );
            }

            /** @var array<string, mixed> $statusBody */
            $statusBody = $statusResponse->json() ?? [];
            $stato = strtoupper((string) ($statusBody['stato'] ?? $statusBody['status'] ?? ''));

            if (in_array($stato, ['ERRORE', 'ERROR', 'FALLITA', 'FAILED', 'RIFIUTATA'], true)) {
                $detail = (string) ($statusBody['messaggio'] ?? $statusBody['message'] ?? 'Elaborazione MUD fallita.');

                throw new MudTelematicoTransmissionException('Invio MUD rifiutato: '.$detail);
            }

            if (in_array($stato, ['COMPLETATA', 'COMPLETED', 'OK', 'SUCCESS', 'ACCETTATA'], true)) {
                $resultResponse = Http::timeout($timeout)
                    ->acceptJson()
                    ->get($resultUrl, $resultQuery);

                if (! $resultResponse->successful()) {
                    throw new MudTelematicoTransmissionException(
                        'Recupero esito MUD fallito: '.$resultResponse->body(),
                        $resultResponse->status(),
                    );
                }

                /** @var array<string, mixed> $resultBody */
                $resultBody = $resultResponse->json() ?? [];
                $protocollo = (string) ($resultBody['protocollo'] ?? $resultBody['numero_protocollo'] ?? '');

                if ($protocollo === '') {
                    throw new MudTelematicoTransmissionException('Esito MUD senza protocollo ministeriale.');
                }

                return [
                    'esito'          => (string) ($resultBody['esito'] ?? 'accettato'),
                    'protocollo'     => $protocollo,
                    'stub'           => false,
                    'transazione_id' => $transazioneId,
                    'ricevuto_il'    => (string) ($resultBody['ricevuto_il'] ?? now()->toIso8601String()),
                    'canale'         => 'ministero_http',
                ];
            }

            usleep($intervalMs * 1000);
        }

        throw new MudTelematicoTransmissionException(
            sprintf('Timeout attesa esito invio MUD dopo %d tentativi.', $maxAttempts),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function buildLiveSubmitPayload(MudDichiarazione $dichiarazione, string $xml): array
    {
        $export = $dichiarazione->export_payload ?? [];

        $raw = [
            'anno_riferimento' => $dichiarazione->anno_riferimento,
            'xml'              => base64_encode($xml),
            'xml_encoding'     => 'base64',
            'schema_version'   => MudXmlValidationService::SCHEMA_VERSION,
            'dichiarazione_id' => $dichiarazione->id,
            'totali'           => $export['totali'] ?? null,
        ];

        return MudTelematicoTransmissionMapper::forTransmission($raw);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSubmitContext(MudDichiarazione $dichiarazione, string $xml): array
    {
        $export = $dichiarazione->export_payload ?? [];

        return [
            'mase'      => $this->buildLiveSubmitPayload($dichiarazione, $xml),
            'crm_audit' => MudTelematicoTransmissionMapper::crmAuditOnly([
                'dichiarazione_id' => $dichiarazione->id,
                'totali'           => $export['totali'] ?? null,
            ]),
        ];
    }

    private function stubCacheKey(string $transazioneId): string
    {
        return 'mud_telematico_stub:'.$transazioneId;
    }
}
