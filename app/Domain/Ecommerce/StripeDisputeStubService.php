<?php

namespace App\Domain\Ecommerce;

use App\Models\StripeWebhookEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Workflow dispute Stripe — stub prep produzione (Sprint 117).
 */
class StripeDisputeStubService
{
    /** @var list<string> */
    public const DISPUTE_EVENT_TYPES = [
        'charge.dispute.created',
        'charge.dispute.updated',
        'charge.dispute.closed',
    ];

    public function isStubEnabled(): bool
    {
        return (bool) config('services.stripe.dispute_stub', true);
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool}>
     */
    public function checklist(): array
    {
        $disputeSecret = (string) config('services.stripe.dispute_webhook_secret', '');
        $stub = $this->isStubEnabled();

        return [
            [
                'key'      => 'webhook_endpoint',
                'label'    => 'Endpoint webhook checkout ('.$this->webhookEndpointUrl().')',
                'ok'       => Route::has('webhooks.stripe.ecommerce') && filled(config('app.url')),
                'hint'     => 'Registrare POST su Stripe Dashboard → Developers → Webhooks.',
                'optional' => false,
            ],
            [
                'key'      => 'dispute_webhook_secret',
                'label'    => 'Webhook dispute (STRIPE_DISPUTE_WEBHOOK_SECRET)',
                'ok'       => $stub || $disputeSecret !== '',
                'hint'     => $stub
                    ? 'Dispute stub attivo — secret opzionale fino a go-live dispute.'
                    : 'Endpoint separato o stesso secret per charge.dispute.*.',
                'optional' => $stub,
            ],
            [
                'key'      => 'dispute_stub_mode',
                'label'    => 'Dispute stub attivo (STRIPE_DISPUTE_STUB=true)',
                'ok'       => $stub,
                'hint'     => 'Disattivare in produzione quando dispute live abilitate.',
                'optional' => true,
            ],
        ];
    }

    public function webhookEndpointUrl(): string
    {
        return url('/webhooks/stripe/ecommerce');
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function workflowSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Ricezione webhook charge.dispute.created',
                'detail' => 'Stripe notifica contestazione — log + record in stripe_webhook_events.',
            ],
            [
                'step'   => 2,
                'action' => 'Verifica ordine CRM',
                'detail' => 'Match payment_intent / checkout_session_id con ecommerce_ordini.',
            ],
            [
                'step'   => 3,
                'action' => 'Notifica segreteria',
                'detail' => 'Alert hub e-commerce + email (futuro Sprint) — stub: solo log notifications.',
            ],
            [
                'step'   => 4,
                'action' => 'Risposta evidence (stub)',
                'detail' => 'STRIPE_DISPUTE_STUB=true — nessuna submit evidence; documentare su Dashboard Stripe.',
            ],
            [
                'step'   => 5,
                'action' => 'Chiusura charge.dispute.closed',
                'detail' => 'Aggiornare stato dispute in CRM report reconciliation.',
            ],
        ];
    }

    public function isDisputeEvent(string $eventType): bool
    {
        return in_array($eventType, self::DISPUTE_EVENT_TYPES, true);
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array{handled: bool, dispute_id: ?string, status: string}
     */
    public function handleDisputeEvent(array $event): array
    {
        $eventId = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');
        /** @var array<string, mixed> $dispute */
        $dispute = $event['data']['object'] ?? [];
        $disputeId = (string) ($dispute['id'] ?? '');

        Log::channel('notifications')->info('stripe.dispute.stub', [
            'stripe_event_id' => $eventId,
            'event_type'      => $eventType,
            'dispute_id'      => $disputeId,
            'status'          => $dispute['status'] ?? null,
            'amount'          => $dispute['amount'] ?? null,
            'stub_mode'       => $this->isStubEnabled(),
        ]);

        if ($eventId !== '') {
            StripeWebhookEvent::query()->updateOrCreate(
                ['stripe_event_id' => $eventId],
                [
                    'event_type'          => $eventType,
                    'checkout_session_id' => (string) ($dispute['payment_intent'] ?? ''),
                    'reconciliation'      => [
                        'type'       => 'dispute_stub',
                        'dispute_id' => $disputeId,
                        'status'     => $dispute['status'] ?? 'unknown',
                        'reason'     => $dispute['reason'] ?? null,
                        'environment' => app(StripeProductionPreflightService::class)->isProductionEnvironment()
                            ? 'production'
                            : 'sandbox',
                    ],
                    'processed_at' => now(),
                ],
            );
        }

        return [
            'handled'    => true,
            'dispute_id' => $disputeId !== '' ? $disputeId : null,
            'status'     => (string) ($dispute['status'] ?? 'received'),
        ];
    }

    public function openDisputeCount(): int
    {
        return StripeWebhookEvent::query()
            ->where('event_type', 'charge.dispute.created')
            ->whereNull('ecommerce_ordine_id')
            ->count();
    }
}
