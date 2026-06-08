<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;
use App\Services\Rentri\Exceptions\RentriApiException;
use App\Support\Demo\DemoContext;

class RentriProductionCertValidationService
{
    public const STATUS_OK = 'ok';

    public const STATUS_FAIL = 'fail';

    public const STATUS_SKIP = 'skip';

    public const STATUS_INFO = 'info';

    public const STATUS_WARN = 'warn';

    public const PRODUCTION_HOST = 'api.rentri.gov.it';

    public const DEMOAPI_HOST = 'demoapi.rentri.gov.it';

    public const VALIDATION_DOC = 'docs/VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md';

    public const RUNBOOK_DOC = 'docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md';

    public function __construct(
        private readonly RentriProductionSwitchService $productionSwitch,
        private readonly RentriRuntimeModeService $runtimeMode,
        private readonly RentriCertificateServiceInterface $certificates,
        private readonly RentriFirmaCertificateServiceInterface $firmaCertificates,
    ) {}

    /**
     * @return array{
     *   overall: string,
     *   steps: list<array{key: string, label: string, status: string, message: string}>,
     *   codifiche_count: int|null,
     *   production_base_url: string,
     *   validation_doc: string,
     *   runbook_doc: string
     * }
     */
    public function run(RentriApiClientInterface $apiClient, ?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();
        $steps = $this->prerequisiteSteps($settings);
        $blocked = collect($steps)->contains(fn (array $s) => $s['status'] === self::STATUS_FAIL);

        if ($blocked) {
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_FAIL);
        }

