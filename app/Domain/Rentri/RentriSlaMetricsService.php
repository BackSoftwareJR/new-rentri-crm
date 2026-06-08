<?php

namespace App\Domain\Rentri;

use App\Models\RentriTransazione;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RentriSlaMetricsService
{
    /** @var list<string> */
    public const TIPI_SLA = ['fir', 'xfir', 'registro'];

    /** @var list<int> */
    public const PERIODI_GIORNI = [7, 30];

    public function __construct(
        private readonly RentriTransazioneService $transazioni,
    ) {}

    /**
     * @return array{
     *   periods: array<int, array<string, mixed>>,
     *   thresholds: array{p95_latency_seconds: int, dead_letter_rate_percent: float, max_avg_retry_count: float},
     *   selected_days: int
     * }
     */
    public function dashboard(int $selectedDays = 7): array
    {
        $selectedDays = in_array($selectedDays, self::PERIODI_GIORNI, true) ? $selectedDays : 7;

        $periods = [];
        foreach (self::PERIODI_GIORNI as $days) {
            $periods[$days] = $this->periodMetrics($days);
        }

        return [
            'periods'       => $periods,
            'thresholds'    => $this->thresholds(),
            'selected_days' => $selectedDays,
            'selected'      => $periods[$selectedDays],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function periodMetrics(int $days): array
    {
        $from = now()->subDays($days)->startOfDay();
        $base = $this->periodQuery($from);

        $totale = (clone $base)->count();
        $completate = (clone $base)->where('stato', 'completata')->count();
        $deadLetterCount = (clone $base)->whereNotNull('dead_letter_at')->count();

        $latency = $this->latencyMetrics(clone $base);
        $retry = $this->retryMetrics(clone $base);

        $byTipo = [];
        foreach (self::TIPI_SLA as $tipo) {
            $tipoQuery = (clone $base)->where('tipo_api', $tipo);
            $tipoTotale = (clone $tipoQuery)->count();
            $tipoDeadLetter = (clone $tipoQuery)->whereNotNull('dead_letter_at')->count();

            $byTipo[$tipo] = [
                'label'            => $this->transazioni->tipoApiLabel($tipo),
                'totale'           => $tipoTotale,
                'completate'       => (clone $tipoQuery)->where('stato', 'completata')->count(),
                'latency'          => $this->latencyMetrics(clone $tipoQuery),
                'retry'            => $this->retryMetrics(clone $tipoQuery),
                'dead_letter'      => [
                    'count'        => $tipoDeadLetter,
                    'rate_percent' => $this->ratePercent($tipoDeadLetter, $tipoTotale),
                ],
                'sla'              => $this->evaluateSla(
                    $this->latencyMetrics(clone $tipoQuery),
                    $this->retryMetrics(clone $tipoQuery),
                    $tipoDeadLetter,
                    $tipoTotale,
                ),
            ];
        }

        $metrics = [
            'period_days'  => $days,
            'period_from'  => $from->toDateString(),
            'period_to'    => now()->toDateString(),
            'totale'       => $totale,
            'completate'   => $completate,
            'latency'      => $latency,
            'retry'        => $retry,
            'dead_letter'  => [
                'count'        => $deadLetterCount,
                'rate_percent' => $this->ratePercent($deadLetterCount, $totale),
            ],
            'by_tipo'      => $byTipo,
        ];

        $metrics['sla'] = $this->evaluateSla($latency, $retry, $deadLetterCount, $totale);

        return $metrics;
    }

    /**
     * @return array{p95_latency_seconds: int, dead_letter_rate_percent: float, max_avg_retry_count: float}
     */
    public function thresholds(): array
    {
        $sla = config('services.rentri.sla', []);

        return [
            'p95_latency_seconds'        => (int) ($sla['p95_latency_seconds'] ?? 120),
            'dead_letter_rate_percent'   => (float) ($sla['dead_letter_rate_percent'] ?? 5.0),
            'max_avg_retry_count'        => (float) ($sla['max_avg_retry_count'] ?? 1.0),
        ];
    }

    /**
     * @return array{p95_latency: string, dead_letter_rate: string, avg_retry: string, overall: string}
     */
    public function evaluateSla(array $latency, array $retry, int $deadLetterCount, int $totale): array
    {
        $thresholds = $this->thresholds();

        $p95 = $latency['p95_seconds'];
        $deadLetterRate = $this->ratePercent($deadLetterCount, $totale);
        $avgRetry = $retry['avg_count'];

        $badges = [
            'p95_latency'      => $this->evaluateLowerIsBetter($p95, (float) $thresholds['p95_latency_seconds']),
            'dead_letter_rate' => $this->evaluateLowerIsBetter($deadLetterRate, $thresholds['dead_letter_rate_percent']),
            'avg_retry'        => $this->evaluateLowerIsBetter($avgRetry, $thresholds['max_avg_retry_count']),
        ];

        $badges['overall'] = $this->worstBadge($badges);

        return $badges;
    }

    public function slaBadgeLabel(string $status): string
    {
        return match ($status) {
            'ok'   => 'SLA OK',
            'warn' => 'SLA attenzione',
            'fail' => 'SLA fuori soglia',
            default => 'SLA —',
        };
    }

    private function periodQuery(Carbon $from): Builder
    {
        return RentriTransazione::query()->where('created_at', '>=', $from);
    }

    /**
     * @return array{avg_seconds: float|null, p50_seconds: float|null, p95_seconds: float|null, sample_size: int}
     */
    private function latencyMetrics(Builder $query): array
    {
        $rows = (clone $query)
            ->where('stato', 'completata')
            ->whereNotNull('completed_at')
            ->get(['created_at', 'completed_at']);

        if ($rows->isEmpty()) {
            return [
                'avg_seconds'  => null,
                'p50_seconds'  => null,
                'p95_seconds'  => null,
                'sample_size'  => 0,
            ];
        }

        /** @var Collection<int, float> $seconds */
        $seconds = $rows
            ->map(fn (RentriTransazione $row) => (float) $row->created_at->diffInSeconds($row->completed_at))
            ->sort()
            ->values();

        return [
            'avg_seconds' => round($seconds->avg(), 1),
            'p50_seconds' => $this->percentile($seconds, 50),
            'p95_seconds' => $this->percentile($seconds, 95),
            'sample_size' => $seconds->count(),
        ];
    }

    /**
     * @return array{avg_count: float, with_retry: int, total_retries: int}
     */
    private function retryMetrics(Builder $query): array
    {
        $totale = (clone $query)->count();

        if ($totale === 0) {
            return [
                'avg_count'     => 0.0,
                'with_retry'    => 0,
                'total_retries' => 0,
            ];
        }

        $totalRetries = (int) (clone $query)->sum('retry_count');
        $withRetry = (clone $query)->where('retry_count', '>', 0)->count();

        return [
            'avg_count'     => round($totalRetries / $totale, 2),
            'with_retry'    => $withRetry,
            'total_retries' => $totalRetries,
        ];
    }

    private function ratePercent(int $part, int $whole): float
    {
        if ($whole === 0) {
            return 0.0;
        }

        return round(($part / $whole) * 100, 2);
    }

    /**
     * @param  Collection<int, float>  $sorted
     */
    private function percentile(Collection $sorted, float $percentile): ?float
    {
        if ($sorted->isEmpty()) {
            return null;
        }

        $index = (int) ceil(($percentile / 100) * $sorted->count()) - 1;
        $index = max(0, min($index, $sorted->count() - 1));

        return round((float) $sorted->get($index), 1);
    }

    private function evaluateLowerIsBetter(?float $value, float $threshold): string
    {
        if ($value === null) {
            return 'ok';
        }

        if ($value <= $threshold * 0.8) {
            return 'ok';
        }

        if ($value <= $threshold) {
            return 'warn';
        }

        return 'fail';
    }

    /**
     * @param  array<string, string>  $badges
     */
    private function worstBadge(array $badges): string
    {
        unset($badges['overall']);

        if (in_array('fail', $badges, true)) {
            return 'fail';
        }

        if (in_array('warn', $badges, true)) {
            return 'warn';
        }

        return 'ok';
    }
}
