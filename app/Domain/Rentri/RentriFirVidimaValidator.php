<?php

namespace App\Domain\Rentri;

use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Exceptions\RentriFirVidimaException;

class RentriFirVidimaValidator
{
    private const MIN_ONBOARDING_STEP = 3;

    public function __construct(
        private RentriCertificateServiceInterface $certificates,
        private RentriRuntimeModeService $runtimeMode,
    ) {}

    /**
     * @return list<array{codice: string, label: string, ok: bool, message: string|null}>
     */
    public function checklist(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();

        $identificativo = trim((string) ($settings->cf_operatore ?: $settings->cf ?: ''));
        $items = [
            $this->item(
                'cf_operatore',
                'Codice fiscale operatore RENTRI',
                $identificativo !== '',
                $identificativo !== '' ? null : 'Configura CF operatore o CF impresa in impostazioni RENTRI.',
            ),
            $this->item(
                'num_iscr_sito',
                'Numero iscrizione sito RENTRI',
                trim((string) ($settings->num_iscr_sito ?? '')) !== '',
                'Configura num_iscr_sito in onboarding RENTRI.',
            ),
            $this->item(
                'onboarding',
                'Onboarding RENTRI completato (test connessione)',
                (int) ($settings->onboarding_step_completed ?? 0) >= self::MIN_ONBOARDING_STEP,
                'Completa almeno il test connessione nel wizard RENTRI (step 3).',
            ),
        ];

        if ($this->runtimeMode->isApiStub($settings)) {
            $items[] = $this->item(
                'certificato_mtls',
                'Certificato mTLS interoperabilità',
                true,
                null,
            );
        } else {
            $certConfigured = $this->certificates->validate($settings);
            $certValid = $certConfigured && ! $this->certificates->isExpired($settings);
            $items[] = $this->item(
                'certificato_mtls',
                'Certificato mTLS interoperabilità valido',
                $certValid,
                ! $certConfigured
                    ? 'Carica un certificato mTLS in impostazioni RENTRI.'
                    : 'Certificato mTLS scaduto — ricarica un PKCS#12 valido.',
            );
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    public function blockers(?RentriSetting $settings = null): array
    {
        $errors = [];

        foreach ($this->checklist($settings) as $item) {
            if (! $item['ok']) {
                $errors[] = $item['message'] ?? $item['label'];
            }
        }

        return $errors;
    }

    public function isReady(?RentriSetting $settings = null): bool
    {
        foreach ($this->checklist($settings) as $item) {
            if (! $item['ok']) {
                return false;
            }
        }

        return true;
    }

    public function assertReady(?RentriSetting $settings = null): void
    {
        $errors = $this->blockers($settings);

        if ($errors !== []) {
            throw new RentriFirVidimaException($errors);
        }
    }

    /**
     * @return array{codice: string, label: string, ok: bool, message: string|null}
     */
    private function item(string $codice, string $label, bool $ok, ?string $message = null): array
    {
        return [
            'codice'  => $codice,
            'label'   => $label,
            'ok'      => $ok,
            'message' => $ok ? null : ($message ?? $label),
        ];
    }
}
