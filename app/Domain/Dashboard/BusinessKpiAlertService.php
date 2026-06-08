<?php

namespace App\Domain\Dashboard;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\BusinessKpiBreachMail;
use Illuminate\Support\Facades\Cache;

/**
 * Alert email KPI business vs soglie configurabili (Sprint 119).
 */
class BusinessKpiAlertService
{
    public const CACHE_KEY_LAST_CHECK = 'kpi.business_check.last_result';

    public const ACTIVITY_DESCRIPTION_PREFIX = 'KPI business breach:';

    /** @var list<string> */
    public const PERIOD_OPTIONS = ['last_7_days', 'last_30_days'];

    public function __construct(
        private readonly BusinessKpiDashboardService $dashboard,
        private readonly NotificationService $notifications,
        private readonly ActivityLogService $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function check(string $periodKey = 'last_7_days', bool $notify = false): array
    {
        if (! in_array($periodKey, self::PERIOD_OPTIONS, true)) {
            $periodKey = (string) config('dashboard.business_kpi.alert.period_default', 'last_7_days');
        }

        $comparison = $this->dashboard->comparisonForPeriod($periodKey);
        $breaches = $this->detectBreaches($comparison);
        $checkedAt = now();

        $result = [
            'checked_at'  => $checkedAt->toIso8601String(),
            'period_key'  => $periodKey,
            'period_label' => $comparison['label'],
            'metrics'     => $this->flattenMetrics($comparison['current']),
            'thresholds'  => config('dashboard.business_kpi.thresholds', []),
            'breaches'    => $breaches,
            'overall'     => $this->overallStatus($breaches),
            'notified'    => false,
        ];

        if ($notify && (bool) config('dashboard.business_kpi.alert.enabled', true)) {
            $result['notified'] = $this->dispatchNotifications($breaches, $comparison, $periodKey);
        }

        foreach ($breaches as $breach) {
            if ($breach['status'] === 'alert') {
                $this->recordBreachActivity($breach, $comparison, $periodKey);
            }
        }

        Cache::put(self::CACHE_KEY_LAST_CHECK, $result, now()->addDays(7));

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
            ->where('log_name', 'dashboard')
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
     * @param  array<string, mixed>  $comparison
     * @return list<array{key: string, label: string, status: string, value: float, warn: float, alert: float, message: string}>
     */
    public function detectBreaches(array $comparison): array
    {
        $breaches = [];
        $labels = [
            'ordini_confermati' => 'Ordini e-commerce confermati',
            'vfu_accettate'     => 'VFU accettate',
            'magazzino_kg'      => 'Movimenti magazzino (kg)',
            'revenue_eur'       => 'Revenue (stub EUR)',
        ];

        foreach ($labels as $key => $label) {
            $status = $comparison['thresholds'][$key] ?? 'ok';

            if ($status === 'ok') {
                continue;
            }

            $value = $this->metricValueFromComparison($comparison['current'], $key);
            $thresholds = config("dashboard.business_kpi.thresholds.{$key}", []);
            $warn = (float) ($thresholds['warn'] ?? 0);
            $alert = (float) ($thresholds['alert'] ?? 0);

            $breaches[] = [
                'key'     => $key,
                'label'   => $label,
                'status'  => $status,
                'value'   => $value,
                'warn'    => $warn,
                'alert'   => $alert,
                'message' => $status === 'alert'
                    ? sprintf('%s: %.2f sotto soglia critica %.2f.', $label, $value, $alert)
                    : sprintf('%s: %.2f sotto soglia attenzione %.2f.', $label, $value, $warn),
            ];
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

        if (collect($breaches)->contains(fn (array $b) => $b['status'] === 'alert')) {
            return 'fail';
        }

        return 'warn';
    }

    /**
     * @param  list<array<string, mixed>>  $breaches
     * @param  array<string, mixed>  $comparison
     */
    private function dispatchNotifications(array $breaches, array $comparison, string $periodKey): bool
    {
        $alertBreaches = collect($breaches)->where('status', 'alert')->values()->all();

        if ($alertBreaches === []) {
            return false;
        }

        return $this->notifications->dispatch(
            NotificationEvent::BusinessKpiBreach,
            new BusinessKpiBreachMail($alertBreaches, $comparison, $periodKey),
            context: [
                'breach_count' => count($alertBreaches),
                'period_key'   => $periodKey,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $breach
     * @param  array<string, mixed>  $comparison
     */
    private function recordBreachActivity(array $breach, array $comparison, string $periodKey): void
    {
        $this->audit->record(
            'dashboard',
            self::ACTIVITY_DESCRIPTION_PREFIX.' '.$breach['label'],
            properties: [
                'event'        => 'kpi_business_breach',
                'key'          => $breach['key'],
                'status'       => $breach['status'],
                'value'        => $breach['value'],
                'warn'         => $breach['warn'],
                'alert'        => $breach['alert'],
                'message'      => $breach['message'],
                'period_key'   => $periodKey,
                'period_label' => $comparison['label'],
            ],
        );

        app(\App\Support\Logging\StructuredLogService::class)->warning(
            'business',
            'kpi_breach',
            'KPI business in breach: '.$breach['label'],
            [
                'outcome' => 'failure',
                'context' => [
                    'key'        => $breach['key'],
                    'value'      => $breach['value'],
                    'alert'      => $breach['alert'],
                    'period_key' => $periodKey,
                ],
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, float>
     */
    private function flattenMetrics(array $current): array
    {
        return [
            'ordini_confermati' => (float) $current['ecommerce']['ordini_confermati'],
            'vfu_accettate'     => (float) $current['vfu']['accettate'],
            'magazzino_kg'      => (float) $current['magazzino']['movimenti_kg'],
            'revenue_eur'       => (float) $current['ecommerce']['revenue_eur'],
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function metricValueFromComparison(array $metrics, string $key): float
    {
        return match ($key) {
            'ordini_confermati' => (float) $metrics['ecommerce']['ordini_confermati'],
            'vfu_accettate'     => (float) $metrics['vfu']['accettate'],
            'magazzino_kg'      => (float) $metrics['magazzino']['movimenti_kg'],
            'revenue_eur'       => (float) $metrics['ecommerce']['revenue_eur'],
            default             => 0.0,
        };
    }
}
