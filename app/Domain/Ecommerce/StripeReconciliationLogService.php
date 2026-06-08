<?php

namespace App\Domain\Ecommerce;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\EcommerceStripeReconciliationMail;
use App\Models\EcommerceOrdine;
use App\Models\StripeWebhookEvent;
use App\Support\Logging\StructuredLogService;

class StripeReconciliationLogService
{
    public function __construct(private readonly StructuredLogService $logger) {}

    /**
     * @param  array<string, mixed>  $reconciliation
     */
    public function log(StripeWebhookEvent $record, ?EcommerceOrdine $ordine, array $reconciliation): void
    {
        $this->logger->info('stripe', 'stripe.reconciliation', 'Riconciliazione Stripe webhook elaborata', [
            'entity_type' => 'StripeWebhookEvent',
            'entity_id'   => $record->stripe_event_id,
            'extra'       => [
                'event_type'  => $record->event_type,
                'ordine_id'   => $ordine?->id,
                'session_id'  => $record->checkout_session_id,
                'environment' => $reconciliation['environment'] ?? null,
                'amount_eur'  => $reconciliation['amount_eur'] ?? null,
                'duplicate'   => false,
            ],
        ]);

        if ($ordine !== null) {
            app(NotificationService::class)->dispatch(
                NotificationEvent::EcommerceStripeReconciliation,
                new EcommerceStripeReconciliationMail($ordine, $record, $reconciliation),
                context: [
                    'ordine_id'       => $ordine->id,
                    'stripe_event_id' => $record->stripe_event_id,
                ],
            );
        }
    }

    public function logDuplicate(string $stripeEventId, string $eventType): void
    {
        $this->logger->info('stripe', 'stripe.reconciliation.duplicate', 'Evento Stripe duplicato ignorato', [
            'entity_id' => $stripeEventId,
            'extra'     => [
                'event_type' => $eventType,
                'duplicate'  => true,
            ],
        ]);
    }
}
