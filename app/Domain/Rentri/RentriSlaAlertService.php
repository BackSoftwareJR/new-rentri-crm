<?php

namespace App\Domain\Rentri;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\RentriDeadLetterMail;
use App\Mail\RentriSlaBreachMail;
use App\Models\RentriTransazione;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RentriSlaAlertService
{
    public const CACHE_KEY_LAST_CHECK = 'rentri.sla_check.last_result';

    public const CACHE_KEY_LAST_RUN_AT = 'rentri.sla_check.last_run_at';

    public const ACTIVITY_DESCRIPTION_PREFIX = 'SLA breach:';

    public function __construct(
        private readonly RentriSlaMetricsService $slaMetrics,
        private readonly NotificationService $notifications,
        private readonly ActivityLogService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function check(int $periodDays = 7, bool $notify = false): array
    {
        $periodDays = in_array($periodDays, RentriSlaMetricsService::PERIODI_GIORNI, true) ? $periodDays : 7;
        $metrics = $this->slaMetrics->periodMetrics($periodDays);
        $thresholds = $this->slaMetrics->thresholds();
        $breaches = $this->detectBreaches($metrics, $thresholds);

        $previousRunAt = Cache::get(self::CACHE_KEY_LAST_RUN_AT);
        $checkedAt = now();

        $result = [
            'checked_at'   => $checkedAt->toIso8601String(),
            'period_days'  => $periodDays,
            'metrics'      => [
                'totale'              => $metrics['totale'],
                'p95_seconds'         => $metrics['latency']['p95_seconds'],
                'dead_letter_count'   => $metrics['dead_letter']['count'],
                'dead_letter_rate'    => $metrics['dead_letter']['rate_percent'],
                'avg_retry'           => $metrics['retry']['avg_count'],
                'sla'                 => $metrics['sla'],
            ],
            'thresholds'   => $thresholds,
            'breaches'     => $breaches,
            'overall'      => $this->overallStatus($breaches),
            'notified'     => false,
            'new_dead_letters_notified' => 0,
        ];

        if ($notify) {
            $result['notified'] = $this->dispatchNotifications($breaches, $metrics, $periodDays);
            $result['new_dead_letters_notified'] = $this->notifyNewDeadLettersSince($previousRunAt);
        }

        foreach ($breaches as $breach) {
            if ($breach['status'] === 'fail') {
                $this->recordBreachActivity($breach, $metrics, $periodDays);
            }
        }

        Cache::put(self::CACHE_KEY_LAST_CHECK, $result, now()->addDays(7));
        Cache::put(self::CACHE_KEY_LAST_RUN_AT, $checkedAt->toIso8601String(), now()->addDays(30));

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function lastCheck(): ?array
    {
        $cached = Cache::get(self::CACHE_KEY_LAST_CHECK);

        return is_array($cached) ? $cached : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentBreaches(int $limit = 5): array
    {
        if (! config('activitylog.enabled')) {
            return [];
        }

        return \Spatie\Activitylog\Models\Activity::query()
            ->where('log_name', 'rentri')
            ->where('description', 'like', self::ACTIVITY_DESCRIPTION_PREFIX.'%')
            ->orderByDesc('created_at')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->map(fn ($activity) => [
                'id'          => $activity->id,
                'description' => (string) $activity->description,
                'created_at'  => $activity->created_at?->format('d/m/Y H:i'),
                'properties'  => $activity->properties?->toArray() ?? [],
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return list<array{key: string, label: string, status: string, value: float|null, threshold: float, message: string}>
     */
    public function detectBreaches(array $metrics, array $thresholds): array
    {
        $breaches = [];

        $p95 = $metrics['latency']['p95_seconds'];
        if ($metrics['sla']['p95_latency'] !== 'ok') {
            $breaches[] = $this->breach(
                'p95_latency',
                'P95 latency',
                $metrics['sla']['p95_latency'],
                $p95 !== null ? (float) $p95 : null,
                (float) $thresholds['p95_latency_seconds'],
                $p95 !== null
                    ? sprintf('P95 %.1f s supera soglia %d s.', $p95, $thresholds['p95_latency_seconds'])
                    : 'Campione latency insufficiente.',
            );
        }

        $deadLetterRate = (float) $metrics['dead_letter']['rate_percent'];
        if ($metrics['sla']['dead_letter_rate'] !== 'ok') {
            $breaches[] = $this->breach(
                'dead_letter_rate',
                'Dead-letter rate',
                $metrics['sla']['dead_letter_rate'],
                $deadLetterRate,
                (float) $thresholds['dead_letter_rate_percent'],
                sprintf(
                    'Dead-letter %.2f%% supera soglia %.2f%% (%d/%d).',
                    $deadLetterRate,
                    $thresholds['dead_letter_rate_percent'],
                    $metrics['dead_letter']['count'],
                    $metrics['totale'],
                ),
            );
        }

        return $breaches;
    }

    /**
     * @param  list<array<string, mixed>>  $breaches
     */
    private function overallStatus(array $breaches): string
    {
        if ($breaches === []) {
            return 'ok';
        }

        if (collect($breaches)->contains(fn (array $b) => $b['status'] === 'fail')) {
            return 'fail';
        }

        return 'warn';
    }

    /**
     * @param  list<array<string, mixed>>  $breaches
     * @param  array<string, mixed>  $metrics
     */
    private function dispatchNotifications(array $breaches, array $metrics, int $periodDays): bool
    {
        $failBreaches = collect($breaches)->where('status', 'fail')->values()->all();

        if ($failBreaches === []) {
            return false;
        }

        return $this->notifications->dispatch(
            NotificationEvent::RentriSlaBreach,
            new RentriSlaBreachMail($failBreaches, $metrics, $periodDays),
            context: [
                'breach_count' => count($failBreaches),
                'period_days'  => $periodDays,
            ],
        );
    }

    private function notifyNewDeadLettersSince(?string $previousRunAt): int
    {
        $query = RentriTransazione::query()->whereNotNull('dead_letter_at');

        if (filled($previousRunAt)) {
            try {
                $query->where('dead_letter_at', '>', Carbon::parse($previousRunAt));
            } catch (\Throwable) {
                // ignore invalid cached timestamp
            }
        }

        $count = 0;

        foreach ($query->orderByDesc('dead_letter_at')->limit(10)->get() as $transazione) {
            $errore = (string) ($transazione->response_json['message'] ?? $transazione->response_json['error'] ?? 'Dead-letter');
            $codice = isset($transazione->response_json['codice'])
                ? (string) $transazione->response_json['codice']
                : null;

            if ($this->notifications->dispatch(
                NotificationEvent::RentriDeadLetter,
                new RentriDeadLetterMail($transazione->id, $errore, $codice),
                context: ['transazione_id' => $transazione->id, 'source' => 'sla_check'],
            )) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $breach
     * @param  array<string, mixed>  $metrics
     */
    private function recordBreachActivity(array $breach, array $metrics, int $periodDays): void
    {
        $this->audit->record(
            'rentri',
            self::ACTIVITY_DESCRIPTION_PREFIX.' '.$breach['label'],
            properties: [
                'event'        => 'sla_breach',
                'key'          => $breach['key'],
                'status'       => $breach['status'],
                'value'        => $breach['value'],
                'threshold'    => $breach['threshold'],
                'message'      => $breach['message'],
                'period_days'  => $periodDays,
                'totale'       => $metrics['totale'],
            ],
        );

        app(\App\Support\Logging\StructuredLogService::class)->warning(
            'business',
            'sla_breach',
            'SLA RENTRI in breach: '.$breach['label'],
            [
                'outcome' => 'failure',
                'context' => [
                    'key'         => $breach['key'],
                    'value'       => $breach['value'],
                    'threshold'   => $breach['threshold'],
                    'period_days' => $periodDays,
                ],
            ],
        );
    }

    /**
     * @return array{key: string, label: string, status: string, value: float|null, threshold: float, message: string}
     */
    private function breach(
        string $key,
        string $label,
        string $status,
        ?float $value,
        float $threshold,
        string $message,
    ): array {
        return [
            'key'       => $key,
            'label'     => $label,
            'status'    => $status,
            'value'     => $value,
            'threshold' => $threshold,
            'message'   => $message,
        ];
    }
}
