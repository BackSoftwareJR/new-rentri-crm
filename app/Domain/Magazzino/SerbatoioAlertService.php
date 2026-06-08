<?php

namespace App\Domain\Magazzino;

use Illuminate\Support\Collection;

class SerbatoioAlertService
{
    public function __construct(
        private MagazzinoService $magazzino,
    ) {}

    /**
     * @return array{in_attenzione: int, soglia_superata: int, totale_alert: int}
     */
    public function summary(): array
    {
        $rows = $this->alertRows();

        return [
            'in_attenzione'   => $rows->where('stato', 'attenzione')->count(),
            'soglia_superata' => $rows->where('stato', 'superata')->count(),
            'totale_alert'    => $rows->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function alertRows(): Collection
    {
        return $this->magazzino->listSerbatoi()
            ->filter(fn (array $row): bool => in_array($row['stato'], ['attenzione', 'superata'], true))
            ->values();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function recentAlerts(int $limit = 8): Collection
    {
        return $this->alertRows()
            ->sortByDesc(fn (array $row) => $row['percentuale'] ?? 0)
            ->take($limit)
            ->values();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function alertForCer(int $codiceCerId): ?array
    {
        $row = $this->magazzino->getSerbatoioDetail($codiceCerId);

        return in_array($row['stato'], ['attenzione', 'superata'], true) ? $row : null;
    }
}
