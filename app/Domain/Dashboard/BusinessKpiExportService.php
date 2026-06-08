<?php

namespace App\Domain\Dashboard;

/**
 * Export CSV metriche KPI business v3 (Sprint 119).
 */
class BusinessKpiExportService
{
    public function __construct(
        private readonly BusinessKpiDashboardService $dashboard,
    ) {}

    /** @var list<string> */
    private const METRIC_LABELS = [
        'ordini_confermati' => 'Ordini e-commerce confermati',
        'vfu_accettate'     => 'VFU accettate',
        'magazzino_kg'      => 'Movimenti magazzino (kg)',
        'revenue_eur'       => 'Revenue (stub ordini EUR)',
    ];

    public function toCsv(string $periodKey = 'last_7_days'): string
    {
        $comparison = $this->dashboard->comparisonForPeriod($periodKey);
        $lines = [
            'metrica,label,periodo,current,previous,delta,delta_pct,direction,threshold_status,warn_soglia,alert_soglia',
        ];

        foreach (self::METRIC_LABELS as $key => $label) {
            $current = $this->metricValue($comparison['current'], $key);
            $previous = $this->metricValue($comparison['previous'], $key);
            $delta = $comparison['delta'][$key];
            $thresholds = config("dashboard.business_kpi.thresholds.{$key}", []);

            $lines[] = implode(',', [
                $key,
                $this->escape($label),
                $this->escape($comparison['label']),
                $this->formatNumber($current),
                $this->formatNumber($previous),
                $this->formatNumber($delta['diff']),
                number_format((float) $delta['pct'], 1, '.', ''),
                $delta['direction'],
                $comparison['thresholds'][$key],
                (float) ($thresholds['warn'] ?? 0),
                (float) ($thresholds['alert'] ?? 0),
            ]);
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * @param  array<string, mixed>  $metrics
     */
    private function metricValue(array $metrics, string $key): float
    {
        return match ($key) {
            'ordini_confermati' => (float) $metrics['ecommerce']['ordini_confermati'],
            'vfu_accettate'     => (float) $metrics['vfu']['accettate'],
            'magazzino_kg'      => (float) $metrics['magazzino']['movimenti_kg'],
            'revenue_eur'       => (float) $metrics['ecommerce']['revenue_eur'],
            default             => 0.0,
        };
    }

    private function formatNumber(int|float $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }

    private function escape(string $value): string
    {
        if (str_contains($value, ',') || str_contains($value, '"')) {
            return '"'.str_replace('"', '""', $value).'"';
        }

        return $value;
    }
}
