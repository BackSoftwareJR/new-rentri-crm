<?php

namespace App\Domain\Ecommerce;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\StripeDisputeAdminMail;
use App\Models\EcommerceOrdine;
use App\Models\StripeDispute;
use App\Support\Logging\StructuredLogService;
use Illuminate\Support\Carbon;

/**
 * Real Stripe dispute handler — activated when STRIPE_DISPUTE_STUB=false.
 *
 * Handles charge.dispute.created / .updated / .closed webhook events,
 * persists them into stripe_disputes, and notifies admin on creation.
 */
class StripeDisputeService
{
    public function __construct(
        private readonly StructuredLogService $logger,
        private readonly NotificationService $notifications,
        private readonly StripeProductionPreflightService $preflight,
    ) {}

    /**
     * charge.dispute.created — store new dispute, notify admin.
     *
     * @param  array<string, mixed>  $stripeEvent
     */
    public function handleDisputeCreated(array $stripeEvent): void
    {
        /** @var array<string, mixed> $disputeData */
        $disputeData = $stripeEvent['data']['object'] ?? [];

        $dispute = $this->upsertDispute($disputeData);

        $this->logger->warning('stripe', 'stripe.dispute.created', 'Dispute Stripe creata', [
            'entity_type' => 'StripeDispute',
            'entity_id'   => $dispute->id,
            'outcome'     => 'dispute_opened',
            'extra'       => [
                'dispute_id'      => $dispute->stripe_dispute_id,
                'amount'          => $dispute->amount,
                'reason'          => $dispute->reason,
                'status'          => $dispute->status,
                'evidence_due_by' => $dispute->evidence_due_by?->toIso8601String(),
                'environment'     => $this->preflight->isProductionEnvironment() ? 'production' : 'sandbox',
            ],
        ]);

        $this->notifyAdmin($dispute, 'charge.dispute.created');
    }

    /**
     * charge.dispute.updated — update existing dispute record.
     *
     * @param  array<string, mixed>  $stripeEvent
     */
    public function handleDisputeUpdated(array $stripeEvent): void
    {
        /** @var array<string, mixed> $disputeData */
        $disputeData = $stripeEvent['data']['object'] ?? [];

        $dispute = $this->upsertDispute($disputeData);

        $this->logger->info('stripe', 'stripe.dispute.updated', 'Dispute Stripe aggiornata', [
            'entity_type' => 'StripeDispute',
            'entity_id'   => $dispute->id,
            'extra'       => [
                'dispute_id' => $dispute->stripe_dispute_id,
                'status'     => $dispute->status,
            ],
        ]);
    }

    /**
     * charge.dispute.closed — record final outcome.
     *
     * @param  array<string, mixed>  $stripeEvent
     */
    public function handleDisputeClosed(array $stripeEvent): void
    {
        /** @var array<string, mixed> $disputeData */
        $disputeData = $stripeEvent['data']['object'] ?? [];

        $dispute = $this->upsertDispute($disputeData);

        $this->logger->info('stripe', 'stripe.dispute.closed', 'Dispute Stripe chiusa', [
            'entity_type' => 'StripeDispute',
            'entity_id'   => $dispute->id,
            'extra'       => [
                'dispute_id' => $dispute->stripe_dispute_id,
                'status'     => $dispute->status,
            ],
        ]);

        $this->notifyAdmin($dispute, 'charge.dispute.closed');
    }

    /** @return array{handled: bool, dispute_id: ?string, status: string} */
    public function handle(string $eventType, array $stripeEvent): array
    {
        match ($eventType) {
            'charge.dispute.created' => $this->handleDisputeCreated($stripeEvent),
            'charge.dispute.updated' => $this->handleDisputeUpdated($stripeEvent),
            'charge.dispute.closed'  => $this->handleDisputeClosed($stripeEvent),
            default                  => null,
        };

        /** @var array<string, mixed> $disputeData */
        $disputeData = $stripeEvent['data']['object'] ?? [];

        return [
            'handled'    => true,
            'dispute_id' => (string) ($disputeData['id'] ?? '') ?: null,
            'status'     => (string) ($disputeData['status'] ?? 'received'),
        ];
    }

    /** Persist or update a StripeDispute from a Stripe dispute object. */
    private function upsertDispute(array $disputeData): StripeDispute
    {
        $disputeId    = (string) ($disputeData['id'] ?? '');
        $paymentIntent = (string) ($disputeData['payment_intent'] ?? '');

        $ordineId = null;
        if ($paymentIntent !== '') {
            $ordine = EcommerceOrdine::query()
                ->where('stripe_checkout_session_id', 'like', '%')
                ->whereHas('stripeWebhookEvents', function ($q) use ($paymentIntent): void {
                    $q->where('checkout_session_id', $paymentIntent);
                })
                ->first();

            if ($ordine === null) {
                $ordine = EcommerceOrdine::query()
                    ->whereNotNull('stripe_checkout_session_id')
                    ->get()
                    ->first(function (EcommerceOrdine $o) use ($paymentIntent): bool {
                        $meta = (array) ($o->getAttributes()['metadata'] ?? []);

                        return isset($meta['payment_intent']) && $meta['payment_intent'] === $paymentIntent;
                    });
            }

            $ordineId = $ordine?->id;
        }

        $evidenceDueBy = null;
        if (isset($disputeData['evidence_details']['due_by'])) {
            $evidenceDueBy = Carbon::createFromTimestamp((int) $disputeData['evidence_details']['due_by']);
        }

        /** @var StripeDispute $dispute */
        $dispute = StripeDispute::query()->updateOrCreate(
            ['stripe_dispute_id' => $disputeId],
            [
                'ordine_id'       => $ordineId,
                'amount'          => (int) ($disputeData['amount'] ?? 0),
                'currency'        => strtolower((string) ($disputeData['currency'] ?? 'eur')),
                'reason'          => (string) ($disputeData['reason'] ?? ''),
                'status'          => (string) ($disputeData['status'] ?? 'unknown'),
                'evidence_due_by' => $evidenceDueBy,
                'metadata'        => $disputeData,
            ],
        );

        return $dispute;
    }

    private function notifyAdmin(StripeDispute $dispute, string $eventType): void
    {
        try {
            $this->notifications->dispatch(
                NotificationEvent::StripeDisputeCreated,
                new StripeDisputeAdminMail($dispute, $eventType),
            );
        } catch (\Throwable $e) {
            $this->logger->warning('stripe', 'stripe.dispute.notify_failed', 'Notifica dispute admin non inviata', [
                'extra' => ['error' => $e->getMessage()],
            ]);
        }
    }

    public function openDisputeCount(): int
    {
        return StripeDispute::query()->open()->count();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, StripeDispute> */
    public function openDisputes(int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return StripeDispute::query()
            ->open()
            ->with('ordine')
            ->orderBy('evidence_due_by')
            ->limit($limit)
            ->get();
    }
}
