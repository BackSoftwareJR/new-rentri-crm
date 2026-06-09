<?php

namespace App\Domain\Rentri;

use App\Domain\Magazzino\MagazzinoService;
use App\Jobs\RentriInitialSyncJob;
use App\Models\RentriSetting;
use App\Support\Demo\DemoContext;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCertificateServiceInterface;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class RentriOnboardingService
{
    public const TOTAL_STEPS = 3;

    public function currentStep(RentriSetting $settings): int
    {
        if ($this->isComplete($settings)) {
            return self::TOTAL_STEPS;
        }

        return min(max($settings->onboarding_step_completed, 0) + 1, self::TOTAL_STEPS);
    }

    public function isComplete(RentriSetting $settings): bool
    {
        return $settings->onboarding_step_completed >= self::TOTAL_STEPS;
    }

    /**
     * @param  array{ambiente: string, cf: string, cf_operatore?: string|null, piva: string, ragione_sociale: string, num_iscr_sito: string}  $data
     */
    public function saveOperatorData(array $data): RentriSetting
    {
        if (DemoContext::isActive()) {
            $data['ambiente'] = 'sandbox';
        }

        $settings = RentriSetting::instance();

        $settings->update([
            'ambiente'                  => $data['ambiente'],
            'cf'                        => $data['cf'],
            'cf_operatore'              => $data['cf_operatore'] ?? null,
            'piva'                      => $data['piva'],
            'ragione_sociale'           => $data['ragione_sociale'],
            'num_iscr_sito'             => $data['num_iscr_sito'],
            'onboarding_step_completed' => max($settings->onboarding_step_completed, 1),
        ]);

        return $settings->fresh();
    }

    public function saveCertificate(UploadedFile $certificate, string $password, RentriCertificateServiceInterface $certificates): RentriSetting
    {
        $settings = $certificates->upload($certificate, $password);

        if (! $certificates->validate($settings)) {
            throw new InvalidArgumentException('Certificato PKCS#12 non valido o incompleto.');
        }

        $settings->update([
            'onboarding_step_completed' => max($settings->onboarding_step_completed, 2),
        ]);

        if (DemoContext::usesLiveSandboxApi()) {
            RentriInitialSyncJob::dispatch();
        }

        return $settings->fresh();
    }

    public function runHealthCheck(RentriApiClientInterface $apiClient): RentriSetting
    {
        $settings = RentriSetting::instance();

        if (blank($settings->num_iscr_sito)) {
            throw new RuntimeException('Completare i dati operatore prima del health check.');
        }

        if (blank($settings->cert_path_encrypted)) {
            throw new RuntimeException('Caricare il certificato prima del health check.');
        }

        $status = $apiClient->healthCheck();

        $settings->update([
            'last_health_check_at'      => now(),
            'last_health_status'        => $status,
            'onboarding_step_completed' => max($settings->onboarding_step_completed, self::TOTAL_STEPS),
        ]);

        return $settings->fresh();
    }

    /**
     * Test connessione live: health (blocchi FIR) + campione codifiche CER.
     * On success, auto-syncs CER codifiche and ensures serbatoi exist.
     *
     * @return array{health: array<string, mixed>, codifiche_count: int, codifiche_synced: int, serbatoi_created: int, sync_error: string|null}
     */
    public function testConnection(RentriApiClientInterface $apiClient): array
    {
        $health = $this->runHealthCheck($apiClient);
        $codifiche = $apiClient->fetchCodificheCer();
        $items = $codifiche['items'] ?? $codifiche['data'] ?? [];
        $codificheCount = is_array($items) ? count($items) : 0;

        $codificheSync = 0;
        $serbatoi = 0;
        $syncError = null;

        if ($codificheCount > 0) {
            try {
                $syncResult = app(RentriCodificheSyncInterface::class)->sync();
                $codificheSync = ($syncResult['created'] ?? 0) + ($syncResult['updated'] ?? 0) + ($syncResult['skipped'] ?? 0);
                $serbatoi = app(MagazzinoService::class)->ensureSerbatoi();

                Log::info('RENTRI onboarding: auto-sync completato', [
                    'codifiche_synced' => $codificheSync,
                    'serbatoi_created' => $serbatoi,
                ]);
            } catch (\Throwable $e) {
                $syncError = $e->getMessage();
                Log::warning('RENTRI onboarding: auto-sync fallito', ['error' => $syncError]);
            }
        }

        return [
            'health'           => $health,
            'codifiche_count'  => $codificheCount,
            'codifiche_synced' => $codificheSync,
            'serbatoi_created' => $serbatoi,
            'sync_error'       => $syncError,
        ];
    }

    /**
     * Completes step 3 and dispatches the background initial sync job.
     */
    public function completeOnboarding(): void
    {
        RentriInitialSyncJob::dispatch();
    }

    public function stepLabel(int $step): string
    {
        return match ($step) {
            1       => 'Dati operatore',
            2       => 'Certificato',
            3       => 'Health check',
            default => 'Onboarding',
        };
    }
}
