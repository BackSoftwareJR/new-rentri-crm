<?php

namespace App\Domain\Ecommerce;

use App\Support\Demo\DemoContext;

class EcommercePaymentRuntimeModeService
{
    public function __construct(
        private StripeProductionPreflightService $stripePreflight,
    ) {}

    public function isStub(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        return (bool) config('services.ecommerce.payment_stub', true);
    }

    public function isStripeProduction(): bool
    {
        if ($this->isStub()) {
            return false;
        }

        return $this->stripePreflight->isProductionEnvironment();
    }

    public function isStripeSandbox(): bool
    {
        return ! $this->isStub() && ! $this->isStripeProduction();
    }

    public function modeLabel(): string
    {
        return $this->isStub() ? 'stub' : ($this->isStripeProduction() ? 'production' : 'sandbox');
    }

    public function modeDisplayLabel(): string
    {
        return match ($this->modeKind()) {
            'offline'    => 'Pagamenti demo offline',
            'stub'       => 'Checkout interno',
            'production' => 'Stripe produzione',
            default      => 'Stripe sandbox',
        };
    }

    public function modeDisplayVariant(): string
    {
        return match ($this->modeKind()) {
            'offline'    => 'warning',
            'stub'       => 'info',
            'production' => 'success',
            default      => 'info',
        };
    }

    /**
     * @return 'offline'|'stub'|'sandbox'|'production'
     */
    public function modeKind(): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        if ($this->isStub()) {
            return 'stub';
        }

        return $this->isStripeProduction() ? 'production' : 'sandbox';
    }

    public function stripeDashboardUrl(): string
    {
        return $this->stripePreflight->dashboardUrl();
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function preflightChecklist(): array
    {
        if ($this->isStub()) {
            return [];
        }

        return $this->stripePreflight->checklist();
    }

    public function preflightReady(): bool
    {
        if ($this->isStub()) {
            return true;
        }

        return $this->stripePreflight->isReady();
    }
}
