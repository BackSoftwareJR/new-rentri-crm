<?php

namespace App\Domain\Vfu;

use App\Models\VfuRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VfuStoricoExportService
{
    public function __construct(
        private VfuTimelineService $timeline,
        private VfuAccettazioneService $accettazione,
    ) {}

    /**
     * @return list<array<string, string|null>>
     */
    public function rowsFor(VfuRegistration $vfu): array
    {
        $steps = $this->timeline->steps($vfu);

        return collect($steps)
            ->map(fn (array $step): array => [
                'vfu_id'         => (string) $vfu->id,
                'targa'          => $vfu->targa,
                'telaio'         => $vfu->telaio,
                'stato_corrente' => $vfu->stato->label(),
                'step_key'       => $step['key'],
                'step_label'     => $step['label'],
                'step_status'    => $step['status'],
                'step_date'      => $step['date'],
                'hint'           => $step['hint'],
            ])
            ->all();
    }

    /**
     * @param  array{search?: string, stato?: string, from?: string, to?: string}  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->accettazione->query($filters);

        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        return $query;
    }

    /**
     * @param  Collection<int, VfuRegistration>|iterable<VfuRegistration>  $registrations
     */
    public function exportCsv(iterable $registrations): StreamedResponse
    {
        return response()->streamDownload(function () use ($registrations) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'vfu_id',
                'targa',
                'telaio',
                'stato_corrente',
                'step_key',
                'step_label',
                'step_status',
                'step_date',
                'hint',
            ], ';');

            foreach ($registrations as $vfu) {
                foreach ($this->rowsFor($vfu) as $row) {
                    fputcsv($out, array_values($row), ';');
                }
            }

            fclose($out);
        }, $this->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportCsvFor(VfuRegistration $vfu): StreamedResponse
    {
        return $this->exportCsv(collect([$vfu]));
    }

    private function filename(): string
    {
        return 'vfu-storico-stati-'.now()->format('Y-m-d_His').'.csv';
    }
}
