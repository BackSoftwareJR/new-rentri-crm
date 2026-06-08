<?php

namespace App\Domain\Rentri;

use App\Domain\Deploy\PreflightService;
use App\Domain\Security\WafDeploymentPreflightService;
use App\Models\RentriSetting;

/**
 * Checklist unificata switch RENTRI produzione MASE (Sprint 106).
 */
class RentriProductionSwitchService
{
    public const RUNBOOK_DOC = 'docs/RENTRI-PRODUCTION-SWITCH-RUNBOOK.md';

    public function __construct(
        private readonly RentriProdReadinessService $prodReadiness,
        private readonly RentriRuntimeModeService $runtimeMode,
        private readonly PreflightService $preflight,
        private readonly WafDeploymentPreflightService $wafPreflight,
    ) {}

    public function rentriEnv(): string
    {
        return strtolower((string) config('services.rentri.env', 'sandbox'));
    }

    public function isRentriEnvProduction(): bool
    {
        return in_array($this->rentriEnv(), ['production', 'prod'], true);
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function unifiedChecklist(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();

        $items = array_merge(
            $this->envChecklist($settings),
            $this->mapProdReadinessItems($this->prodReadiness->checklist($settings)),
            $this->securityChecklist(),
        );

        $preflight = $this->preflightSnapshot();
        $items[] = $this->item(
            'preflight',
            'Preflight deploy (`rentri:preflight`) senza FAIL',
            $preflight['passed'],
            $preflight['passed']
                ? 'Tutti i check ok o warn accettabili.'
                : 'Eseguire php artisan rentri:preflight e correggere i FAIL.',
            false,
            'preflight',
        );

        return $items;
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function envChecklist(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();

        $apiStubConfig = (bool) config('services.rentri.api_stub', true);
        $firmaStubConfig = (bool) config('services.rentri.firma_stub', true);
        $apiLive = ! $this->runtimeMode->isApiStub($settings);
        $firmaLive = ! $this->runtimeMode->isFirmaStub($settings);

        return [
            $this->item(
                'rentri_env',
                'RENTRI_ENV=production',
                $this->isRentriEnvProduction(),
                'Impostare RENTRI_ENV=production in .env staging/prod.',
                false,
                'env',
            ),
            $this->item(
                'api_stub_off',
                'RENTRI_API_STUB=false (o override live UI attivo)',
                ! $apiStubConfig || $this->runtimeMode->isLiveEnabled($settings),
                'Disabilitare stub API in .env oppure attivare modalità live (step 4).',
                false,
                'env',
            ),
            $this->item(
                'firma_stub_off',
                'RENTRI_FIRMA_STUB=false (o firma live UI attiva)',
                ! $firmaStubConfig || $settings->firma_live_enabled_at !== null,
                'Disabilitare stub firma in .env oppure passaggio live UI.',
                false,
                'env',
            ),
            $this->item(
                'runtime_api_live',
                'Runtime API verso MASE (non stub)',
                $apiLive,
                'Attivare modalità live o RENTRI_API_STUB=false.',
                false,
                'env',
            ),
            $this->item(
                'runtime_firma_live',
                'Runtime firma xFIR (non stub)',
                $firmaLive,
                'RENTRI_FIRMA_STUB=false o passaggio live UI.',
                false,
                'env',
            ),
            $this->item(
                'production_base_url',
                'Base URL produzione configurato',
                filled(config('services.rentri.base_url_production')),
                'RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it',
                false,
                'env',
            ),
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function securityChecklist(): array
    {
        return [
            $this->item(
                'waf_block_gate',
                'WAF edge in modalità block (consigliato post-pen-test)',
                $this->wafPreflight->isBlockMode(),
                'WAF_MODE=block — gate opzionale; vedi WAF-STAGING-ROLLOUT.md.',
                true,
                'security',
            ),
        ];
    }

    public function canSwitchToProduction(?RentriSetting $settings = null): bool
    {
        return collect($this->unifiedChecklist($settings))
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    public function isProductionActive(?RentriSetting $settings = null): bool
    {
        $settings ??= RentriSetting::instance();

        return $settings->ambiente === 'produzione'
            && $this->isRentriEnvProduction()
            && ! $this->runtimeMode->isApiStub($settings)
            && ! $this->runtimeMode->isFirmaStub($settings);
    }

    /**
     * @return array{
     *     ready: bool,
     *     production_active: bool,
     *     ok: int,
     *     total: int,
     *     optional_pending: int,
     *     rentri_env: string,
     *     api_mode: string
     * }
     */
    public function summary(?RentriSetting $settings = null): array
    {
        $items = $this->unifiedChecklist($settings);
        $required = collect($items)->reject(fn (array $i): bool => $i['optional']);
        $optional = collect($items)->filter(fn (array $i): bool => $i['optional']);

        return [
            'ready'              => $this->canSwitchToProduction($settings),
            'production_active'  => $this->isProductionActive($settings),
            'ok'                 => $required->where('ok', true)->count(),
            'total'              => $required->count(),
            'optional_pending'   => $optional->reject(fn (array $i): bool => $i['ok'])->count(),
            'rentri_env'         => $this->rentriEnv(),
            'api_mode'           => $this->runtimeMode->apiModeDisplayLabel($settings),
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     production_active: bool,
     *     checklist: list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>,
     *     preflight: array{passed: bool, checks: list<array{name: string, status: string, message: string}>},
     *     summary: array<string, mixed>
     * }
     */
    public function dryRunReport(?RentriSetting $settings = null): array
    {
        $settings ??= RentriSetting::instance();
        $preflight = $this->preflight->run();

        return [
            'passed'             => $this->canSwitchToProduction($settings) && $preflight['passed'],
            'production_active'  => $this->isProductionActive($settings),
            'checklist'          => $this->unifiedChecklist($settings),
            'preflight'          => $preflight,
            'summary'            => $this->summary($settings),
        ];
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function rollbackSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Impostare RENTRI_API_STUB=true',
                'detail' => 'Rollback immediato traffico API — nessuna chiamata api.rentri.gov.it.',
            ],
            [
                'step'   => 2,
                'action' => 'Impostare RENTRI_FIRMA_STUB=true',
                'detail' => 'Disabilita firma COSE live; vidima/xFIR in stub.',
            ],
            [
                'step'   => 3,
                'action' => 'UI: «Rientra in stub» (Impostazioni RENTRI step 4)',
                'detail' => 'Azzera override live_mode_enabled_at; activity log audit.',
            ],
            [
                'step'   => 4,
                'action' => 'Opzionale: RENTRI_ENV=sandbox',
                'detail' => 'Se rollback completo verso demoapi.',
            ],
            [
                'step'   => 5,
                'action' => 'Monitoraggio 48h',
                'detail' => 'rentri:monitor, dead-letter, SLA dashboard hub RENTRI.',
            ],
        ];
    }

    public function runbookRelativePath(): string
    {
        return self::RUNBOOK_DOC;
    }

    /**
     * @param  list<array{key: string, label: string, ok: bool, hint: ?string}>  $items
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    private function mapProdReadinessItems(array $items): array
    {
        return array_map(
            fn (array $item): array => $this->item(
                'ui_'.$item['key'],
                $item['label'],
                $item['ok'],
                $item['hint'],
                false,
                'ui',
            ),
            $items,
        );
    }

    /**
     * @return array{passed: bool, fail_count: int}
     */
    private function preflightSnapshot(): array
    {
        $result = $this->preflight->run();
        $failCount = collect($result['checks'])->where('status', 'fail')->count();

        return [
            'passed'     => $result['passed'],
            'fail_count' => $failCount,
        ];
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}
     */
    private function item(
        string $key,
        string $label,
        bool $ok,
        ?string $hint,
        bool $optional,
        string $group,
    ): array {
        return compact('key', 'label', 'ok', 'hint', 'optional', 'group');
    }
}
