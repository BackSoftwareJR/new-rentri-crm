<?php

namespace App\Domain\Ecommerce;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Ecommerce\Contracts\StripeCheckoutClientInterface;
use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use Illuminate\Support\Str;
use RuntimeException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Gateway pagamenti e-commerce — stub token vs Stripe Checkout Session live.
 *
 * @see docs/SPRINT-96-AUDIT-NOTES.md
 * @see docs/SPRINT-103-AUDIT-NOTES.md
 */
class EcommercePaymentGatewayService
{
    public function __construct(
        private EcommercePaymentRuntimeModeService $runtime,
        private StripeCheckoutClientInterface $stripeClient,
        private StripeWebhookIdempotencyService $webhookIdempotency,
        private StripeProductionPreflightService $stripePreflight,
        private StripeDisputeStubService $disputeStub,
    ) {}

    /**
     * Avvia pagamento: stub token o Stripe Checkout Session.
     *
     * @return array{gateway: string, checkout_token: ?string, stripe_session_id: ?string, checkout_url: ?string}
     */
    public function initiatePayment(EcommerceOrdine $ordine, string $pagamentoMetodo): array
    {
        if ($this->runtime->isStub()) {
            return $this->initiateStubPayment($ordine);
        }

        if (! $this->runtime->preflightReady()) {
            throw new RuntimeException('Preflight Stripe non superato. Verificare STRIPE_KEY, STRIPE_WEBHOOK_SECRET e STRIPE_CURRENCY.');
        }

        return $this->initiateStripeCheckout($ordine, $pagamentoMetodo);
    }

    public function confirmStubPayment(EcommerceOrdine $ordine, string $token, int $userId): EcommerceOrdine
    {
        if ($ordine->stato !== OrdineEcommerceStato::PagamentoInAttesa) {
            throw new \InvalidArgumentException('Ordine non in attesa di pagamento.');
        }

        if ($ordine->payment_gateway !== 'stub') {
            throw new \InvalidArgumentException('Ordine non in modalità pagamento stub.');
        }

        if (! hash_equals((string) $ordine->checkout_token, $token)) {
            throw new \InvalidArgumentException('Token checkout non valido.');
        }

        return $this->markOrdineConfermato($ordine, $userId, 'Ordine e-commerce confermato (pagamento stub)');
    }

    /**
     * Gestisce webhook Stripe (o payload stub senza firma se secret assente).
     */
    public function handleWebhook(string $payload, ?string $signatureHeader): void
    {
        $event = $this->parseWebhookEvent($payload, $signatureHeader);
        $eventId = (string) ($event['id'] ?? '');

        if ($eventId !== '' && $this->webhookIdempotency->alreadyProcessed($eventId)) {
            app(StripeReconciliationLogService::class)->logDuplicate(
                $eventId,
                (string) ($event['type'] ?? ''),
            );

            return;
        }

        $type = (string) ($event['type'] ?? '');

        if ($this->disputeStub->isDisputeEvent($type)) {
            if ($this->disputeStub->isStubEnabled()) {
                $this->disputeStub->handleDisputeEvent($event);
            }

            return;
        }

        if ($type !== 'checkout.session.completed') {
            if ($eventId !== '') {
                $this->webhookIdempotency->recordProcessed($event, null, [
                    'ignored'     => true,
                    'environment' => $this->stripePreflight->isProductionEnvironment() ? 'production' : 'sandbox',
                ]);
            }

            return;
        }

        /** @var array<string, mixed> $session */
        $session = $event['data']['object'] ?? [];
        $sessionId = (string) ($session['id'] ?? '');

        if ($sessionId === '') {
            return;
        }

        $ordine = EcommerceOrdine::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('stato', OrdineEcommerceStato::PagamentoInAttesa)
            ->first();

        if ($ordine === null) {
            if ($eventId !== '') {
                $this->webhookIdempotency->recordProcessed($event, null, [
                    'checkout_session_id' => $sessionId,
                    'ordine_not_found'    => true,
                    'environment'         => $this->stripePreflight->isProductionEnvironment() ? 'production' : 'sandbox',
                ]);
            }

            return;
        }

        $reconciliation = [
            'checkout_session_id' => $sessionId,
            'amount_eur'          => (float) $ordine->totale,
            'environment'         => $this->stripePreflight->isProductionEnvironment() ? 'production' : 'sandbox',
            'currency'            => $this->stripePreflight->currency(),
        ];

        $this->markOrdineConfermato($ordine, (int) ($ordine->user_id ?? 0), 'Ordine e-commerce confermato (Stripe webhook)');

        app(\App\Support\Logging\StructuredLogService::class)->info(
            'stripe',
            'webhook_checkout_completed',
            'Webhook Stripe checkout.session.completed elaborato',
            [
                'entity_type' => 'ecommerce_ordine',
                'entity_id'   => $ordine->id,
                'user_id'     => (int) ($ordine->user_id ?? 0),
                'outcome'     => 'success',
                'context'     => [
                    'event_id'            => $eventId,
                    'checkout_session_id' => $sessionId,
                    'environment'         => $reconciliation['environment'],
                ],
            ],
        );

        if ($eventId !== '') {
            $this->webhookIdempotency->recordProcessed($event, $ordine->fresh(), $reconciliation);
        }
    }

