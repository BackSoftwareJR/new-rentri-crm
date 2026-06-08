<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Exceptions\RentriApiException;
use App\Support\Demo\DemoContext;

class RentriSandboxValidationService
{
    public const STATUS_OK = 'ok';

    public const STATUS_FAIL = 'fail';

    public const STATUS_SKIP = 'skip';

    public const STATUS_INFO = 'info';

    public const STATUS_WARN = 'warn';

    public function __construct(
        private readonly RentriRuntimeModeService $runtimeMode,
        private readonly RentriCertificateServiceInterface $certificates,
        private readonly RentriFirVidimaValidator $vidimaValidator,
    ) {}

    /**
     * @return array{
     *   overall: string,
     *   steps: list<array{key: string, label: string, status: string, message: string}>,
     *   codifiche_count: int|null,
     *   sandbox_base_url: string,
     *   demoapi_docs_url: string,
     *   vidima_dry_run_doc: string
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
                'Chiamate HTTP MASE',
                self::STATUS_SKIP,
                'RENTRI_DEMO_NO_HTTP=true — validazione live disabilitata in palestra offline.',
            );
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_WARN);
        }

        if ($this->runtimeMode->isApiStub($settings)) {
            $steps[] = $this->step(
                'live_calls',
                'Chiamate live demoapi',
                self::STATUS_SKIP,
                'API in stub — disabilitare stub o attivare live da wizard per test reale MASE.',
            );
            $steps[] = $this->vidimaDryRunStep();

            return $this->result($steps, null, self::STATUS_WARN);
        }

        try {
            $health = $apiClient->healthCheck();
            $healthOk = ($health['status'] ?? null) === 'ok';
            $steps[] = $this->step(
                'health',
                'Health check (blocchi FIR)',
                $healthOk ? self::STATUS_OK : self::STATUS_FAIL,
                $healthOk
                    ? ($health['message'] ?? 'Connessione demoapi OK.')
                    : ('Health non OK: '.($health['message'] ?? 'risposta inattesa')),
            );

            $codifiche = $apiClient->fetchCodificheCer();
            $items = $codifiche['items'] ?? $codifiche['data'] ?? [];
            $count = is_array($items) ? count($items) : 0;
            $steps[] = $this->step(
                'codifiche_cer',
                'Codifiche CER',
                $count > 0 ? self::STATUS_OK : self::STATUS_WARN,
                $count > 0
                    ? sprintf('Recuperate %d voci codifiche CER da demoapi.', $count)
                    : 'Risposta codifiche CER senza voci — verificare permessi certificato sandbox.',
            );

            $steps[] = $this->vidimaDryRunStep();

            $overall = collect($steps)->contains(fn (array $s) => $s['status'] === self::STATUS_FAIL)
                ? self::STATUS_FAIL
                : self::STATUS_OK;

            return $this->result($steps, $count, $overall);
        } catch (RentriApiException $e) {
            $steps[] = $this->step('health', 'Health check (blocchi FIR)', self::STATUS_FAIL, $e->getMessage());
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

        $certOk = $this->certificates->validate($settings) && ! $this->certificates->isExpired($settings);
        $steps = [
            $this->step(
                'prereq_cert',
                'Certificato mTLS sandbox PKCS#12',
                $certOk ? self::STATUS_OK : self::STATUS_FAIL,
                $certOk
                    ? 'Certificato interoperabilità configurato e non scaduto.'
                    : 'Caricare certificato sandbox nel wizard (step 2) o impostare RENTRI_SANDBOX_CERT_PATH.',
            ),
        ];

        foreach ($this->vidimaValidator->checklist($settings) as $item) {
            if ($item['codice'] === 'certificato_mtls') {
                continue;
            }

            $steps[] = $this->step(
                'prereq_'.$item['codice'],
                $item['label'],
                $item['ok'] ? self::STATUS_OK : self::STATUS_FAIL,
                $item['ok'] ? 'OK' : ($item['message'] ?? $item['label']),
            );
        }

        $liveReady = ! $this->runtimeMode->isApiStub($settings) || $settings->live_mode_enabled_at !== null;
        $steps[] = $this->step(
            'prereq_api_mode',
            'Modalità API live verso demoapi',
            $liveReady && ! $this->runtimeMode->isApiStub($settings)
                ? self::STATUS_OK
                : ($this->runtimeMode->isApiStub($settings) ? self::STATUS_FAIL : self::STATUS_OK),
            $this->runtimeMode->isApiStub($settings)
                ? 'RENTRI_API_STUB=true o override non attivo — abilitare live per test reale.'
                : 'Chiamate live verso '.config('services.rentri.base_url_sandbox', 'demoapi.rentri.gov.it').'.',
        );

        return $steps;
    }

    public function sandboxBaseUrl(): string
    {
        return (string) config('services.rentri.base_url_sandbox', 'https://demoapi.rentri.gov.it');
    }

    public function demoapiDocsUrl(): string
    {
        return $this->sandboxBaseUrl().'/docs';
    }

    public function vidimaDryRunDocPath(): string
    {
        return 'docs/VALIDAZIONE-SANDBOX-MASE.md';
    }

    /**
     * @param  list<array{key: string, label: string, status: string, message: string}>  $steps
     * @return array{
     *   overall: string,
     *   steps: list<array{key: string, label: string, status: string, message: string}>,
     *   codifiche_count: int|null,
     *   sandbox_base_url: string,
     *   demoapi_docs_url: string,
     *   vidima_dry_run_doc: string
     * }
     */
    private function result(array $steps, ?int $codificheCount, string $overall): array
    {
        return [
            'overall'          => $overall,
            'steps'            => $steps,
            'codifiche_count'  => $codificheCount,
            'sandbox_base_url' => $this->sandboxBaseUrl(),
            'demoapi_docs_url' => $this->demoapiDocsUrl(),
            'vidima_dry_run_doc' => $this->vidimaDryRunDocPath(),
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
            'Vidimazione FIR (dry-run)',
            self::STATUS_INFO,
            'Non eseguita automaticamente — seguire '. $this->vidimaDryRunDocPath() .' §4 per vidima sandbox controllata su blocco di test.',
        );
    }
}
