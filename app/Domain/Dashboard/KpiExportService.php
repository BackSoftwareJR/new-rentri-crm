<?php

namespace App\Domain\Dashboard;

use Symfony\Component\HttpFoundation\StreamedResponse;

class KpiExportService
{
    public function __construct(
        private readonly DashboardAnalyticsService $analytics,
    ) {}

    public function exportMonthlyTrend(int $months = 6): StreamedResponse
    {
        $rows = $this->analytics->monthlyTrend($months);

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'mese',
                'vfu_nuove_pratiche',
                'magazzino_movimenti',
                'rentri_transazioni',
                'mud_inviate',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['month'],
                    (string) $row['vfu_nuove'],
                    (string) $row['magazzino_movimenti'],
                    (string) $row['rentri_transazioni'],
                    (string) $row['mud_inviate'],
                ], ';');
            }

            fclose($out);
        }, $this->filename($months), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<array<string, string>>
     */
    public function rowsForMonthlyTrend(int $months = 6): array
    {
        return array_map(
            fn (array $row): array => [
                'mese'                 => $row['month'],
                'vfu_nuove_pratiche'   => (string) $row['vfu_nuove'],
                'magazzino_movimenti'  => (string) $row['magazzino_movimenti'],
                'rentri_transazioni'   => (string) $row['rentri_transazioni'],
                'mud_inviate'          => (string) $row['mud_inviate'],
            ],
            $this->analytics->monthlyTrend($months),
        );
    }

    private function filename(int $months): string
    {
        return 'kpi-mensile-'.$months.'m-'.now()->format('Y-m-d_His').'.csv';
    }
}