    /**
     * @return array{gateway: string, checkout_token: string}
     */
    private function initiateStubPayment(EcommerceOrdine $ordine): array
    {
        $token = Str::random(32);

        $ordine->update([
            'payment_gateway'            => 'stub',
            'checkout_token'             => $token,
            'stripe_checkout_session_id' => null,
            'payment_checkout_url'       => null,
        ]);

        return [
            'gateway'            => 'stub',
            'checkout_token'     => $token,
            'stripe_session_id'  => null,
            'checkout_url'       => null,
        ];
    }

    /**
     * @return array{gateway: string, checkout_token: null, stripe_session_id: string, checkout_url: ?string}
     */
    private function initiateStripeCheckout(EcommerceOrdine $ordine, string $pagamentoMetodo): array
    {
        $successUrl = route('segreteria.ecommerce.ordini.show', $ordine).'?stripe=success';
        $cancelUrl = route('segreteria.ecommerce.ordini.show', $ordine).'?stripe=cancel';

        $session = $this->stripeClient->createCheckoutSession([
            'mode'        => 'payment',
            'success_url' => $successUrl,
            'cancel_url'  => $cancelUrl,
            'line_items'  => [[
                'price_data' => [
                    'currency'     => $this->stripePreflight->currency(),
                    'unit_amount'  => (int) round(((float) $ordine->totale) * 100),
                    'product_data' => [
                        'name' => 'Ordine e-commerce #'.$ordine->id,
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'ordine_id'         => (string) $ordine->id,
                'pagamento_metodo'  => $pagamentoMetodo,
                'stripe_environment' => $this->stripePreflight->isProductionEnvironment() ? 'production' : 'sandbox',
            ],
        ]);

        $ordine->update([
            'payment_gateway'            => 'stripe',
            'checkout_token'             => null,
            'stripe_checkout_session_id' => $session->id,
            'payment_checkout_url'       => $session->url,
        ]);

        return [
            'gateway'           => 'stripe',
            'checkout_token'    => null,
            'stripe_session_id' => $session->id,
            'checkout_url'      => $session->url,
        ];
    }

    private function markOrdineConfermato(EcommerceOrdine $ordine, int $userId, string $logDescription): EcommerceOrdine
    {
        $ordine->update([
            'stato'                      => OrdineEcommerceStato::Confermato,
            'confermato_at'              => now(),
            'checkout_token'             => null,
            'payment_checkout_url'       => null,
        ]);

        app(ActivityLogService::class)->record(
            'ecommerce',
            $logDescription,
            $ordine->fresh(),
            [
                'ordine_id' => $ordine->id,
                'totale'    => (float) $ordine->totale,
                'gateway'   => $ordine->payment_gateway,
            ],
            $userId > 0 ? $userId : null,
        );

        return $ordine->fresh();
    }

    /**
     * @return array<string, mixed>
     */
    private function parseWebhookEvent(string $payload, ?string $signatureHeader): array
    {
        $secret = (string) config('services.stripe.webhook_secret', '');

        if ($secret !== '' && $signatureHeader !== null && $signatureHeader !== '') {
            try {
                $event = Webhook::constructEvent($payload, $signatureHeader, $secret);

                return json_decode(json_encode($event, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
            } catch (SignatureVerificationException $e) {
                throw new RuntimeException('Firma webhook Stripe non valida.', 0, $e);
            }
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        if (! isset($decoded['id'])) {
            $decoded['id'] = 'evt_stub_'.sha1($payload);
        }

        return $decoded;
    }
}
