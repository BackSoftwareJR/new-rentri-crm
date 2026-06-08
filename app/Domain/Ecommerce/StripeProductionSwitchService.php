<?php

namespace App\Domain\Ecommerce;

/**
 * Checklist switch Stripe sandbox → produzione (Sprint 117).
 */
class StripeProductionSwitchService
{
    public const RUNBOOK_DOC = 'docs/STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md';

    public function __construct(
        private readonly EcommercePaymentRuntimeModeService $runtime,
        private readonly StripeProductionPreflightService $preflight,
        private readonly StripeDisputeStubService $disputes,
    ) {}

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function unifiedChecklist(): array
    {
        $production = $this->preflight->isProductionEnvironment();

        $items = [
            $this->item(
                'payment_stub_off',
                'ECOMMERCE_PAYMENT_STUB=false',
                ! $this->runtime->isStub(),
                'Disattivare stub pagamenti in .env produzione.',
                false,
                'env',
            ),
            $this->item(
                'stripe_live_mode',
                $production
                    ? 'STRIPE_LIVE_MODE=true (produzione)'
                    : 'STRIPE_LIVE_MODE=false (sandbox)',
                $production
                    ? $this->preflight->isLiveModeFlag()
                    : ! $this->preflight->isLiveModeFlag(),
                $production
                    ? 'Impostare STRIPE_LIVE_MODE=true per go-live Stripe prod.'
                    : 'Sandbox: STRIPE_LIVE_MODE=false con sk_test_.',
                false,
                'env',
            ),
        ];

        foreach ($this->preflight->checklist() as $preflightItem) {
            $items[] = $this->item(
                'preflight_'.$preflightItem['key'],
                $preflightItem['label'],
                $preflightItem['ok'],
                $preflightItem['hint'],
                false,
                'stripe',
            );
        }

        foreach ($this->disputes->checklist() as $disputeItem) {
            $items[] = $this->item(
                'dispute_'.$disputeItem['key'],
                $disputeItem['label'],
                $disputeItem['ok'],
                $disputeItem['hint'],
                $disputeItem['optional'],
                'dispute',
            );
        }

        return $items;
    }

    public function canSwitchToProduction(): bool
    {
        return collect($this->unifiedChecklist())
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    public function isProductionActive(): bool
    {
        return ! $this->runtime->isStub()
            && $this->runtime->isStripeProduction()
            && $this->preflight->isReady();
    }

    /**
     * @return array{
     *     ready: bool,
     *     production_active: bool,
     *     mode: string,
     *     mode_label: string,
     *     ok: int,
     *     total: int,
     *     optional_pending: int,
     *     dashboard_url: string
     * }
     */
    public function summary(): array
    {
        $items = $this->unifiedChecklist();
        $required = collect($items)->reject(fn (array $i): bool => $i['optional']);

        return [
            'ready'              => $this->canSwitchToProduction(),
            'production_active'  => $this->isProductionActive(),
            'mode'               => $this->runtime->modeKind(),
            'mode_label'         => $this->runtime->modeDisplayLabel(),
            'ok'                 => $required->where('ok', true)->count(),
            'total'              => $required->count(),
            'optional_pending'   => collect($items)->filter(fn (array $i): bool => $i['optional'])->reject(fn (array $i): bool => $i['ok'])->count(),
            'dashboard_url'      => $this->preflight->dashboardUrl(),
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     production_active: bool,
     *     checklist: list<array<string, mixed>>,
     *     summary: array<string, mixed>
     * }
     */
    public function dryRunReport(): array
    {
        $passed = $this->canSwitchToProduction();

        if ($this->runtime->isStub()) {
            $passed = true;
        }

        return [
            'passed'              => $passed,
            'production_active'   => $this->isProductionActive(),
            'checklist'           => $this->unifiedChecklist(),
            'summary'             => $this->summary(),
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
                'action' => 'Impostare ECOMMERCE_PAYMENT_STUB=true',
                'detail' => 'Rollback immediato — checkout token stub, nessuna chiamata Stripe.',
            ],
            [
                'step'   => 2,
                'action' => 'STRIPE_LIVE_MODE=false + sk_test_',
                'detail' => 'Ripristinare chiavi sandbox se necessario.',
            ],
            [
                'step'   => 3,
                'action' => 'php artisan config:clear',
                'detail' => 'Verificare badge «Pagamenti stub» su hub e-commerce.',
            ],
            [
                'step'   => 4,
                'action' => 'Disattivare webhook prod su Stripe Dashboard',
                'detail' => 'Endpoint '.$this->preflight->webhookEndpointUrl().' — evitare eventi live in staging.',
            ],
        ];
    }

    public function runbookRelativePath(): string
    {
        return self::RUNBOOK_DOC;
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
