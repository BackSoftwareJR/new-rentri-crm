<?php

namespace App\Domain\Dashboard;

use App\Domain\Mud\MudService;
use App\Domain\Rentri\RentriTransazioneService;
use App\Enums\RegistroMovimentoTipo;
use App\Enums\VfuStato;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use App\Models\VfuRegistration;
use Illuminate\Support\Carbon;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly RentriTransazioneService $rentriTransazioni,
        private readonly MudService $mud,
    ) {}

    /** @return list<string> */
    public function periodOptions(): array
    {
        return ['current_month', 'previous_month', 'last_3_months', 'last_6_months'];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function resolvePeriod(string $key): array
    {
        return match ($key) {
            'previous_month' => [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth(),
                'Mese precedente',
            ],
            'last_3_months' => [
                now()->subMonths(2)->startOfMonth(),
                now()->endOfDay(),
                'Ultimi 3 mesi',
            ],
            'last_6_months' => [
                now()->subMonths(5)->startOfMonth(),
                now()->endOfDay(),
                'Ultimi 6 mesi',
            ],
            default => [
                now()->startOfMonth(),
                now()->endOfDay(),
                'Mese corrente',
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function metricsForRange(Carbon $from, Carbon $to): array
    {
        $dateFilters = [
            'data_da' => $from->toDateString(),
            'data_a'  => $to->toDateString(),
        ];

        $movimentiQuery = RegistroMovimento::query()
            ->whereBetween('data_movimento', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        return [
            'periodo' => [
                'da' => $from->toDateString(),
                'a'  => $to->toDateString(),
            ],
            'vfu' => [
                'nuove_pratiche'       => VfuRegistration::query()
                    ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
                'bonifiche_pericolosi' => VfuRegistration::query()
                    ->whereNotNull('bonifica_pericolosi_completata_at')
                    ->whereBetween('bonifica_pericolosi_completata_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
                'rottamati'            => VfuRegistration::query()
                    ->where('stato', VfuStato::Rottamato)
                    ->whereBetween('updated_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
            ],
            'magazzino' => [
                'movimenti'   => (clone $movimentiQuery)->count(),
                'carichi_kg'  => (float) ((clone $movimentiQuery)->where('tipo', RegistroMovimentoTipo::Carico)->sum('peso_kg') ?? 0),
                'scarichi_kg' => (float) ((clone $movimentiQuery)->where('tipo', RegistroMovimentoTipo::Scarico)->sum('peso_kg') ?? 0),
                'trasmessi_rentri' => (clone $movimentiQuery)->where('rentri_trasmesso', true)->count(),
            ],
            'rentri' => $this->rentriTransazioni->contatori($dateFilters),
            'mud'    => [
                'create'     => MudDichiarazione::query()
                    ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
                'completate' => MudDichiarazione::query()
                    ->whereNotNull('completata_at')
                    ->whereBetween('completata_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
                'inviate'    => MudDichiarazione::query()
                    ->whereNotNull('inviata_at')
                    ->whereBetween('inviata_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                    ->count(),
                'totale'     => $this->mud->contatori()['totale'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function monthlyTrend(int $months = 6): array
    {
        $months = max(1, min(12, $months));
        $rows = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i)->startOfMonth();
            $metrics = $this->metricsForRange($month->copy(), $month->copy()->endOfMonth());

            $rows[] = [
                'month'                => $month->format('Y-m'),
                'label'                => $month->locale('it')->translatedFormat('M Y'),
                'vfu_nuove'            => $metrics['vfu']['nuove_pratiche'],
                'magazzino_movimenti'  => $metrics['magazzino']['movimenti'],
                'rentri_transazioni'   => $metrics['rentri']['totale'],
                'mud_inviate'          => $metrics['mud']['inviate'],
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function comparisonForPeriod(string $periodKey): array
    {
        [$from, $to, $label] = $this->resolvePeriod($periodKey);
        $current = $this->metricsForRange($from, $to);

        if (in_array($periodKey, ['current_month', 'previous_month'], true)) {
            $prevFrom = $from->copy()->subMonth()->startOfMonth();
            $prevTo = $from->copy()->subMonth()->endOfMonth();
            $previousLabel = $prevFrom->locale('it')->translatedFormat('M Y');
        } else {
            $days = (int) $from->diffInDays($to) + 1;
            $prevTo = $from->copy()->subDay()->endOfDay();
            $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();
            $previousLabel = $prevFrom->toDateString().' — '.$prevTo->toDateString();
        }

        $previous = $this->metricsForRange($prevFrom, $prevTo);

        return [
            'label'          => $label,
            'previous_label' => $previousLabel,
            'current'        => $current,
            'previous'       => $previous,
            'delta'          => $this->buildDeltas($current, $previous),
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, array{diff: int|float, pct: float, direction: string}>
     */
    private function buildDeltas(array $current, array $previous): array
    {
        return [
            'vfu_nuove'           => $this->delta((int) $current['vfu']['nuove_pratiche'], (int) $previous['vfu']['nuove_pratiche']),
            'magazzino_movimenti' => $this->delta((int) $current['magazzino']['movimenti'], (int) $previous['magazzino']['movimenti']),
            'rentri_transazioni'=> $this->delta((int) $current['rentri']['totale'], (int) $previous['rentri']['totale']),
            'mud_inviate'         => $this->delta((int) $current['mud']['inviate'], (int) $previous['mud']['inviate']),
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
