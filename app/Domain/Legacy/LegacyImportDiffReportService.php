<?php

namespace App\Domain\Legacy;

use App\Domain\Audit\ActivityLogService;
use App\Models\LegacyImportSyncRun;
use App\Support\Demo\DemoContext;
use Illuminate\Support\Str;

class LegacyImportDiffReportService
{
    /** @var list<string> */
    public const DIFF_KEYS = ['new', 'updated', 'skipped', 'errors'];

    /**
     * @param  array<string, array{processed?: int, imported?: int, updated?: int, skipped?: int, errors?: list<string>}>  $entityResults
     * @return array<string, array{new: int, updated: int, skipped: int, errors: int, label: string}>
     */
    public function fromEntityResults(array $entityResults): array
    {
        $summary = [];

        foreach ($entityResults as $entity => $result) {
            $summary[$entity] = [
                'label'   => LegacyImportService::entityLabels()[$entity] ?? $entity,
                'new'     => (int) ($result['imported'] ?? 0),
                'updated' => (int) ($result['updated'] ?? 0),
                'skipped' => (int) ($result['skipped'] ?? 0),
                'errors'  => count($result['errors'] ?? []),
            ];
        }

        return $summary;
    }

    /**
     * @param  array<string, array{new: int, updated: int, skipped: int, errors: int}>  $diffSummary
     * @return array{new: int, updated: int, skipped: int, errors: int}
     */
    public function totals(array $diffSummary): array
    {
        return [
            'new'     => (int) collect($diffSummary)->sum('new'),
            'updated' => (int) collect($diffSummary)->sum('updated'),
            'skipped' => (int) collect($diffSummary)->sum('skipped'),
            'errors'  => (int) collect($diffSummary)->sum('errors'),
        ];
    }

    public function lastRun(): ?LegacyImportSyncRun
    {
        return LegacyImportSyncRun::query()
            ->latest('started_at')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, LegacyImportSyncRun>
     */
    public function recentRuns(int $limit = 5): \Illuminate\Support\Collection
    {
        return LegacyImportSyncRun::query()
            ->latest('started_at')
            ->limit(max(1, min(20, $limit)))
            ->get();
    }

    /**
     * @return list<array{run_id: string, started_at: string, status: string, dry_run: bool, totals: array{new: int, updated: int, skipped: int, errors: int}}>
     */
    public function runLogRows(int $limit = 5): array
    {
        return $this->recentRuns($limit)
            ->map(fn (LegacyImportSyncRun $run) => [
                'run_id'     => $run->run_id,
                'started_at' => $run->started_at?->format('d/m/Y H:i') ?? '—',
                'status'     => $run->status,
                'dry_run'    => (bool) $run->dry_run,
                'totals'     => [
                    'new'     => $run->total_new,
                    'updated' => $run->total_updated,
                    'skipped' => $run->total_skipped,
                    'errors'  => $run->total_errors,
                ],
            ])
            ->all();
    }
}
