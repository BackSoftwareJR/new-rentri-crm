<?php

namespace App\Domain\Ecommerce;

use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\StripeWebhookEvent;
use Illuminate\Support\Collection;

/**
 * Reporting riconciliazione pagamenti Stripe prod vs CRM (Sprint 117).
 */
class StripeReconciliationReportService
{
    public function daysWindow(): int
    {
        return max(1, (int) config('services.stripe.reconciliation_days', 30));
    }

    /**
     * @return array{
     *     days: int,
     *     ordini_stripe: int,
     *     webhook_events: int,
     *     matched: int,
     *     crm_only: int,
     *     stripe_only: int,
     *     amount_matched_eur: float,
     *     open_disputes: int
     * }
     */
    public function summary(?int $days = null): array
    {
        $days ??= $this->daysWindow();
        $rows = $this->rows($days);

        $matched = $rows->where('status', 'matched')->count();
        $crmOnly = $rows->where('status', 'crm_only')->count();
        $stripeOnly = $rows->where('status', 'stripe_only')->count();

        return [
            'days'               => $days,
            'ordini_stripe'      => $rows->whereNotNull('ordine_id')->count(),
            'webhook_events'     => StripeWebhookEvent::query()
                ->where('processed_at', '>=', now()->subDays($days))
                ->where('event_type', 'checkout.session.completed')
                ->count(),
            'matched'            => $matched,
            'crm_only'           => $crmOnly,
            'stripe_only'        => $stripeOnly,
            'amount_matched_eur' => round(
                (float) $rows->where('status', 'matched')->sum('amount_eur'),
                2,
            ),
            'open_disputes'      => app(StripeDisputeStubService::class)->openDisputeCount(),
        ];
    }

    /**
     * @return Collection<int, array{
     *     ordine_id: ?int,
     *     stripe_event_id: ?string,
     *     checkout_session_id: ?string,
     *     amount_eur: float,
     *     environment: ?string,
     *     status: string,
     *     ordine_stato: ?string,
     *     processed_at: ?string
     * }>
     */
    public function rows(?int $days = null): Collection
    {
        $days ??= $this->daysWindow();
        $since = now()->subDays($days);

        $ordini = EcommerceOrdine::query()
            ->where('payment_gateway', 'stripe')
            ->where('created_at', '>=', $since)
            ->whereIn('stato', [
                OrdineEcommerceStato::Confermato,
                OrdineEcommerceStato::PagamentoInAttesa,
            ])
            ->get(['id', 'stato', 'totale', 'stripe_checkout_session_id', 'created_at']);

        $events = StripeWebhookEvent::query()
            ->where('processed_at', '>=', $since)
            ->where('event_type', 'checkout.session.completed')
            ->get(['stripe_event_id', 'checkout_session_id', 'ecommerce_ordine_id', 'reconciliation', 'processed_at']);

        $eventsBySession = $events->keyBy('checkout_session_id');
        $matchedSessionIds = collect();

        $rows = $ordini->map(function (EcommerceOrdine $ordine) use ($eventsBySession, &$matchedSessionIds): array {
            $sessionId = (string) $ordine->stripe_checkout_session_id;
            $event = $sessionId !== '' ? $eventsBySession->get($sessionId) : null;

            if ($event !== null) {
                $matchedSessionIds->push($sessionId);
                $status = 'matched';
            } elseif ($ordine->stato === OrdineEcommerceStato::Confermato) {
                $status = 'crm_only';
            } else {
                $status = 'crm_pending';
            }

            /** @var array<string, mixed>|null $reconciliation */
            $reconciliation = $event?->reconciliation;

            return [
                'ordine_id'           => $ordine->id,
                'stripe_event_id'     => $event?->stripe_event_id,
                'checkout_session_id' => $sessionId !== '' ? $sessionId : null,
                'amount_eur'          => (float) $ordine->totale,
                'environment'         => is_array($reconciliation) ? ($reconciliation['environment'] ?? null) : null,
                'status'              => $status,
                'ordine_stato'        => $ordine->stato->value,
                'processed_at'        => $event?->processed_at?->toIso8601String(),
            ];
        });

        foreach ($events as $event) {
            if ($matchedSessionIds->contains($event->checkout_session_id)) {
                continue;
            }

            /** @var array<string, mixed>|null $reconciliation */
            $reconciliation = $event->reconciliation;

            $rows->push([
                'ordine_id'           => $event->ecommerce_ordine_id,
                'stripe_event_id'     => $event->stripe_event_id,
                'checkout_session_id' => $event->checkout_session_id,
                'amount_eur'          => is_array($reconciliation)
                    ? (float) ($reconciliation['amount_eur'] ?? 0)
                    : 0.0,
                'environment'         => is_array($reconciliation) ? ($reconciliation['environment'] ?? null) : null,
                'status'              => 'stripe_only',
                'ordine_stato'        => null,
                'processed_at'        => $event->processed_at?->toIso8601String(),
            ]);
        }

        return $rows->sortByDesc(fn (array $row): string => $row['processed_at'] ?? '')->values();
    }

    public function toCsv(?int $days = null): string
    {
        $days ??= $this->daysWindow();
        $lines = ['ordine_id,stripe_event_id,checkout_session_id,amount_eur,environment,status,ordine_stato,processed_at'];

        foreach ($this->rows($days) as $row) {
            $lines[] = implode(',', [
                $row['ordine_id'] ?? '',
                $row['stripe_event_id'] ?? '',
                $row['checkout_session_id'] ?? '',
                number_format($row['amount_eur'], 2, '.', ''),
                $row['environment'] ?? '',
                $row['status'],
                $row['ordine_stato'] ?? '',
                $row['processed_at'] ?? '',
            ]);
        }

        return implode("\n", $lines)."\n";
    }
}
