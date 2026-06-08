<?php

namespace App\Domain\Magazzino;

use Illuminate\Support\Collection;

class SerbatoioAlertService
{
    public function __construct(
        private MagazzinoService $magazzino,
    ) {}

    /**
     * @return array{
     *   in_attenzione: int,
     *   soglia_superata: int,
     *   sotto_soglia_minima: int,
     *   totale_alert: int
     * }
     */
    public function summary(): array
    {
        $rows = $this->alertRows();
        $sottoMinimo = $this->giacenzeSottoMinimo();

        return [
            'in_attenzione'       => $rows->where('stato', 'attenzione')->count(),
            'soglia_superata'     => $rows->where('stato', 'superata')->count(),
            'sotto_soglia_minima' => $sottoMinimo->count(),
            'totale_alert'        => $rows->count() + $sottoMinimo->count(),
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

        if (in_array($row['stato'], ['attenzione', 'superata'], true)) {
            return $row;
        }

        return ($row['sotto_soglia_minima'] ?? false) ? $row : null;
    }

    /**
     * Serbatoi con giacenza sotto la soglia minima configurata.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function giacenzeSottoMinimo(): Collection
    {
        return $this->magazzino->listSerbatoi()
            ->filter(fn (array $row): bool => (bool) ($row['sotto_soglia_minima'] ?? false))
            ->values();
    }
}
