<?php

namespace App\Domain\Ecommerce;

/**
 * Preflight Stripe sandbox vs produzione (Sprint 103, switch Sprint 117).
 */
class StripeProductionPreflightService
{
    public function stripeSecretKey(): string
    {
        return (string) config('services.stripe.secret', '');
    }

    public function isLiveModeFlag(): bool
    {
        return (bool) config('services.stripe.live_mode', false);
    }

    public function isProductionKey(): bool
    {
        return str_starts_with($this->stripeSecretKey(), 'sk_live_');
    }

    public function isSandboxKey(): bool
    {
        return str_starts_with($this->stripeSecretKey(), 'sk_test_');
    }

    /**
     * Produzione effettiva: flag STRIPE_LIVE_MODE o chiave sk_live_.
     */
    public function isProductionEnvironment(): bool
    {
        if ($this->isLiveModeFlag()) {
            return true;
        }

        return $this->isProductionKey();
    }

    public function currency(): string
    {
        return strtolower((string) config('services.stripe.currency', 'eur'));
    }

    public function dashboardUrl(): string
    {
        return $this->isProductionEnvironment()
            ? 'https://dashboard.stripe.com/'
            : 'https://dashboard.stripe.com/test/';
    }

    public function webhookEndpointUrl(): string
    {
        return url('/webhooks/stripe/ecommerce');
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function checklist(): array
    {
        $secret = $this->stripeSecretKey();
        $webhook = (string) config('services.stripe.webhook_secret', '');
        $currency = $this->currency();
        $production = $this->isProductionEnvironment();

        $keyModeOk = $this->keyModeMatches($secret, $production);

        return [
            [
                'key'   => 'stripe_secret',
                'label' => 'Stripe secret key (STRIPE_KEY)',
                'ok'    => $secret !== '',
                'hint'  => $production
                    ? 'Usare sk_live_… per produzione.'
                    : 'Usare sk_test_… per sandbox.',
            ],
            [
                'key'   => 'stripe_key_mode',
                'label' => $production
                    ? 'Chiave produzione sk_live_'
                    : 'Chiave sandbox sk_test_ (o test)',
                'ok'    => $keyModeOk,
                'hint'  => $this->keyModeHint($secret, $production),
            ],
            [
                'key'   => 'stripe_webhook',
                'label' => 'Webhook secret (STRIPE_WEBHOOK_SECRET)',
                'ok'    => $webhook !== '',
                'hint'  => 'Obbligatorio in produzione per verifica firma checkout.session.completed.',
            ],
            [
                'key'   => 'stripe_currency',
                'label' => 'Valuta EUR (STRIPE_CURRENCY=eur)',
                'ok'    => $currency === 'eur',
                'hint'  => $currency !== 'eur' ? 'Valuta attuale: '.$currency : null,
            ],
        ];
    }

    public function isReady(): bool
    {
        return collect($this->checklist())->every(fn (array $item) => $item['ok']);
    }

    private function keyModeHint(string $secret, bool $production): ?string
    {
        if ($secret === '') {
            return 'STRIPE_KEY mancante.';
        }

        if (! $this->keyModeMatches($secret, $production)) {
            return $production
                ? 'Ambiente produzione richiede sk_live_.'
                : 'Ambiente sandbox richiede sk_test_.';
        }

        return null;
    }

    private function keyModeMatches(string $secret, bool $production): bool
    {
        if ($secret === '') {
            return false;
        }

        return $production ? $this->isProductionKey() : $this->isSandboxKey();
    }
}
