<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Support\Demo\DemoContext;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;

class RentriConnectionStatusService
{
    public const STATE_NOT_CONFIGURED = 'not_configured';

    public const STATE_CERT_EXPIRED = 'cert_expired';

    public const STATE_CONNECTED_SANDBOX = 'connected_sandbox';

    public const STATE_CONNECTED_PRODUCTION = 'connected_production';

    public const STATE_STUB_MODE = 'stub_mode';

    public function __construct(
        private RentriCertificateServiceInterface $certificates,
        private RentriRuntimeModeService $runtimeMode,
    ) {}

    /**
     * @return array{
     *   state: string,
     *   label: string,
     *   api_mode: string,
     *   ambiente: string|null,
     *   certificato: string,
     *   ultimo_health: string|null
     * }
     */
    public function resolve(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();
        $apiStub = $this->runtimeMode->isApiStub($settings);

        if ($apiStub) {
            $label = $settings->ambiente === 'produzione'
                ? 'Stub attivo con ambiente produzione — completare passaggio live'
                : 'Modalità stub (RENTRI_API_STUB=true o override non attivo)';

            return [
                'state'         => self::STATE_STUB_MODE,
                'label'         => $label,
                'api_mode'      => 'stub',
                'ambiente'      => $settings->ambiente,
                'certificato'   => $this->certLabel($settings),
                'ultimo_health' => $settings->last_health_check_at?->format('d/m/Y H:i'),
            ];
        }

        if (blank($settings->num_iscr_sito) || blank($settings->cert_path_encrypted)) {
            $label = DemoContext::usesLiveSandboxApi()
                ? 'Palestra sandbox live — caricare certificato PKCS#12 DEMO da rentri.gov.it/demo'
                : 'Non configurato — completare dati operatore e certificato';

            return [
                'state'         => self::STATE_NOT_CONFIGURED,
                'label'         => $label,
                'api_mode'      => 'live',
                'ambiente'      => $settings->ambiente,
                'certificato'   => $this->certLabel($settings),
                'ultimo_health' => null,
            ];
        }

        if ($this->certificates->isExpired($settings)) {
            return [
                'state'         => self::STATE_CERT_EXPIRED,
                'label'         => 'Certificato scaduto — caricare un nuovo PKCS#12',
                'api_mode'      => 'live',
                'ambiente'      => $settings->ambiente,
                'certificato'   => $this->certLabel($settings),
                'ultimo_health' => $settings->last_health_check_at?->format('d/m/Y H:i'),
            ];
        }

        $healthOk = ($settings->last_health_status['status'] ?? null) === 'ok';
        $connected = $healthOk && $settings->last_health_check_at !== null;

        if ($settings->ambiente === 'produzione') {
            return [
                'state'         => $connected ? self::STATE_CONNECTED_PRODUCTION : self::STATE_NOT_CONFIGURED,
                'label'         => $connected
                    ? 'Connesso produzione — ultimo test OK'
                    : 'Produzione — eseguire test connessione',
                'api_mode'      => 'live',
                'ambiente'      => 'produzione',
                'certificato'   => $this->certLabel($settings),
                'ultimo_health' => $settings->last_health_check_at?->format('d/m/Y H:i'),
            ];
        }

        return [
            'state'         => $connected ? self::STATE_CONNECTED_SANDBOX : self::STATE_NOT_CONFIGURED,
            'label'         => $connected
                ? 'Connesso sandbox/demo — ultimo test OK'
                : 'Sandbox — eseguire test connessione',
            'api_mode'      => 'live',
            'ambiente'      => 'sandbox',
            'certificato'   => $this->certLabel($settings),
            'ultimo_health' => $settings->last_health_check_at?->format('d/m/Y H:i'),
        ];
    }

    private function certLabel(RentriSetting $settings): string
    {
        if (blank($settings->cert_path_encrypted)) {
            return 'Mancante';
        }

        if ($this->certificates->isExpired($settings)) {
            return 'Scaduto';
        }

        return $settings->cert_scadenza
            ? 'Valido fino al '.$settings->cert_scadenza->format('d/m/Y')
            : 'Configurato';
    }
}
