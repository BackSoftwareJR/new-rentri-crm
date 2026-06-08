<?php

namespace App\Domain\Ecommerce;

use App\Models\EcommerceOrdine;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Log;

/**
 * Idempotenza webhook Stripe + log riconciliazione.
 */
class StripeWebhookIdempotencyService
{
    public function __construct(
        private StripeReconciliationLogService $reconciliation,
    ) {}

    public function alreadyProcessed(string $stripeEventId): bool
    {
        if ($stripeEventId === '') {
            return false;
        }

        return StripeWebhookEvent::query()
            ->where('stripe_event_id', $stripeEventId)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $event
     * @param  array<string, mixed>  $reconciliation
     */
    public function recordProcessed(
        array $event,
        ?EcommerceOrdine $ordine,
        array $reconciliation,
    ): StripeWebhookEvent {
        $record = StripeWebhookEvent::create([
            'stripe_event_id'      => (string) ($event['id'] ?? ''),
            'event_type'           => (string) ($event['type'] ?? ''),
            'ecommerce_ordine_id'  => $ordine?->id,
            'checkout_session_id'  => (string) ($reconciliation['checkout_session_id'] ?? ''),
            'reconciliation'       => $reconciliation,
            'processed_at'         => now(),
        ]);

        $this->reconciliation->log($record, $ordine, $reconciliation);

        return $record;
    }
}
