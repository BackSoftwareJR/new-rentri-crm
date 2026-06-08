<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriFirmaCertificateServiceInterface;

class RentriProdReadinessService
{
    public const PRODUCTION_STEP = 4;

    public function __construct(
        private readonly RentriOnboardingService $onboarding,
        private readonly RentriRuntimeModeService $runtimeMode,
        private readonly RentriCertificateServiceInterface $certificates,
        private readonly RentriFirmaCertificateServiceInterface $firmaCertificates,
    ) {}

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function checklist(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();

        return [
            $this->item(
                'ambiente_produzione',
                'Ambiente impostato su produzione',
                $settings->ambiente === 'produzione',
                'Selezionare «Produzione» nei dati operatore (step 1).',
            ),
            $this->item(
                'cert_mtls',
                'Certificato interoperabilità mTLS valido',
                filled($settings->cert_path_encrypted) && ! $this->certificates->isExpired($settings),
                'Caricare PKCS#12 non scaduto (step 2).',
            ),
            $this->item(
                'cert_firma',
                'Certificato firma xFIR valido',
                filled($settings->firma_cert_path_encrypted) && ! $this->firmaCertificates->isExpired($settings),
                'Caricare certificato firma remota distinto da mTLS.',
            ),
            $this->item(
                'dati_operatore',
                'Dati operatore completi',
                filled($settings->num_iscr_sito) && filled($settings->cf_operatore) && filled($settings->piva),
                'Completare CF operatore, P.IVA e n. iscrizione sito.',
            ),
            $this->item(
                'onboarding',
                'Onboarding RENTRI completato',
                $this->onboarding->isComplete($settings),
                'Completare test connessione (step 3).',
            ),
            $this->item(
                'health_ok',
                'Ultimo health check OK',
                ($settings->last_health_status['status'] ?? null) === 'ok'
                    && $settings->last_health_check_at !== null,
                'Eseguire test connessione con esito positivo.',
            ),
        ];
    }

    public function canEnableLiveMode(?RentriSetting $settings = null): bool
    {
        return collect($this->checklist($settings))->every(fn (array $item): bool => $item['ok']);
    }

    public function shouldShowProdStubBanner(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        if ($settings->ambiente !== 'produzione') {
            return false;
        }

        return $this->runtimeMode->isApiStub($settings);
    }

    /**
     * @return array{ok: int, total: int}
     */
    public function summary(?RentriSetting $settings = null): array
    {
        $items = $this->checklist($settings);

        return [
            'ok'    => collect($items)->where('ok', true)->count(),
            'total' => count($items),
        ];
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string}
     */
    private function item(string $key, string $label, bool $ok, ?string $hint): array
    {
        return compact('key', 'label', 'ok', 'hint');
    }
}
