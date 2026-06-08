<?php

namespace App\Domain\Dashboard;

use App\Enums\OrdineEcommerceStato;
use App\Models\EcommerceOrdine;
use App\Models\Fattura;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;
use Illuminate\Support\Carbon;

class BusinessKpiDashboardService
{
    /** @return list<string> */
    public function periodOptions(): array
    {
        return ['last_7_days', 'last_30_days'];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function resolvePeriod(string $key): array
    {
        return match ($key) {
            'last_30_days' => [
                now()->subDays(29)->startOfDay(),
                now()->endOfDay(),
                'Ultimi 30 giorni',
            ],
            default => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                'Ultimi 7 giorni',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboard(int $days = 7): array
    {
        $key = $days === 30 ? 'last_30_days' : 'last_7_days';

        return $this->comparisonForPeriod($key);
    }

    /**
     * @return array<string, mixed>
     */
    public function metricsForRange(Carbon $from, Carbon $to): array
    {
        $fromDay = $from->copy()->startOfDay();
        $toDay = $to->copy()->endOfDay();

        $ordiniQuery = EcommerceOrdine::query()
            ->where('stato', OrdineEcommerceStato::Confermato)
            ->whereBetween('confermato_at', [$fromDay, $toDay]);

        $movimentiQuery = RegistroMovimento::query()
            ->whereBetween('data_movimento', [$fromDay, $toDay]);

        $magazzinoKg = (float) ((clone $movimentiQuery)->sum('peso_kg') ?? 0);
        $revenueEur = (float) Fattura::query()
            ->where('stato', 'pagata')
            ->whereDate('data_pagamento', '>=', $fromDay)
            ->whereDate('data_pagamento', '<=', $toDay)
            ->sum('totale');

        return [
            'periodo' => [
                'da' => $from->toDateString(),
                'a'  => $to->toDateString(),
            ],
            'ecommerce' => [
                'ordini_confermati' => (clone $ordiniQuery)->count(),
                'revenue_eur'       => $revenueEur,
            ],
            'vfu' => [
                'accettate' => VfuRegistration::query()
                    ->whereNotNull('data_accettazione')
                    ->whereDate('data_accettazione', '>=', $fromDay)
                    ->whereDate('data_accettazione', '<=', $toDay)
                    ->count(),
            ],
            'magazzino' => [
                'movimenti_kg' => $magazzinoKg,
                'movimenti'    => (clone $movimentiQuery)->count(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function comparisonForPeriod(string $periodKey): array
    {
        [$from, $to, $label] = $this->resolvePeriod($periodKey);
        $current = $this->metricsForRange($from, $to);

        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();
        $previousLabel = $prevFrom->toDateString().' — '.$prevTo->toDateString();

        $previous = $this->metricsForRange($prevFrom, $prevTo);

        return [
            'label'          => $label,
            'previous_label' => $previousLabel,
            'days'           => $days,
            'current'        => $current,
            'previous'       => $previous,
            'delta'          => $this->buildDeltas($current, $previous),
            'thresholds'     => $this->thresholdStatuses($current),
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, string>
     */
    public function thresholdStatuses(array $metrics): array
    {
        return [
            'ordini_confermati' => $this->thresholdStatus(
                'ordini_confermati',
                (int) $metrics['ecommerce']['ordini_confermati'],
            ),
            'vfu_accettate' => $this->thresholdStatus(
                'vfu_accettate',
                (int) $metrics['vfu']['accettate'],
            ),
            'magazzino_kg' => $this->thresholdStatus(
                'magazzino_kg',
                (float) $metrics['magazzino']['movimenti_kg'],
            ),
            'revenue_eur' => $this->thresholdStatus(
                'revenue_eur',
                (float) $metrics['ecommerce']['revenue_eur'],
            ),
        ];
    }

    public function thresholdStatus(string $metricKey, int|float $value): string
    {
        $thresholds = config("dashboard.business_kpi.thresholds.{$metricKey}", []);

        $alert = (float) ($thresholds['alert'] ?? 0);
        $warn = (float) ($thresholds['warn'] ?? 0);

        if ($value <= $alert) {
            return 'alert';
        }

        if ($value <= $warn) {
            return 'warn';
        }

        return 'ok';
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, array{diff: int|float, pct: float, direction: string}>
     */
    private function buildDeltas(array $current, array $previous): array
    {
        return [
            'ordini_confermati' => $this->delta(
                (int) $current['ecommerce']['ordini_confermati'],
                (int) $previous['ecommerce']['ordini_confermati'],
            ),
            'vfu_accettate' => $this->delta(
                (int) $current['vfu']['accettate'],
                (int) $previous['vfu']['accettate'],
            ),
            'magazzino_kg' => $this->delta(
                (float) $current['magazzino']['movimenti_kg'],
                (float) $previous['magazzino']['movimenti_kg'],
            ),
            'revenue_eur' => $this->delta(
                (float) $current['ecommerce']['revenue_eur'],
                (float) $previous['ecommerce']['revenue_eur'],
            ),
        ];
    }

    /**
     * @return array{diff: int|float, pct: float, direction: string}
     */
    private function delta(int|float $current, int|float $previous): array
    {
        $diff = $current - $previous;
        $pct = $previous > 0
            ? round(($diff / $previous) * 100, 1)
            : ($current > 0 ? 100.0 : 0.0);

        $direction = match (true) {
            $diff > 0 => 'up',
            $diff < 0 => 'down',
            default   => 'flat',
        };

        return ['diff' => $diff, 'pct' => $pct, 'direction' => $direction];
    }
}
