<?php

namespace App\Services\Rentri;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Domain\Rentri\RentriTransazioneRetryService;
use App\Models\RentriSetting;
use App\Models\RentriTransazione;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Dto\RentriFirVidimaRequest;
use App\Services\Rentri\Dto\RentriRegistroTrasmissioneRequest;
use App\Services\Rentri\Dto\RentriXfirTrasmissioneRequest;
use App\Services\Rentri\Exceptions\RentriApiException;
use App\Support\Demo\DemoContext;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class RentriApiClient implements RentriApiClientInterface
{
    protected ?RentriSetting $settings = null;

    public function __construct(
        protected RentriCertificateServiceInterface $certificates,
    ) {}

    public function settings(): RentriSetting
    {
        return $this->settings ??= RentriSetting::instance();
    }

    public function request(string $method, string $endpoint, array $payload = []): array
    {
        return $this->execute($method, $endpoint, $payload);
    }

    public function healthCheck(): array
    {
        if ($this->usesStub()) {
            return $this->execute('GET', '/health', []);
        }

        return $this->executeLiveHealthCheck();
    }

    public function fetchCodificheCer(): array
    {
        return $this->request('GET', '/codifiche/cer', []);
    }

    public function fetchFirBlocchi(): array
    {
        if ($this->usesStub()) {
            return $this->execute('GET', '/fir/blocchi', []);
        }

        return $this->executePath(
            'GET',
            RentriEndpoints::FIR_VIDIMazione,
            RentriEndpoints::blocchiFirQuery($this->settings()),
            [],
            '/fir/blocchi',
            'fir_blocchi',
        );
    }

    public function submitFirVidima(RentriFirVidimaRequest $request): array
    {
        if ($this->usesStub()) {
            // Lo stub sintetizza l'esito vidima dal contesto CRM (codice_blocco); non va sul wire live.
            $stubContext = array_merge($request->body(), $request->crmAuditPayload());

            return $this->execute('POST', '/fir/vidima', $stubContext);
        }

        return $this->executePath(
            'POST',
            $request->livePath(),
            [],
            $request->body(),
            '/fir/vidima',
            'fir',
            requestMetadata: $this->vidimaRequestMetadata($request),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function vidimaRequestMetadata(RentriFirVidimaRequest $request): array
    {
        $crmAudit = $request->crmAuditPayload();

        return $crmAudit === [] ? [] : ['crm_audit' => $crmAudit];
    }

    public function waitFirVidimaResult(string $transazioneId): array
    {
        if ($this->usesStub()) {
            return $this->executePath('GET', '/fir/vidima/result', ['transazione_id' => $transazioneId], [], '/fir/vidima/result', 'fir');
        }

        $maxAttempts = (int) config('services.rentri.fir_poll_max_attempts', 15);
        $intervalMs = (int) config('services.rentri.fir_poll_interval_ms', 200);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->executePath(
                'GET',
                RentriEndpoints::firVidimaStatusPath($transazioneId),
                [],
                [],
                '/fir/vidima/status',
                'fir',
            );

            $stato = strtoupper((string) ($status['stato'] ?? $status['status'] ?? ''));

            if (in_array($stato, ['COMPLETATA', 'COMPLETED', 'OK', 'SUCCESS'], true)) {
                return $this->executePath(
                    'GET',
                    RentriEndpoints::firVidimaResultPath(),
                    RentriEndpoints::firVidimaResultQuery($transazioneId),
                    [],
                    '/fir/vidima/result',
                    'fir',
                );
            }

            if (in_array($stato, ['ERRORE', 'ERROR', 'FALLITA', 'FAILED'], true)) {
                $detail = (string) ($status['messaggio'] ?? $status['message'] ?? 'Elaborazione RENTRI fallita.');

                throw new RentriApiException('Vidimazione FIR rifiutata: '.$detail, 422);
            }

            usleep($intervalMs * 1000);
        }

        throw new RentriApiException(
            sprintf(
                'Timeout attesa esito vidimazione FIR RENTRI dopo %d tentativi (~%d s).',
                $maxAttempts,
                (int) ceil(($maxAttempts * $intervalMs) / 1000),
            ),
            408,
        );
    }

    public function submitRegistroTrasmissione(RentriRegistroTrasmissioneRequest $request): array
    {
        if ($this->usesStub()) {
            return $this->execute('POST', '/registro/trasmetti', $request->body());
        }

        return $this->executePath(
            'POST',
            $request->livePath(),
            [],
            $request->body(),
            '/registro/trasmetti',
            'registro',
        );
    }

    public function waitRegistroTrasmissioneResult(string $transazioneId): array
    {
        if ($this->usesStub()) {
            return $this->executePath(
                'GET',
                '/registro/trasmetti/result',
                ['transazione_id' => $transazioneId],
                [],
                '/registro/trasmetti/result',
                'registro',
            );
        }

        $maxAttempts = (int) config('services.rentri.registro_poll_max_attempts', 15);
        $intervalMs = (int) config('services.rentri.registro_poll_interval_ms', 200);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->executePath(
                'GET',
                RentriEndpoints::registroTrasmissioneStatusPath($transazioneId),
                [],
                [],
                '/registro/trasmetti/status',
                'registro',
            );

            $stato = strtoupper((string) ($status['stato'] ?? $status['status'] ?? ''));

            if (in_array($stato, ['COMPLETATA', 'COMPLETED', 'OK', 'SUCCESS', 'ACCETTATA'], true)) {
                return $this->executePath(
                    'GET',
                    RentriEndpoints::registroTrasmissioneResultPath(),
                    RentriEndpoints::registroTrasmissioneResultQuery($transazioneId),
                    [],
                    '/registro/trasmetti/result',
                    'registro',
                );
            }

            if (in_array($stato, ['ERRORE', 'ERROR', 'FALLITA', 'FAILED', 'RIFIUTATA'], true)) {
                $detail = (string) ($status['messaggio'] ?? $status['message'] ?? 'Elaborazione RENTRI fallita.');

                throw new RentriApiException('Trasmissione registro rifiutata: '.$detail, 422);
            }

            usleep($intervalMs * 1000);
        }

        throw new RentriApiException('Timeout attesa esito trasmissione registro RENTRI.', 408);
    }

    public function submitXfirFirmato(RentriXfirTrasmissioneRequest $request): array
    {
        if ($this->usesStub()) {
            return $this->execute('POST', '/xfir/trasmetti', $request->body());
        }

        return $this->executePath(
            'POST',
            $request->livePath(),
            [],
            $request->body(),
            '/xfir/trasmetti',
            'xfir',
        );
    }

    public function waitXfirTrasmissioneResult(string $transazioneId): array
    {
        if ($this->usesStub()) {
            return $this->executePath(
                'GET',
                '/xfir/trasmetti/result',
                ['transazione_id' => $transazioneId],
                [],
                '/xfir/trasmetti/result',
                'xfir',
            );
        }

        $maxAttempts = (int) config('services.rentri.xfir_poll_max_attempts', 20);
        $intervalMs = (int) config('services.rentri.xfir_poll_interval_ms', 300);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $status = $this->executePath(
                'GET',
                RentriEndpoints::xfirTrasmissioneStatusPath($transazioneId),
                [],
                [],
                '/xfir/trasmetti/status',
                'xfir',
            );

            $stato = strtoupper((string) ($status['stato'] ?? $status['status'] ?? ''));

            if (in_array($stato, ['COMPLETATA', 'COMPLETED', 'OK', 'SUCCESS', 'ACCETTATA'], true)) {
                return $this->executePath(
                    'GET',
                    RentriEndpoints::xfirTrasmissioneResultPath(),
                    RentriEndpoints::xfirTrasmissioneResultQuery($transazioneId),
                    [],
                    '/xfir/trasmetti/result',
                    'xfir',
                );
            }

            if (in_array($stato, ['ERRORE', 'ERROR', 'FALLITA', 'FAILED', 'RIFIUTATA'], true)) {
                $detail = (string) ($status['messaggio'] ?? $status['message'] ?? 'Elaborazione RENTRI fallita.');

                throw new RentriApiException('Invio xFIR firmato rifiutato: '.$detail, 422);
            }

            usleep($intervalMs * 1000);
        }

        throw new RentriApiException('Timeout attesa esito invio xFIR firmato RENTRI.', 408);
    }

    public function replayTransazione(RentriTransazione $transazione): array
    {
        /** @var array<string, mixed> $request */
        $request = $transazione->request_json ?? [];
        $method = (string) ($request['method'] ?? 'GET');
        $path = (string) ($request['endpoint'] ?? '/');
        $logicalKey = (string) ($request['logical_endpoint'] ?? $path);
        $payload = (array) ($request['payload'] ?? []);
        $query = strtoupper($method) === 'GET' ? $payload : [];
        $body = strtoupper($method) === 'POST' ? $payload : [];

        return $this->executePath(
            $method,
            $path,
            $query,
            $body,
            $logicalKey,
            $transazione->tipo_api,
            existingTransaction: $transazione,
            scheduleRetryOnFailure: false,
        );
    }

    public function trackTransaction(
        string $tipoApi,
        string $method,
        string $endpoint,
        array $payload = [],
        array $headers = [],
    ): RentriTransazione {
        return RentriTransazione::create([
            'transazione_id' => (string) Str::uuid(),
            'tipo_api'       => $tipoApi,
            'stato'          => 'in_corso',
            'request_json'   => [
                'method'            => $method,
                'endpoint'          => $endpoint,
                'logical_endpoint'  => $endpoint,
                'payload'           => $payload,
                'headers'           => $this->sanitizeHeadersForLog($headers),
            ],
        ]);
    }

    protected function baseUrl(): string
    {
        if (DemoContext::forceSandboxApi()) {
            return rtrim((string) config('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it'), '/');
        }

        return match ($this->settings()->ambiente) {
            'produzione' => rtrim((string) config('services.rentri.base_url_production', 'https://api.rentri.gov.it'), '/'),
            default      => rtrim((string) config('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it'), '/'),
        };
    }

    protected function usesStub(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        if (DemoContext::isSessionDemoActive()) {
            return blank($this->settings()->cert_path_encrypted);
        }

        return app(RentriRuntimeModeService::class)->isApiStub($this->settings());
    }

    protected function assertDemoSafeLiveCall(): void
    {
        if (! DemoContext::isActive()) {
            return;
        }

        $url = $this->baseUrl();

        $host = parse_url($url, PHP_URL_HOST);

        if ($host === 'api.rentri.gov.it') {
            throw new RuntimeException('Modalità demo: chiamate API produzione MASE (api.rentri.gov.it) bloccate.');
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function execute(string $method, string $endpoint, array $payload = []): array
    {
        if (! $this->usesStub()) {
            $livePath = RentriEndpoints::livePath($endpoint);
            $query = strtoupper($method) === 'GET' ? $payload : [];
            $body = strtoupper($method) === 'POST' ? $payload : [];

            return $this->executePath($method, $livePath, $query, $body, $endpoint, $this->resolveTipoApi($endpoint));
        }

        $query = strtoupper($method) === 'GET' ? $payload : [];
        $body = strtoupper($method) === 'POST' ? $payload : [];

        return $this->executePath($method, $endpoint, $query, $body, $endpoint, $this->resolveTipoApi($endpoint));
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function executePath(
        string $method,
        string $path,
        array $query,
        array $body,
        string $logicalKey,
        string $tipoApi,
        ?RentriTransazione $existingTransaction = null,
        bool $scheduleRetryOnFailure = true,
        array $requestMetadata = [],
    ): array {
        $settings = $this->settings();

        if (! $this->usesStub()) {
            $this->assertDemoSafeLiveCall();

            if (blank($settings->cert_path_encrypted)) {
                throw new RuntimeException('Certificato RENTRI non configurato.');
            }

            if ($this->certificates->isExpired($settings)) {
                throw new RuntimeException('Certificato RENTRI scaduto.');
            }
        } elseif ($this->certificates->validate($settings) && $this->certificates->isExpired($settings)) {
            throw new RuntimeException('Certificato RENTRI scaduto.');
        }

        $signPayload = $body !== [] ? $body : $query;
        $headers = $this->certificates->signRequestForMode($settings, $method, $logicalKey, $signPayload, $this->usesStub());
        $transaction = $existingTransaction ?? $this->trackTransaction($tipoApi, $method, $path, array_merge($query, $body), $headers);

        $requestJson = [
            'method'           => $method,
            'endpoint'         => $path,
            'logical_endpoint' => $logicalKey,
            'payload'          => array_merge($query, $body),
            'headers'          => $this->sanitizeHeadersForLog($headers),
        ];

        if ($requestMetadata !== []) {
            $requestJson = array_merge($requestJson, $requestMetadata);
        }

        if ($existingTransaction !== null) {
            $transaction->update(['request_json' => $requestJson]);
        } else {
            $transaction->update([
                'request_json' => array_merge($transaction->request_json ?? [], $requestJson),
            ]);
        }

        $startedAt = microtime(true);

        try {
            $response = $this->usesStub()
                ? $this->stubPathResponse($method, $logicalKey, $query, $body, $settings)
                : $this->liveHttp($method, $path, $query, $body, $headers, $settings);

            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $transaction->update([
                'stato'           => 'completata',
                'response_json'   => $response,
                'completed_at'    => now(),
                'next_retry_at'   => null,
                'dead_letter_at'  => null,
            ]);

            app(\App\Support\Logging\StructuredLogService::class)->info(
                'rentri',
                'api_call',
                'Chiamata RENTRI completata',
                [
                    'entity_type' => 'rentri_transazione',
                    'entity_id'   => $transaction->id,
                    'outcome'     => 'success',
                    'duration_ms' => $durationMs,
                    'context'     => [
                        'method'          => strtoupper($method),
                        'endpoint'        => $logicalKey,
                        'tipo_api'        => $tipoApi,
                        'correlation_id'  => is_array($response)
                            ? ($response['correlation_id'] ?? null)
                            : null,
                    ],
                ],
            );

            return $response;
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $transaction->update([
                'stato'         => 'errore',
                'response_json' => [
                    'error'          => $e->getMessage(),
                    'correlation_id' => $e instanceof RentriApiException ? $e->correlationId : null,
                ],
                'completed_at'  => now(),
            ]);

            app(\App\Support\Logging\StructuredLogService::class)->error(
                'rentri',
                'api_call',
                'Chiamata RENTRI fallita',
                [
                    'entity_type' => 'rentri_transazione',
                    'entity_id'   => $transaction->id,
                    'outcome'     => 'failure',
                    'duration_ms' => $durationMs,
                    'context'     => [
                        'method'         => strtoupper($method),
                        'endpoint'       => $logicalKey,
                        'tipo_api'       => $tipoApi,
                        'error'          => $e->getMessage(),
                        'correlation_id' => $e instanceof RentriApiException ? $e->correlationId : null,
                    ],
                ],
            );

            if ($scheduleRetryOnFailure) {
                $retryService = app(RentriTransazioneRetryService::class);
                if ($retryService->shouldScheduleRetry($transaction->fresh(), $e)) {
                    $retryService->scheduleRetry($transaction->fresh());
                }
            }

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function executeLiveHealthCheck(): array
    {
        $settings = $this->settings();
        $query = RentriEndpoints::blocchiFirQuery($settings);

        try {
            $response = $this->executePath(
                'GET',
                RentriEndpoints::FIR_VIDIMazione,
                $query,
                [],
                '/health',
                'health',
            );

            return [
                'status'          => 'ok',
                'message'         => 'Connessione RENTRI verificata (API live).',
                'ambiente'        => $settings->ambiente,
                'num_iscr_sito'   => $settings->num_iscr_sito,
                'api_mode'        => 'live',
                'endpoint'        => RentriEndpoints::FIR_VIDIMazione,
                'blocchi_sample'  => count($response['items'] ?? $response['data'] ?? []),
                'correlation_id'  => $this->extractCorrelationId($response),
                'checked_at'      => now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    protected function http(RentriSetting $settings): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl())
            ->timeout((int) config('services.rentri.timeout', 30))
            ->acceptJson();

        $options = $this->certificates->httpClientOptions($settings);

        if ($options !== []) {
            $client = $client->withOptions($options);
        }

        return $client;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     * @return array<string, mixed>
     */
    protected function liveHttp(
        string $method,
        string $path,
        array $query,
        array $body,
        array $headers,
        RentriSetting $settings,
    ): array {
        $this->assertDemoSafeLiveCall();

        $client = $this->http($settings)->withHeaders($headers);

        $response = match (strtoupper($method)) {
            'GET'  => $query !== [] ? $client->get($path, $query) : $client->get($path),
            'POST' => $client->post($path, $body),
            default => throw new RuntimeException("Metodo HTTP non supportato: {$method}"),
        };

        return $this->parseLiveResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseLiveResponse(Response $response): array
    {
        if ($response->successful()) {
            $json = $response->json();

            if (is_array($json)) {
                $json['correlation_id'] = $this->extractCorrelationId($json)
                    ?? $response->header('X-Correlation-Id')
                    ?? $response->header('X-Request-Id');

                return $json;
            }

            return ['raw' => $response->body()];
        }

        throw $this->buildApiException($response);
    }

    protected function buildApiException(Response $response): RentriApiException
    {
        $json = $response->json();
        $detail = is_array($json)
            ? ($json['message'] ?? $json['detail'] ?? $json['title'] ?? null)
            : null;
        $correlationId = is_array($json) ? ($json['correlation_id'] ?? $json['correlationId'] ?? null) : null;
        $correlationId ??= $response->header('X-Correlation-Id') ?? $response->header('X-Request-Id');

        $message = $this->translateHttpError($response->status(), (string) ($detail ?: $response->body()));

        return new RentriApiException($message, $response->status(), $correlationId);
    }

    protected function translateHttpError(int $status, string $detail): string
    {
        $base = match (true) {
            $status === 401 => 'Autenticazione RENTRI fallita — verificare certificato interoperabilità.',
            $status === 403 => 'Accesso negato — operatore non autorizzato su questo sito RENTRI.',
            $status === 404 => 'Risorsa RENTRI non trovata.',
            $status === 422 => 'Dati non validi per RENTRI.',
            $status >= 500  => 'Servizio RENTRI temporaneamente non disponibile.',
            default         => 'Errore API RENTRI.',
        };

        $detail = Str::limit(trim($detail), 200);

        return $detail !== '' ? $base.' '.$detail : $base;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function extractCorrelationId(array $response): ?string
    {
        foreach (['correlation_id', 'correlationId', 'transazione_id', 'transazioneId'] as $key) {
            if (! empty($response[$key])) {
                return (string) $response[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $query
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function stubPathResponse(string $method, string $logicalKey, array $query, array $body, RentriSetting $settings): array
    {
        if ($method === 'POST' && $logicalKey === '/fir/vidima') {
            $transazioneId = (string) Str::uuid();
            Cache::put($this->stubVidimaCacheKey($transazioneId), $body, now()->addMinutes(5));

            return [
                'transazione_id' => $transazioneId,
                'stub'           => true,
            ];
        }

        if ($method === 'GET' && $logicalKey === '/fir/vidima/status') {
            return ['stato' => 'COMPLETATA', 'stub' => true];
        }

        if ($method === 'GET' && $logicalKey === '/fir/vidima/result') {
            return $this->stubFirVidimaResult((string) ($query['transazione_id'] ?? ''));
        }

        if ($method === 'POST' && $logicalKey === '/registro/trasmetti') {
            $transazioneId = (string) Str::uuid();
            Cache::put($this->stubRegistroCacheKey($transazioneId), $body, now()->addMinutes(5));

            return [
                'transazione_id' => $transazioneId,
                'stub'           => true,
            ];
        }

        if ($method === 'GET' && $logicalKey === '/registro/trasmetti/status') {
            return ['stato' => 'COMPLETATA', 'stub' => true];
        }

        if ($method === 'GET' && $logicalKey === '/registro/trasmetti/result') {
            return $this->stubRegistroTrasmissioneResult((string) ($query['transazione_id'] ?? ''));
        }

        if ($method === 'POST' && $logicalKey === '/xfir/trasmetti') {
            $transazioneId = (string) Str::uuid();
            Cache::put($this->stubXfirCacheKey($transazioneId), $body, now()->addMinutes(5));

            return [
                'transazione_id' => $transazioneId,
                'stub'           => true,
            ];
        }

        if ($method === 'GET' && $logicalKey === '/xfir/trasmetti/status') {
            return ['stato' => 'COMPLETATA', 'stub' => true];
        }

        if ($method === 'GET' && $logicalKey === '/xfir/trasmetti/result') {
            return $this->stubXfirTrasmissioneResult((string) ($query['transazione_id'] ?? ''));
        }

        if ($method === 'GET' && $logicalKey === '/fir/blocchi') {
            return [
                'items' => [
                    [
                        'codice_blocco' => 'BLK-STUB-01',
                        'num_iscr_sito' => $settings->num_iscr_sito ?? 'SITE-TEST',
                    ],
                ],
                'stub' => true,
            ];
        }

        return $this->stubResponse($method, $logicalKey, array_merge($query, $body), $settings);
    }

    /**
     * @return array<string, mixed>
     */
    protected function stubFirVidimaResult(string $transazioneId): array
    {
        /** @var array<string, mixed>|null $context */
        $context = Cache::get($this->stubVidimaCacheKey($transazioneId));

        if ($context === null) {
            throw new RuntimeException('Transazione vidima stub non trovata.');
        }

        $codiceBlocco = (string) ($context['codice_blocco'] ?? 'BLK-A');
        $numIscrSito = (string) ($context['num_iscr_sito'] ?? 'SITE-TEST');
        $progressivo = (int) ($context['progressivo'] ?? 1);
        $numeroFir = sprintf('%s-%s-%04d', $numIscrSito, $codiceBlocco, $progressivo);

        return [
            'codice_blocco'  => $codiceBlocco,
            'progressivo'    => $progressivo,
            'numero_fir'     => $numeroFir,
            'protocollo'     => 'FIR-'.strtoupper(Str::random(8)),
            'qr_code'        => 'STUB-QR-'.$numeroFir,
            'transazione_id' => $transazioneId,
            'stub'           => true,
        ];
    }

    protected function stubVidimaCacheKey(string $transazioneId): string
    {
        return 'rentri_stub_vidima:'.$transazioneId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function stubRegistroTrasmissioneResult(string $transazioneId): array
    {
        /** @var array<string, mixed>|null $context */
        $context = Cache::get($this->stubRegistroCacheKey($transazioneId));

        if ($context === null) {
            throw new RuntimeException('Transazione registro stub non trovata.');
        }

        $movimentiCount = count($context['movimenti'] ?? []);

        return [
            'esito'          => 'accettato',
            'protocollo'     => 'REG-'.strtoupper(Str::random(8)),
            'movimenti'      => $movimentiCount,
            'transazione_id' => $transazioneId,
            'stub'           => true,
        ];
    }

    protected function stubRegistroCacheKey(string $transazioneId): string
    {
        return 'rentri_stub_registro:'.$transazioneId;
    }

    /**
     * @return array<string, mixed>
     */
    protected function stubXfirTrasmissioneResult(string $transazioneId): array
    {
        /** @var array<string, mixed>|null $context */
        $context = Cache::get($this->stubXfirCacheKey($transazioneId));

        if ($context === null) {
            throw new RuntimeException('Transazione xFIR stub non trovata.');
        }

        $numeroFir = (string) ($context['numero_fir'] ?? 'FIR-STUB');

        return [
            'esito'          => 'accettato',
            'protocollo'     => 'XFIR-'.strtoupper(Str::random(8)),
            'numero_fir'     => $numeroFir,
            'transazione_id' => $transazioneId,
            'stub'           => true,
        ];
    }

    protected function stubXfirCacheKey(string $transazioneId): string
    {
        return 'rentri_stub_xfir:'.$transazioneId;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function stubResponse(string $method, string $endpoint, array $payload, RentriSetting $settings): array
    {
        return match (true) {
            $method === 'GET' && $endpoint === '/health' => [
                'status'        => 'ok',
                'ambiente'      => $settings->ambiente,
                'num_iscr_sito' => $settings->num_iscr_sito,
                'message'       => 'Connessione RENTRI verificata (stub).',
                'api_mode'      => 'stub',
                'checked_at'    => now()->toIso8601String(),
                'stub'          => true,
            ],
            $method === 'GET' && $endpoint === '/codifiche/cer' => $this->codificheCerStub(),
            default => ['stub' => true],
        };
    }

    protected function resolveTipoApi(string $endpoint): string
    {
        return match ($endpoint) {
            '/registro/trasmetti', '/registro/trasmetti/status', '/registro/trasmetti/result' => 'registro',
            '/fir/vidima', '/fir/vidima/status', '/fir/vidima/result' => 'fir',
            '/xfir/trasmetti', '/xfir/trasmetti/status', '/xfir/trasmetti/result' => 'xfir',
            '/health', '/fir/blocchi' => 'health',
            '/codifiche/cer' => 'codifiche',
            default => 'generic',
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function codificheCerStub(): array
    {
        $path = database_path('fixtures/rentri_codifiche_cer.json');

        if (! is_readable($path)) {
            return ['items' => [], 'stub' => true];
        }

        /** @var array<string, mixed> $data */
        $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return array_merge($data, ['stub' => true]);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    protected function sanitizeHeadersForLog(array $headers): array
    {
        $logged = $headers;

        if (isset($logged['X-RENTRI-Signature'])) {
            $logged['X-RENTRI-Signature'] = Str::limit($logged['X-RENTRI-Signature'], 24, '…');
        }

        return $logged;
    }
}