        if (DemoContext::offlineNoHttp()) {
            $steps[] = $this->step(
                'offline_mode',
                'Chiamate HTTP MASE produzione',
                self::STATUS_SKIP,
                'RENTRI_DEMO_NO_HTTP=true — validazione live disabilitata in palestra offline.',
            );
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_WARN);
        }

        if ($this->runtimeMode->isApiStub($settings)) {
            $steps[] = $this->step(
                'live_calls',
                'Chiamate live api.rentri.gov.it',
                self::STATUS_SKIP,
                'API in stub — disabilitare RENTRI_API_STUB o attivare override live prima del test produzione.',
            );
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_WARN);
        }

        try {
            $resolvedHost = $this->resolvedApiHost($settings);
            if ($resolvedHost !== self::PRODUCTION_HOST) {
                $steps[] = $this->step(
                    'production_host',
                    'Host API produzione',
                    self::STATUS_FAIL,
                    sprintf(
                        'Host risolto «%s» — in modalità produzione è consentito solo %s.',
                        $resolvedHost,
                        self::PRODUCTION_HOST,
                    ),
                );
                $steps[] = $this->vidimaDryRunStep();

                return $this->result($steps, null, self::STATUS_FAIL);
            }

            $health = $apiClient->healthCheck();
            $healthOk = ($health['status'] ?? null) === 'ok';
            $steps[] = $this->step(
                'health',
                'Health check produzione (blocchi FIR)',
                $healthOk ? self::STATUS_OK : self::STATUS_FAIL,
                $healthOk
                    ? ($health['message'] ?? 'Connessione api.rentri.gov.it OK.')
                    : ('Health non OK: '.($health['message'] ?? 'risposta inattesa')),
            );

            $codifiche = $apiClient->fetchCodificheCer();
            $items = $codifiche['items'] ?? $codifiche['data'] ?? [];
            $count = is_array($items) ? count($items) : 0;
            $steps[] = $this->step(
                'codifiche_cer',
                'Codifiche CER (campione)',
                $count > 0 ? self::STATUS_OK : self::STATUS_WARN,
                $count > 0
                    ? sprintf('Recuperate %d voci codifiche CER da api.rentri.gov.it.', $count)
                    : 'Risposta codifiche CER senza voci — verificare permessi certificato produzione.',
            );

            $steps[] = $this->vidimaDryRunStep();

            $overall = collect($steps)->contains(fn (array $s) => $s['status'] === self::STATUS_FAIL)
                ? self::STATUS_FAIL
                : self::STATUS_OK;

            return $this->result($steps, $count, $overall);
        } catch (RentriApiException $e) {
            $steps[] = $this->step('health', 'Health check produzione (blocchi FIR)', self::STATUS_FAIL, $e->getMessage());
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_FAIL);
        }
    }

    /**
     * @return list<array{key: string, label: string, status: string, message: string}>
     */
    public function prerequisiteSteps(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();

        $mtlsOk = $this->certificates->validate($settings) && ! $this->certificates->isExpired($settings);
        $firmaOk = $this->firmaCertificates->validate($settings) && ! $this->firmaCertificates->isExpired($settings);

        $steps = [
            $this->step(
                'prereq_rentri_env',
                'RENTRI_ENV=production',
                $this->productionSwitch->isRentriEnvProduction() ? self::STATUS_OK : self::STATUS_FAIL,
                $this->productionSwitch->isRentriEnvProduction()
                    ? 'Ambiente .env impostato su produzione.'
                    : 'Impostare RENTRI_ENV=production in .env staging/prod.',
            ),
            $this->step(
                'prereq_ambiente_ui',
                'Wizard ambiente «Produzione»',
                ($settings->ambiente ?? '') === 'produzione' ? self::STATUS_OK : self::STATUS_FAIL,
                ($settings->ambiente ?? '') === 'produzione'
                    ? 'Dati operatore in modalità produzione.'
                    : 'Selezionare «Produzione» nello step 1 del wizard.',
            ),
            $this->step(
                'prereq_no_demoapi',
                'Solo api.rentri.gov.it (demoapi bloccato)',
                $this->isProductionHostOnly($settings) ? self::STATUS_OK : self::STATUS_FAIL,
                $this->demoapiBlockMessage($settings),
            ),
            $this->step(
                'prereq_cert_mtls',
                'Certificato mTLS produzione PKCS#12',
                $mtlsOk ? self::STATUS_OK : self::STATUS_FAIL,
                $mtlsOk
                    ? 'Certificato interoperabilità configurato e non scaduto.'
                    : 'Caricare certificato produzione nel wizard (step 2).',
            ),
            $this->step(
                'prereq_cert_firma',
                'Certificato firma xFIR produzione',
                $firmaOk ? self::STATUS_OK : self::STATUS_FAIL,
                $firmaOk
                    ? 'Certificato firma remota configurato e non scaduto.'
                    : 'Caricare certificato firma xFIR distinto da mTLS.',
            ),
            $this->step(
                'prereq_api_live',
                'API live verso MASE (non stub)',
                ! $this->runtimeMode->isApiStub($settings) ? self::STATUS_OK : self::STATUS_FAIL,
                ! $this->runtimeMode->isApiStub($settings)
                    ? 'Chiamate live verso '.$this->productionBaseUrl().'.'
                    : 'RENTRI_API_STUB=true — disabilitare stub o attivare override live (step 4).',
            ),
            $this->step(
                'prereq_firma_live',
                'Firma xFIR live (non stub)',
                ! $this->runtimeMode->isFirmaStub($settings) ? self::STATUS_OK : self::STATUS_FAIL,
                ! $this->runtimeMode->isFirmaStub($settings)
                    ? 'Firma COSE live abilitata.'
                    : 'RENTRI_FIRMA_STUB=false — abilitare firma live prima della vidima produzione.',
            ),
        ];

        return $steps;
    }

    public function productionBaseUrl(): string
    {
        return rtrim((string) config('services.rentri.base_url_production', 'https://api.rentri.gov.it'), '/');
    }

    public function validationDocPath(): string
    {
        return self::VALIDATION_DOC;
    }

    public function runbookDocPath(): string
    {
        return self::RUNBOOK_DOC;
    }

    public function isProductionHostOnly(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        if (! $this->productionSwitch->isRentriEnvProduction()) {
            return false;
        }

        if (($settings->ambiente ?? '') !== 'produzione') {
            return false;
        }

        $productionUrl = $this->productionBaseUrl();
        $productionHost = parse_url($productionUrl, PHP_URL_HOST);

        if ($productionHost !== self::PRODUCTION_HOST) {
            return false;
        }

        if (str_contains(strtolower($productionUrl), self::DEMOAPI_HOST)) {
            return false;
        }

        $sandboxUrl = rtrim((string) config('services.rentri.base_url_sandbox', ''), '/');
        if ($sandboxUrl !== '' && $this->resolvedApiHost($settings) === self::DEMOAPI_HOST) {
            return false;
        }

        return true;
    }

    private function resolvedApiHost(?RentriSetting $settings = null): string
    {
        $settings ??= RentriSetting::instance();

        if (DemoContext::forceSandboxApi()) {
            return self::DEMOAPI_HOST;
        }

        $url = ($settings->ambiente ?? '') === 'produzione'
            ? $this->productionBaseUrl()
            : rtrim((string) config('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it'), '/');

        return (string) parse_url($url, PHP_URL_HOST);
    }

    private function demoapiBlockMessage(?RentriSetting $settings = null): string
    {
        if ($this->isProductionHostOnly($settings)) {
            return 'Traffico consentito solo verso '.self::PRODUCTION_HOST.' — demoapi.rentri.gov.it bloccato in produzione.';
        }

        $host = $this->resolvedApiHost($settings);

        if ($host === self::DEMOAPI_HOST && $this->productionSwitch->isRentriEnvProduction()) {
            return 'RENTRI_ENV=production ma host risolto demoapi — correggere ambiente wizard e base URL produzione.';
        }

        return 'Configurare RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it e ambiente wizard «Produzione».';
    }

    /**
     * @param  list<array{key: string, label: string, status: string, message: string}>  $steps
     * @return array{
     *   overall: string,
     *   steps: list<array{key: string, label: string, status: string, message: string}>,
     *   codifiche_count: int|null,
     *   production_base_url: string,
     *   validation_doc: string,
     *   runbook_doc: string
     * }
     */
    private function result(array $steps, ?int $codificheCount, string $overall): array
    {
        return [
            'overall'             => $overall,
            'steps'               => $steps,
            'codifiche_count'     => $codificheCount,
            'production_base_url' => $this->productionBaseUrl(),
            'validation_doc'      => $this->validationDocPath(),
            'runbook_doc'         => $this->runbookDocPath(),
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, message: string}
     */
    private function step(string $key, string $label, string $status, string $message): array
    {
        return [
            'key'     => $key,
            'label'   => $label,
            'status'  => $status,
            'message' => $message,
        ];
    }

    /**
     * @return array{key: string, label: string, status: string, message: string}
     */
    private function vidimaDryRunStep(): array
    {
        return $this->step(
            'vidima_dry_run',
            'Vidimazione FIR produzione (dry-run)',
            self::STATUS_INFO,
            'Non eseguita automaticamente — seguire '.$this->validationDocPath().' §5 e '.$this->runbookDocPath().' prima della vidima reale.',
        );
    }
}
